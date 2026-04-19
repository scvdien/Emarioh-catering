(function (window, document) {
    document.addEventListener("DOMContentLoaded", function () {
        var form = document.getElementById("forgotPasswordForm");

        if (!form) {
            return;
        }

        var mobileInput = document.getElementById("forgotPhone");
        var otpInput = document.getElementById("forgotOtp");
        var passwordInput = document.getElementById("forgotPassword");
        var confirmPasswordInput = document.getElementById("forgotConfirmPassword");
        var stepPanels = Array.prototype.slice.call(document.querySelectorAll("[data-reset-step]"));
        var stepIndicators = Array.prototype.slice.call(document.querySelectorAll("[data-step-indicator]"));
        var otpTarget = document.querySelector('[data-reset-target="otp-mobile"]');
        var passwordTarget = document.querySelector('[data-reset-target="password-mobile"]');
        var otpFeedback = document.querySelector('[data-reset-feedback="otp"]');
        var resendOtpButton = document.querySelector("[data-reset-resend]");
        var sendOtpButton = document.querySelector("[data-reset-send-otp]");
        var verifyOtpButton = document.querySelector("[data-reset-verify-otp]");
        var changeNumberButton = document.querySelector("[data-reset-change-number]");
        var backToOtpButton = document.querySelector("[data-reset-back-to-otp]");
        var completeButton = document.getElementById("forgotCompleteButton");
        var otpVerified = false;
        var otpCountdown = 0;
        var otpTimerId = null;

        function digits(value) {
            return String(value || "").replace(/\D/g, "");
        }

        function maskMobile(value) {
            var normalizedDigits = digits(value);

            if (!normalizedDigits) {
                return "your mobile number";
            }

            if (normalizedDigits.length <= 4) {
                return normalizedDigits;
            }

            return normalizedDigits.slice(0, 4) + " *** " + normalizedDigits.slice(-4);
        }

        function setStep(step) {
            stepPanels.forEach(function (panel) {
                var isActive = Number(panel.getAttribute("data-reset-step")) === step;
                panel.hidden = !isActive;
                panel.classList.toggle("is-active", isActive);
            });

            stepIndicators.forEach(function (indicator) {
                var indicatorStep = Number(indicator.getAttribute("data-step-indicator"));
                indicator.classList.toggle("is-active", indicatorStep === step);
                indicator.classList.toggle("is-complete", indicatorStep < step);
            });
        }

        function setOtpFeedback(type, message) {
            if (!message) {
                otpFeedback.className = "register-inline-note register-inline-note--info is-hidden";
                otpFeedback.textContent = "";
                return;
            }

            otpFeedback.className = "register-inline-note register-inline-note--" + type;
            otpFeedback.textContent = message;
        }

        function clearOtpTimer() {
            if (otpTimerId) {
                window.clearInterval(otpTimerId);
                otpTimerId = null;
            }
        }

        function updateResendState() {
            if (otpCountdown > 0) {
                resendOtpButton.disabled = true;
                resendOtpButton.textContent = "Resend in " + otpCountdown + "s";
                return;
            }

            resendOtpButton.disabled = false;
            resendOtpButton.textContent = "Resend";
        }

        function startOtpTimer() {
            clearOtpTimer();
            otpCountdown = 45;
            updateResendState();

            otpTimerId = window.setInterval(function () {
                otpCountdown -= 1;

                if (otpCountdown <= 0) {
                    otpCountdown = 0;
                    clearOtpTimer();
                }

                updateResendState();
            }, 1000);
        }

        function setButtonsBusy(buttons, isBusy, busyLabel) {
            buttons.forEach(function (button) {
                if (!button) {
                    return;
                }

                if (!button.dataset.defaultLabel) {
                    button.dataset.defaultLabel = button.textContent;
                }

                button.disabled = isBusy;
                button.textContent = isBusy ? busyLabel : button.dataset.defaultLabel;
            });
        }

        function validateMobileStep() {
            mobileInput.setCustomValidity("");

            if (!mobileInput.value.trim()) {
                mobileInput.setCustomValidity("Enter the mobile number linked to your account.");
                mobileInput.reportValidity();
                return false;
            }

            if (digits(mobileInput.value).length < 10) {
                mobileInput.setCustomValidity("Enter a valid mobile number.");
                mobileInput.reportValidity();
                return false;
            }

            return true;
        }

        function validatePasswordStep() {
            passwordInput.setCustomValidity("");
            confirmPasswordInput.setCustomValidity("");

            if (!passwordInput.value) {
                passwordInput.setCustomValidity("Create your new password.");
                passwordInput.reportValidity();
                return false;
            }

            if (passwordInput.value.length < 8) {
                passwordInput.setCustomValidity("Password must be at least 8 characters.");
                passwordInput.reportValidity();
                return false;
            }

            if (!confirmPasswordInput.value) {
                confirmPasswordInput.setCustomValidity("Confirm your new password.");
                confirmPasswordInput.reportValidity();
                return false;
            }

            if (passwordInput.value !== confirmPasswordInput.value) {
                confirmPasswordInput.setCustomValidity("Passwords do not match.");
                confirmPasswordInput.reportValidity();
                return false;
            }

            return true;
        }

        async function requestOtp(isResend) {
            if (!validateMobileStep()) {
                return;
            }

            otpVerified = false;
            setButtonsBusy([sendOtpButton, resendOtpButton], true, "Sending...");

            try {
                var response = await window.EmariohAuth.post("request-password-reset.php", {
                    mobile: mobileInput.value.trim()
                });

                otpTarget.textContent = response.masked_mobile || maskMobile(mobileInput.value);
                passwordTarget.textContent = response.masked_mobile || maskMobile(mobileInput.value);
                setStep(2);
                startOtpTimer();
                otpInput.value = "";
                otpInput.focus();

                if (response.demo_otp) {
                    setOtpFeedback("info", "Development OTP: " + response.demo_otp + ". Use this while SMS delivery is not connected yet.");
                } else {
                    setOtpFeedback("info", response.message || "OTP sent. Enter it to continue.");
                }
            } catch (error) {
                mobileInput.setCustomValidity(error.message || "OTP request failed. Please try again.");
                mobileInput.reportValidity();
            } finally {
                setButtonsBusy([sendOtpButton], false);
                updateResendState();
            }
        }

        async function verifyOtp() {
            var enteredOtp = digits(otpInput.value).slice(0, 6);

            if (enteredOtp.length !== 6) {
                setOtpFeedback("error", "Enter the 6-digit code.");
                otpInput.focus();
                return;
            }

            setButtonsBusy([verifyOtpButton], true, "Verifying...");

            try {
                await window.EmariohAuth.post("verify-password-reset-otp.php", {
                    mobile: mobileInput.value.trim(),
                    otp: enteredOtp
                });

                otpVerified = true;
                clearOtpTimer();
                setOtpFeedback("", "");
                setStep(3);
                passwordInput.focus();
            } catch (error) {
                otpVerified = false;
                setOtpFeedback("error", error.message || "OTP verification failed.");
            } finally {
                setButtonsBusy([verifyOtpButton], false);
            }
        }

        sendOtpButton.addEventListener("click", function () {
            requestOtp(false);
        });

        resendOtpButton.addEventListener("click", function () {
            requestOtp(true);
        });

        verifyOtpButton.addEventListener("click", function () {
            verifyOtp();
        });

        changeNumberButton.addEventListener("click", function () {
            otpVerified = false;
            clearOtpTimer();
            setOtpFeedback("", "");
            setStep(1);
            mobileInput.focus();
        });

        backToOtpButton.addEventListener("click", function () {
            setStep(2);
            otpInput.focus();
        });

        otpInput.addEventListener("input", function () {
            otpInput.value = digits(otpInput.value).slice(0, 6);
        });

        [mobileInput, passwordInput, confirmPasswordInput].forEach(function (field) {
            field.addEventListener("input", function () {
                if (field.setCustomValidity) {
                    field.setCustomValidity("");
                }
            });
            field.addEventListener("change", function () {
                if (field.setCustomValidity) {
                    field.setCustomValidity("");
                }
            });
        });

        document.querySelectorAll("[data-toggle-password]").forEach(function (button) {
            button.addEventListener("click", function () {
                var input = document.getElementById(button.getAttribute("data-toggle-password"));

                if (!input) {
                    return;
                }

                var isPassword = input.type === "password";
                input.type = isPassword ? "text" : "password";
                button.setAttribute("aria-label", isPassword ? "Hide password" : "Show password");
                button.innerHTML = isPassword ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
            });
        });

        form.addEventListener("submit", async function (event) {
            event.preventDefault();

            if (!validateMobileStep()) {
                setStep(1);
                return;
            }

            if (!otpVerified) {
                setStep(2);
                setOtpFeedback("error", "Verify your OTP first.");
                return;
            }

            if (!validatePasswordStep()) {
                setStep(3);
                return;
            }

            setButtonsBusy([completeButton], true, "Updating...");

            try {
                var response = await window.EmariohAuth.post("reset-password.php", {
                    mobile: mobileInput.value.trim(),
                    password: passwordInput.value
                });

                window.EmariohAuth.clearStatusCache();
                window.location.href = response.redirect_url || "login.php?password_reset=1";
            } catch (error) {
                if (/otp/i.test(error.message || "")) {
                    otpVerified = false;
                    setStep(2);
                    setOtpFeedback("error", error.message || "Verify your OTP again.");
                } else {
                    setStep(3);
                    confirmPasswordInput.setCustomValidity(error.message || "Password reset failed. Please try again.");
                    confirmPasswordInput.reportValidity();
                }
            } finally {
                setButtonsBusy([completeButton], false);
            }
        });

        setStep(1);
        updateResendState();
    });
}(window, document));
