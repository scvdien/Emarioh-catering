<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$registrationDb = emarioh_db();
$registrationSetupMode = trim((string) ($_GET['setup'] ?? ''));

if ($registrationSetupMode === 'admin' && emarioh_admin_exists($registrationDb)) {
    emarioh_redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Client registration for Emarioh Catering Services online booking reservation system.">
    <title>Client Registration | Emarioh Catering Services</title>
    <?= emarioh_render_vendor_head_assets(); ?>
    <link rel="stylesheet" href="assets/css/auth.css?v=20260331f">
    <link rel="stylesheet" href="assets/css/pages/registration.css?v=20260419a">
</head>
<body class="auth-page auth-page--register" data-auth-guard="guest">
    <main class="register-shell">
        <section class="register-panel" aria-label="Registration form">
            <div class="register-topbar">
                <a href="public-page.php" class="register-brand" aria-label="Back to Emarioh public page">
                    <span class="register-brand__mark">
                        <img src="assets/images/logo.jpg" alt="Emarioh Catering Services logo">
                    </span>
                    <span>
                        <span class="register-brand__name">EMARIOH</span>
                        <span class="register-brand__sub">Catering Services</span>
                    </span>
                </a>
                <a class="register-back" href="public-page.php">
                    <i class="bi bi-arrow-left"></i>
                    <span class="register-back__desktop">Back to website</span>
                    <span class="register-back__mobile">Back</span>
                </a>
            </div>

            <div class="register-content">
                <div class="register-heading">
                    <p class="register-eyebrow" id="registerEyebrow" hidden>Setup Mode</p>
                    <h1 id="registerPageTitle">Create an Account</h1>
                    <p id="registerPageSubtitle">Sign up in 3 easy steps.</p>
                </div>
                <div class="register-mode-banner" id="registerModeBanner" hidden></div>

                <div class="register-steps" aria-label="Registration steps">
                    <div class="register-step-chip is-active" data-step-indicator="1">
                        <span class="register-step-chip__count">1</span>
                        <strong>Details</strong>
                        <span>Start</span>
                    </div>
                    <div class="register-step-chip" data-step-indicator="2">
                        <span class="register-step-chip__count">2</span>
                        <strong>OTP</strong>
                        <span>Verify</span>
                    </div>
                    <div class="register-step-chip" data-step-indicator="3">
                        <span class="register-step-chip__count">3</span>
                        <strong>Password</strong>
                        <span>Finish</span>
                    </div>
                </div>

                <form class="register-form" id="registrationForm" action="client-dashboard.php" method="get" novalidate>
                    <section class="register-stage is-active" data-register-step="1">
                        <div class="register-grid">
                            <div class="register-field">
                                <label for="registerName" id="registerNameLabel">Full Name</label>
                                <div class="register-input-wrap">
                                    <input id="registerName" name="full_name" type="text" placeholder="Maria Santos" autocomplete="name" required>
                                </div>
                            </div>
                            <div class="register-field">
                                <label for="registerPhone" id="registerPhoneLabel">Mobile Number</label>
                                <div class="register-input-wrap">
                                    <input id="registerPhone" name="mobile" type="tel" inputmode="numeric" placeholder="0917 555 2481" autocomplete="tel" required>
                                </div>
                            </div>
                        </div>

                        <label class="register-agree" for="agreeTerms">
                            <input id="agreeTerms" name="agree_terms" type="checkbox" required>
                            <span id="registerConsentCopy">I agree to the <a href="public-page.php#contact">Terms of Service</a> and <a href="public-page.php#contact">Privacy Policy</a>.</span>
                        </label>

                        <div class="register-actions">
                            <button class="register-submit" type="button" data-send-otp id="registerSendOtpButton">Send OTP</button>
                        </div>
                    </section>

                    <section class="register-stage" data-register-step="2" hidden>
                        <p class="register-stage__copy"><span id="registerOtpCopyLead">Enter the code sent to</span> <strong data-otp-target>your mobile number</strong>.</p>

                        <div class="register-field">
                            <div class="register-input-wrap register-input-wrap--otp">
                                <input id="registerOtp" type="text" inputmode="numeric" autocomplete="one-time-code" placeholder="Enter code" maxlength="6">
                            </div>
                        </div>

                        <p class="register-inline-note register-inline-note--info is-hidden" data-otp-feedback aria-live="polite"></p>

                        <div class="register-actions register-actions--otp">
                            <button class="register-submit register-submit--otp" type="button" data-verify-otp>Verify OTP</button>
                            <div class="register-actions__links">
                                <button class="register-text-button" type="button" data-resend-otp>Resend</button>
                                <button class="register-text-button" type="button" data-change-number>Edit Number</button>
                            </div>
                        </div>
                    </section>

                    <section class="register-stage" data-register-step="3" hidden>
                        <p class="register-stage__copy"><strong data-password-target>Your mobile number</strong> <span id="registerPasswordCopyLead">verified</span></p>

                        <div class="register-grid register-grid--password">
                            <div class="register-field">
                                <label for="registerPassword">Password</label>
                                <div class="register-input-wrap register-input-wrap--password">
                                    <input id="registerPassword" name="password" type="password" placeholder="Create a strong password" minlength="8" autocomplete="new-password" required>
                                    <button class="register-toggle-password" type="button" data-toggle-password="registerPassword" aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="register-field">
                                <label for="registerConfirmPassword">Confirm Password</label>
                                <div class="register-input-wrap register-input-wrap--password">
                                    <input id="registerConfirmPassword" type="password" placeholder="Re-enter your password" minlength="8" autocomplete="new-password" required>
                                    <button class="register-toggle-password" type="button" data-toggle-password="registerConfirmPassword" aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="register-actions register-actions--password">
                            <button class="register-submit register-submit--password" id="registerCompleteButton" type="submit">Create Account</button>
                        </div>
                        <button class="register-text-link register-text-link--password" type="button" data-back-to-otp>Back</button>
                    </section>
                </form>

                <p class="register-footer" id="registerFooter">Already have an account? <a href="login.php">Sign in</a></p>
            </div>
        </section>
    </main>

    <?= emarioh_render_vendor_runtime_assets(false); ?>
    <script src="assets/js/auth-api.js"></script>
    <script src="assets/js/pages/auth-registration-page.js"></script>
</body>
</html>

