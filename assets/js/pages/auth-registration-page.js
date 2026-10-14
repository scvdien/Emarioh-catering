(function (window, document) {
    document.addEventListener("DOMContentLoaded", function () {
        var form = document.getElementById("registrationForm");

        if (!form) {
            return;
        }

        var eyebrow = document.getElementById("registerEyebrow");
        var pageTitle = document.getElementById("registerPageTitle");
        var pageSubtitle = document.getElementById("registerPageSubtitle");
        var modeBanner = document.getElementById("registerModeBanner");
        var nameLabel = document.getElementById("registerNameLabel");
        var phoneLabel = document.getElementById("registerPhoneLabel");
        var consentCopy = document.getElementById("registerConsentCopy");
        var otpCopyLead = document.getElementById("registerOtpCopyLead");
        var passwordCopyLead = document.getElementById("registerPasswordCopyLead");
        var footer = document.getElementById("registerFooter");
        var fullNameInput = document.getElementById("registerName");
        var mobileInput = document.getElementById("registerPhone");
        var agreeInput = document.getElementById("agreeTerms");
        var otpInput = document.getElementById("registerOtp");
        var passwordInput = document.getElementById("registerPassword");
        var confirmPasswordInput = document.getElementById("registerConfirmPassword");
        var stepPanels = Array.prototype.slice.call(document.querySelectorAll("[data-register-step]"));
        var stepIndicators = Array.prototype.slice.call(document.querySelectorAll("[data-step-indicator]"));
        var otpTarget = document.querySelector("[data-otp-target]");
        var passwordTarget = document.querySelector("[data-password-target]");
        var otpFeedback = document.querySelector("[data-otp-feedback]");
        var resendOtpButton = document.querySelector("[data-resend-otp]");
        var sendOtpButton = document.querySelector("[data-send-otp]");
        var verifyOtpButton = document.querySelector("[data-verify-otp]");
        var changeNumberButton = document.querySelector("[data-change-number]");
        var backToOtpButton = document.querySelector("[data-back-to-otp]");
        var completeButton = document.getElementById("registerCompleteButton");
        var params = new URLSearchParams(window.location.search);
        var otpVerified = false;
        var otpCountdown = 0;
        var otpTimerId = null;
        var currentMode = "client_registration";

        function digits(value) {
            return String(value || "").replace(/\D/g, "");
        }

        function formatPhilippineLocalMobile(raw) {
            var d = String(raw || "").replace(/\D/g, "");
            if (d.indexOf("63") === 0 && d.length > 2) {
                d = d.slice(2);
            }
            while (d.charAt(0) === "0") {
                d = d.slice(1);
            }
            d = d.slice(0, 10);
            if (d.length <= 3) {
                return d;
            }
            if (d.length <= 6) {
                return d.slice(0, 3) + " " + d.slice(3);
            }
            return d.slice(0, 3) + " " + d.slice(3, 6) + " " + d.slice(6, 10);
        }

        function getNormalizedMobileSubmission(value) {
            var d = digits(value);
            if (d.indexOf("63") === 0 && d.length > 2) {
                d = d.slice(2);
            }
            while (d.charAt(0) === "0") {
                d = d.slice(1);
            }
            return "63" + d;
        }

        function maskMobile(value) {
            var d = digits(value);
            if (d.indexOf("63") === 0 && d.length > 2) {
                d = d.slice(2);
            }
            while (d.charAt(0) === "0") {
                d = d.slice(1);
            }

            if (!d) {
                return currentMode === "admin_setup" ? "your admin mobile number" : "your mobile number";
            }

            if (d.length <= 4) {
                return "+63 " + d;
            }

            return "+63 " + d.slice(0, 3) + " *** " + d.slice(-4);
        }

        function setStep(step) {
            stepPanels.forEach(function (panel) {
                var isActive = Number(panel.getAttribute("data-register-step")) === step;
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

        function refreshMode(status) {
            var forcedSetup = params.get("setup") === "admin";
            currentMode = status.admin_exists ? "client_registration" : "admin_setup";
            eyebrow.hidden = currentMode !== "admin_setup";
            modeBanner.hidden = currentMode !== "admin_setup" && !forcedSetup;

            if (currentMode === "admin_setup") {
                document.title = "First-Time Admin Setup | Emarioh Catering Services";
                eyebrow.textContent = "Setup Mode";
                pageTitle.textContent = "First-Time Admin Setup";
                pageSubtitle.textContent = "Create the first admin account before opening the shared login for the full system.";
                modeBanner.innerHTML = "<strong>No Admin Account Detected</strong><span>This backend now stores the first admin account in the local database. Finish setup here before client registration becomes available.</span>";
                nameLabel.textContent = "Admin Name";
                phoneLabel.textContent = "Admin Mobile Number";
                fullNameInput.placeholder = "Emarioh Admin";
                consentCopy.textContent = "I understand this mobile number will receive admin OTP verification.";
                sendOtpButton.textContent = "Send Setup OTP";
                verifyOtpButton.textContent = "Verify OTP";
                completeButton.textContent = "Create Admin Access";
                otpCopyLead.textContent = "Enter the 6-digit OTP sent to";
                passwordCopyLead.textContent = "verified. Create the admin password to finish setup.";
                footer.innerHTML = 'Already finished setup? <a href="login.php">Go to log in</a>.';
                form.dataset.mode = currentMode;
                return;
            }

            document.title = "Client Registration | Emarioh Catering Services";
            pageTitle.textContent = "Create an Account";
            pageSubtitle.textContent = "Register your client portal access in 3 easy steps.";
            modeBanner.innerHTML = forcedSetup
                ? "<strong>Admin Setup Already Completed</strong><span>The first admin account already exists, so this page is now used for client registration.</span>"
                : "";
            nameLabel.textContent = "Full Name";
            phoneLabel.textContent = "Mobile Number";
            fullNameInput.placeholder = "Maria Santos";
            consentCopy.innerHTML = 'I agree to the <button type="button" class="register-policy-link" data-open-policy="terms">Terms of Service</button> and <button type="button" class="register-policy-link" data-open-policy="privacy">Privacy Policy</button>.';
            sendOtpButton.textContent = "Send OTP";
            verifyOtpButton.textContent = "Verify OTP";
            completeButton.textContent = "Create Account";
            otpCopyLead.textContent = "Enter the code sent to";
            passwordCopyLead.textContent = "verified";
            footer.innerHTML = 'Already have an account? <a href="login.php">Sign in</a>.';
            form.dataset.mode = currentMode;
        }

        function validateStepOne() {
            fullNameInput.setCustomValidity("");
            mobileInput.setCustomValidity("");
            agreeInput.setCustomValidity("");

            if (!fullNameInput.value.trim()) {
                fullNameInput.setCustomValidity(currentMode === "admin_setup" ? "Enter the admin name." : "Enter your full name.");
                fullNameInput.reportValidity();
                return false;
            }

            var cleanDigits = digits(mobileInput.value);
            if (cleanDigits.indexOf("63") === 0 && cleanDigits.length > 2) {
                cleanDigits = cleanDigits.slice(2);
            }
            while (cleanDigits.charAt(0) === "0") {
                cleanDigits = cleanDigits.slice(1);
            }

            if (!cleanDigits) {
                mobileInput.setCustomValidity(currentMode === "admin_setup" ? "Enter the admin mobile number." : "Enter your mobile number.");
                mobileInput.reportValidity();
                return false;
            }

            if (cleanDigits.length !== 10 || cleanDigits.charAt(0) !== "9") {
                mobileInput.setCustomValidity("Please enter a valid 10-digit mobile number starting with 9.");
                mobileInput.reportValidity();
                return false;
            }

            if (!agreeInput.checked) {
                agreeInput.setCustomValidity(currentMode === "admin_setup" ? "Please confirm the OTP consent." : "Please agree to continue.");
                agreeInput.reportValidity();
                return false;
            }

            return true;
        }

        function validatePasswordStep() {
            passwordInput.setCustomValidity("");
            confirmPasswordInput.setCustomValidity("");

            if (!passwordInput.value) {
                passwordInput.setCustomValidity(currentMode === "admin_setup" ? "Create the admin password." : "Create your password.");
                passwordInput.reportValidity();
                return false;
            }

            if (passwordInput.value.length < 8) {
                passwordInput.setCustomValidity("Password must be at least 8 characters.");
                passwordInput.reportValidity();
                return false;
            }

            if (!confirmPasswordInput.value) {
                confirmPasswordInput.setCustomValidity(currentMode === "admin_setup" ? "Confirm the admin password." : "Confirm your password.");
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
            if (!validateStepOne()) {
                return;
            }

            otpVerified = false;
            setButtonsBusy([sendOtpButton, resendOtpButton], true, isResend ? "Sending..." : "Sending...");

            try {
                var response = await window.EmariohAuth.post("request-otp.php", {
                    full_name: fullNameInput.value.trim(),
                    mobile: getNormalizedMobileSubmission(mobileInput.value),
                    mode: currentMode
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
                await window.EmariohAuth.post("verify-otp.php", {
                    mobile: getNormalizedMobileSubmission(mobileInput.value),
                    otp: enteredOtp,
                    mode: currentMode
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

        mobileInput.addEventListener("input", function () {
            var formatted = formatPhilippineLocalMobile(mobileInput.value);
            if (mobileInput.value !== formatted) {
                mobileInput.value = formatted;
            }
        });

        [fullNameInput, mobileInput, agreeInput, passwordInput, confirmPasswordInput].forEach(function (field) {
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

            if (!validateStepOne()) {
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

            setButtonsBusy([completeButton], true, currentMode === "admin_setup" ? "Creating..." : "Creating...");

            try {
                var response = await window.EmariohAuth.post("register.php", {
                    full_name: fullNameInput.value.trim(),
                    mobile: getNormalizedMobileSubmission(mobileInput.value),
                    password: passwordInput.value,
                    mode: currentMode
                });

                window.EmariohAuth.clearStatusCache();
                window.location.href = response.redirect_url || "login.php";
            } catch (error) {
                if (/otp/i.test(error.message || "")) {
                    otpVerified = false;
                    setStep(2);
                    setOtpFeedback("error", error.message || "Verify your OTP again.");
                } else {
                    setStep(3);
                    confirmPasswordInput.setCustomValidity(error.message || "Account creation failed. Please try again.");
                    confirmPasswordInput.reportValidity();
                }
            } finally {
                setButtonsBusy([completeButton], false);
            }
        });

        function initPolicyModal() {
            var modal = document.getElementById("policyModal");
            if (!modal) {
                return;
            }

            var tabButtons = Array.prototype.slice.call(modal.querySelectorAll("[data-policy-tab]"));
            var panes = Array.prototype.slice.call(modal.querySelectorAll("[data-policy-pane]"));
            var modalTitle = document.getElementById("policyModalTitle");

            function switchTab(type) {
                var targetType = type === "privacy" ? "privacy" : "terms";

                tabButtons.forEach(function (btn) {
                    var isMatch = btn.getAttribute("data-policy-tab") === targetType;
                    btn.classList.toggle("is-active", isMatch);
                    btn.setAttribute("aria-selected", isMatch ? "true" : "false");
                });

                panes.forEach(function (pane) {
                    var isMatch = pane.getAttribute("data-policy-pane") === targetType;
                    pane.classList.toggle("is-active", isMatch);
                    pane.hidden = !isMatch;
                });

                if (modalTitle) {
                    modalTitle.textContent = targetType === "privacy" ? "Privacy Policy" : "Terms of Service";
                }
            }

            function openModal(type) {
                switchTab(type);
                modal.classList.add("is-visible");
                modal.setAttribute("aria-hidden", "false");
                document.body.classList.add("modal-open");
            }

            function closeModal() {
                modal.classList.remove("is-visible");
                modal.setAttribute("aria-hidden", "true");
                document.body.classList.remove("modal-open");
            }

            document.addEventListener("click", function (event) {
                var trigger = event.target.closest("[data-open-policy]");
                if (trigger) {
                    event.preventDefault();
                    event.stopPropagation();
                    openModal(trigger.getAttribute("data-open-policy"));
                    return;
                }

                if (event.target.closest("[data-close-policy]")) {
                    event.preventDefault();
                    closeModal();
                    return;
                }

                if (event.target === modal) {
                    closeModal();
                }
            });

            tabButtons.forEach(function (btn) {
                btn.addEventListener("click", function (event) {
                    event.preventDefault();
                    switchTab(btn.getAttribute("data-policy-tab"));
                });
            });

            document.addEventListener("keydown", function (event) {
                if (event.key === "Escape" && modal.classList.contains("is-visible")) {
                    closeModal();
                }
            });
        }

        initPolicyModal();

        window.EmariohAuth.getStatus(false)
            .then(function (status) {
                refreshMode(status);
                setStep(1);
                updateResendState();
            })
            .catch(function () {
                modeBanner.hidden = false;
                modeBanner.innerHTML = "<strong>Backend Unavailable</strong><span>Please make sure Apache or your local PHP server is running before continuing with registration.</span>";
            });
    });
}(window, document));
