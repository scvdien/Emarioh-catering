(function () {
    function getDigits(value) {
        return String(value || "").replace(/\D/g, "");
    }

    function maskMobile(value) {
        var digits = getDigits(value);

        if (!digits) {
            return "your admin mobile number";
        }

        if (digits.length <= 4) {
            return digits;
        }

        return digits.slice(0, 4) + " *** " + digits.slice(-4);
    }

    function togglePasswordField(button) {
        var inputId = button.getAttribute("data-toggle-password");
        var input = inputId ? document.getElementById(inputId) : null;

        if (!input) {
            return;
        }

        var isPassword = input.type === "password";
        input.type = isPassword ? "text" : "password";
        button.setAttribute("aria-label", isPassword ? "Hide password" : "Show password");
        button.innerHTML = isPassword ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
    }

    function reportFieldError(field, message) {
        if (!field) {
            return false;
        }

        field.setCustomValidity(message);
        field.reportValidity();
        return false;
    }

    function clearFieldError(field) {
        if (field) {
            field.setCustomValidity("");
        }
    }

    function goToOtpPage(mode, mobile) {
        var query = new URLSearchParams({
            mode: mode,
            mobile: getDigits(mobile)
        });

        window.location.href = "admin-otp.php?" + query.toString();
    }

    document.querySelectorAll("[data-toggle-password]").forEach(function (button) {
        button.addEventListener("click", function () {
            togglePasswordField(button);
        });
    });

    var adminLoginForm = document.getElementById("adminLoginForm");

    if (adminLoginForm) {
        var adminLoginMobile = document.getElementById("adminLoginMobile");
        var adminLoginPassword = document.getElementById("adminLoginPassword");

        adminLoginMobile?.addEventListener("input", function () {
            clearFieldError(adminLoginMobile);
        });

        adminLoginPassword?.addEventListener("input", function () {
            clearFieldError(adminLoginPassword);
        });

        adminLoginForm.addEventListener("submit", function (event) {
            event.preventDefault();

            var mobileDigits = getDigits(adminLoginMobile?.value);
            var passwordValue = adminLoginPassword?.value.trim() || "";

            clearFieldError(adminLoginMobile);
            clearFieldError(adminLoginPassword);

            if (!mobileDigits) {
                reportFieldError(adminLoginMobile, "Enter the admin mobile number.");
                return;
            }

            if (mobileDigits.length < 10) {
                reportFieldError(adminLoginMobile, "Enter a valid mobile number.");
                return;
            }

            if (!passwordValue) {
                reportFieldError(adminLoginPassword, "Enter the admin password.");
                return;
            }

            goToOtpPage("login", adminLoginMobile.value);
        });
    }

    var adminSetupForm = document.getElementById("adminSetupForm");

    if (adminSetupForm) {
        var adminSetupName = document.getElementById("adminSetupName");
        var adminSetupMobile = document.getElementById("adminSetupMobile");
        var adminSetupPassword = document.getElementById("adminSetupPassword");
        var adminSetupConfirmPassword = document.getElementById("adminSetupConfirmPassword");
        var adminSetupOtpConsent = document.getElementById("adminSetupOtpConsent");

        [adminSetupName, adminSetupMobile, adminSetupPassword, adminSetupConfirmPassword, adminSetupOtpConsent].forEach(function (field) {
            field?.addEventListener("input", function () {
                clearFieldError(field);
            });
            field?.addEventListener("change", function () {
                clearFieldError(field);
            });
        });

        adminSetupForm.addEventListener("submit", function (event) {
            event.preventDefault();

            var mobileDigits = getDigits(adminSetupMobile?.value);
            var passwordValue = adminSetupPassword?.value || "";
            var confirmPasswordValue = adminSetupConfirmPassword?.value || "";

            [adminSetupName, adminSetupMobile, adminSetupPassword, adminSetupConfirmPassword, adminSetupOtpConsent].forEach(clearFieldError);

            if (!adminSetupName?.value.trim()) {
                reportFieldError(adminSetupName, "Enter the admin name.");
                return;
            }

            if (!mobileDigits) {
                reportFieldError(adminSetupMobile, "Enter the admin mobile number.");
                return;
            }

            if (mobileDigits.length < 10) {
                reportFieldError(adminSetupMobile, "Enter a valid mobile number.");
                return;
            }

            if (passwordValue.length < 8) {
                reportFieldError(adminSetupPassword, "Password must be at least 8 characters.");
                return;
            }

            if (!confirmPasswordValue) {
                reportFieldError(adminSetupConfirmPassword, "Confirm the admin password.");
                return;
            }

            if (passwordValue !== confirmPasswordValue) {
                reportFieldError(adminSetupConfirmPassword, "Passwords do not match.");
                return;
            }

            if (!adminSetupOtpConsent?.checked) {
                reportFieldError(adminSetupOtpConsent, "Please confirm that the number will receive SMS OTP.");
                return;
            }

            goToOtpPage("setup", adminSetupMobile.value);
        });
    }

    var adminOtpForm = document.getElementById("adminOtpForm");

    if (adminOtpForm) {
        var params = new URLSearchParams(window.location.search);
        var mode = params.get("mode") === "setup" ? "setup" : "login";
        var mobile = params.get("mobile") || "";
        var otpInputs = Array.prototype.slice.call(document.querySelectorAll("[data-admin-otp-input]"));
        var feedback = document.querySelector("[data-admin-otp-feedback]");
        var title = document.querySelector("[data-admin-otp-title]");
        var subtitle = document.querySelector("[data-admin-otp-subtitle]");
        var noteTitle = document.querySelector("[data-admin-otp-note-title]");
        var noteCopy = document.querySelector("[data-admin-otp-note-copy]");
        var mobileTarget = document.querySelector("[data-admin-mobile-target]");
        var submitButton = document.querySelector("[data-admin-otp-submit]");
        var footer = document.querySelector("[data-admin-otp-footer]");
        var backLink = document.querySelector("[data-admin-back-link]");
        var backLabel = document.querySelector("[data-admin-back-label]");
        var editLink = document.querySelector("[data-admin-edit-link]");
        var resendButton = document.querySelector("[data-admin-resend]");

        function showFeedback(message, tone) {
            if (!feedback) {
                return;
            }

            feedback.hidden = false;
            feedback.textContent = message;
            feedback.setAttribute("data-tone", tone || "info");
        }

        function clearFeedback() {
            if (!feedback) {
                return;
            }

            feedback.hidden = true;
            feedback.textContent = "";
            feedback.removeAttribute("data-tone");
        }

        if (mode === "setup") {
            if (title) {
                title.textContent = "Verify Admin Setup";
            }

            if (subtitle) {
                subtitle.textContent = "Confirm the SMS OTP for the new admin mobile number before opening the dashboard.";
            }

            if (noteTitle) {
                noteTitle.textContent = "Demo setup verification.";
            }

            if (noteCopy) {
                noteCopy.textContent = "This UI shows the admin onboarding step. Real SMS delivery and account activation will be connected later.";
            }

            if (submitButton) {
                submitButton.textContent = "Activate Admin Dashboard";
            }

            if (footer) {
                footer.textContent = "After verification, this prototype will continue to the admin dashboard home screen.";
            }

            if (backLink) {
                backLink.setAttribute("href", "registration.php?setup=admin");
            }

            if (backLabel) {
                backLabel.textContent = "Back to admin setup";
            }

            if (editLink) {
                editLink.setAttribute("href", "registration.php?setup=admin");
            }
        } else {
            if (backLink) {
                backLink.setAttribute("href", "login.php");
            }

            if (backLabel) {
                backLabel.textContent = "Back to log in";
            }

            if (editLink) {
                editLink.setAttribute("href", "login.php");
            }
        }

        if (mobileTarget) {
            mobileTarget.textContent = maskMobile(mobile);
        }

        otpInputs.forEach(function (input, index) {
            input.addEventListener("input", function () {
                input.value = getDigits(input.value).slice(0, 1);
                clearFeedback();

                if (input.value && otpInputs[index + 1]) {
                    otpInputs[index + 1].focus();
                    otpInputs[index + 1].select();
                }
            });

            input.addEventListener("keydown", function (event) {
                if (event.key === "Backspace" && !input.value && otpInputs[index - 1]) {
                    otpInputs[index - 1].focus();
                    return;
                }

                if (event.key === "ArrowLeft" && otpInputs[index - 1]) {
                    otpInputs[index - 1].focus();
                }

                if (event.key === "ArrowRight" && otpInputs[index + 1]) {
                    otpInputs[index + 1].focus();
                }
            });
        });

        adminOtpForm.addEventListener("paste", function (event) {
            var pastedDigits = getDigits(event.clipboardData?.getData("text") || "").slice(0, otpInputs.length);

            if (!pastedDigits) {
                return;
            }

            event.preventDefault();
            clearFeedback();

            otpInputs.forEach(function (input, index) {
                input.value = pastedDigits.charAt(index) || "";
            });

            var focusTarget = otpInputs[Math.min(pastedDigits.length, otpInputs.length - 1)];
            focusTarget?.focus();
        });

        resendButton?.addEventListener("click", function () {
            showFeedback("Prototype only: the resend action is part of the UI flow, but no real SMS is sent yet.", "info");
        });

        adminOtpForm.addEventListener("submit", function (event) {
            event.preventDefault();

            var code = otpInputs.map(function (input) {
                return getDigits(input.value);
            }).join("");

            if (code.length !== otpInputs.length) {
                showFeedback("Enter the full 6-digit OTP before continuing.", "error");

                var emptyInput = otpInputs.find(function (input) {
                    return !getDigits(input.value);
                });

                emptyInput?.focus();
                return;
            }

            window.location.href = "index.php";
        });

        otpInputs[0]?.focus();
    }
}());
