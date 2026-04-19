(function (window, document) {
    document.addEventListener("DOMContentLoaded", function () {
        var loginForm = document.getElementById("loginForm");

        if (!loginForm) {
            return;
        }

        var loginIdentity = document.getElementById("loginIdentity");
        var loginPassword = document.getElementById("loginPassword");
        var rememberMe = document.getElementById("rememberMe");
        var loginFeedback = document.getElementById("loginFeedback");
        var loginPageTitle = document.getElementById("loginPageTitle");
        var loginPageSubtitle = document.getElementById("loginPageSubtitle");
        var loginIntroNote = document.getElementById("loginIntroNote");
        var clientLoginFooter = document.getElementById("clientLoginFooter");
        var adminSetupNotice = document.getElementById("adminSetupNotice");
        var adminSetupLink = document.getElementById("adminSetupLink");
        var adminDetectedFooter = document.getElementById("adminDetectedFooter");
        var submitButton = loginForm.querySelector(".login-submit");
        var params = new URLSearchParams(window.location.search);

        function digits(value) {
            return String(value || "").replace(/\D/g, "");
        }

        function showFeedback(message, tone) {
            loginFeedback.hidden = false;
            loginFeedback.textContent = message;
            loginFeedback.setAttribute("data-tone", tone || "info");
        }

        function clearFeedback() {
            loginFeedback.hidden = true;
            loginFeedback.textContent = "";
            loginFeedback.removeAttribute("data-tone");
        }

        function setSubmitting(isSubmitting) {
            submitButton.disabled = isSubmitting;
            submitButton.textContent = isSubmitting ? "Logging In..." : "Log In";
        }

        function refreshState(status) {
            var adminExists = Boolean(status && status.admin_exists);

            document.title = adminExists ? "Log In | Emarioh Catering Services" : "First-Time Setup Required | Emarioh Catering Services";
            loginPageTitle.textContent = adminExists ? "Log In" : "First-Time Setup Required";
            loginPageSubtitle.textContent = adminExists
                ? "Use your mobile number and password to access your account."
                : "Create the first admin account in registration before using the shared login form for both admin and client accounts.";
            loginIntroNote.hidden = true;
            loginIntroNote.textContent = "";
            loginForm.hidden = !adminExists;
            clientLoginFooter.hidden = !adminExists;
            if (adminSetupNotice) {
                adminSetupNotice.hidden = adminExists;
            }

            if (adminSetupLink) {
                adminSetupLink.hidden = adminExists;
            }

            if (adminDetectedFooter) {
                adminDetectedFooter.hidden = true;
            }

            if (!adminExists) {
                clearFeedback();
                loginPassword.value = "";
            }
        }

        function showQueryMessage() {
            if (params.get("registered") === "1") {
                showFeedback("Account created successfully. Log in to continue.", "success");
                return;
            }

            if (params.get("password_reset") === "1") {
                showFeedback("Password updated. Log in with your new password.", "success");
            }
        }

        document.querySelectorAll("[data-toggle-password]").forEach(function (button) {
            button.addEventListener("click", function () {
                var input = document.getElementById(button.getAttribute("data-toggle-password"));

                if (!input) {
                    return;
                }

                var isHidden = input.type === "password";
                input.type = isHidden ? "text" : "password";
                button.setAttribute("aria-label", isHidden ? "Hide password" : "Show password");
                button.innerHTML = isHidden ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
            });
        });

        loginIdentity.addEventListener("input", function () {
            loginIdentity.setCustomValidity("");
            loginPassword.setCustomValidity("");
            clearFeedback();
        });

        loginPassword.addEventListener("input", function () {
            loginPassword.setCustomValidity("");
            clearFeedback();
        });

        loginForm.addEventListener("submit", async function (event) {
            event.preventDefault();
            clearFeedback();
            loginIdentity.setCustomValidity("");
            loginPassword.setCustomValidity("");

            if (!digits(loginIdentity.value)) {
                loginIdentity.setCustomValidity("Enter your mobile number.");
                loginIdentity.reportValidity();
                return;
            }

            if (digits(loginIdentity.value).length < 10) {
                loginIdentity.setCustomValidity("Enter a valid mobile number.");
                loginIdentity.reportValidity();
                return;
            }

            if (!loginPassword.value.trim()) {
                loginPassword.setCustomValidity("Enter your password.");
                loginPassword.reportValidity();
                return;
            }

            setSubmitting(true);

            try {
                var response = await window.EmariohAuth.post("login.php", {
                    mobile: loginIdentity.value,
                    password: loginPassword.value,
                    remember_me: Boolean(rememberMe && rememberMe.checked)
                });

                window.EmariohAuth.clearStatusCache();
                window.location.href = response.redirect_url || "client-dashboard.php";
            } catch (error) {
                var payload = error && error.payload ? error.payload : {};

                if (payload.setup_required && payload.redirect_url) {
                    window.location.href = payload.redirect_url;
                    return;
                }

                showFeedback(error.message || "Login failed. Please try again.", "error");
            } finally {
                setSubmitting(false);
            }
        });

        window.EmariohAuth.getStatus(true)
            .then(function (status) {
                refreshState(status);
                showQueryMessage();

                if (!status.admin_exists && params.get("show_setup") === "admin") {
                    if (adminSetupLink) {
                        adminSetupLink.focus();
                    }
                    return;
                }

                if (status.admin_exists) {
                    loginIdentity.focus();
                }
            })
            .catch(function () {
                showFeedback("Backend auth status could not be loaded. Please make sure the local server is running.", "error");
            });
    });
}(window, document));
