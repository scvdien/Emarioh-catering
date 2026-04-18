<?php require __DIR__ . '/app/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Reset your Emarioh Catering Services account password using mobile OTP verification.">
    <title>Forgot Password | Emarioh Catering Services</title>
    <?= emarioh_render_vendor_head_assets(); ?>
    <link rel="stylesheet" href="assets/css/auth.css?v=20260331f">
    <link rel="stylesheet" href="assets/css/registration.css?v=20260418b">
</head>
<body class="auth-page auth-page--register" data-auth-guard="guest">
    <main class="register-shell">
        <section class="register-panel" aria-label="Forgot password form">
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
                <a class="register-back" href="login.php">
                    <i class="bi bi-arrow-left"></i>
                    <span class="register-back__desktop">Back to login</span>
                    <span class="register-back__mobile">Back</span>
                </a>
            </div>

            <div class="register-content">
                <div class="register-heading">
                    <h1>Forgot Password</h1>
                    <p>Reset your account password in 3 secure steps using the mobile number registered to your account.</p>
                </div>

                <div class="register-steps" aria-label="Password reset steps">
                    <div class="register-step-chip is-active" data-step-indicator="1">
                        <span class="register-step-chip__count">1</span>
                        <strong>Mobile</strong>
                        <span>Find</span>
                    </div>
                    <div class="register-step-chip" data-step-indicator="2">
                        <span class="register-step-chip__count">2</span>
                        <strong>OTP</strong>
                        <span>Verify</span>
                    </div>
                    <div class="register-step-chip" data-step-indicator="3">
                        <span class="register-step-chip__count">3</span>
                        <strong>Password</strong>
                        <span>Reset</span>
                    </div>
                </div>

                <form class="register-form" id="forgotPasswordForm" novalidate>
                    <section class="register-stage is-active" data-reset-step="1">
                        <p class="register-stage__copy">Enter the mobile number linked to your account. We will verify it with a 6-digit OTP before you create a new password.</p>

                        <div class="register-field">
                            <label for="forgotPhone">Mobile Number</label>
                            <div class="register-input-wrap">
                                <input id="forgotPhone" name="mobile" type="tel" inputmode="numeric" placeholder="0917 555 2481" autocomplete="tel" required>
                                <span class="register-input-icon" aria-hidden="true"><i class="bi bi-phone"></i></span>
                            </div>
                        </div>

                        <div class="register-actions">
                            <button class="register-submit" type="button" data-reset-send-otp id="forgotSendOtpButton">Send OTP</button>
                        </div>
                    </section>

                    <section class="register-stage" data-reset-step="2" hidden>
                        <p class="register-stage__copy">Enter the 6-digit code sent to <strong data-reset-target="otp-mobile">your mobile number</strong>.</p>

                        <div class="register-field">
                            <div class="register-input-wrap register-input-wrap--otp">
                                <input id="forgotOtp" type="text" inputmode="numeric" autocomplete="one-time-code" placeholder="Enter code" maxlength="6">
                            </div>
                        </div>

                        <p class="register-inline-note register-inline-note--info is-hidden" data-reset-feedback="otp" aria-live="polite"></p>

                        <div class="register-actions register-actions--otp">
                            <button class="register-submit register-submit--otp" type="button" data-reset-verify-otp>Verify OTP</button>
                            <div class="register-actions__links">
                                <button class="register-text-button" type="button" data-reset-resend>Resend</button>
                                <button class="register-text-button" type="button" data-reset-change-number>Edit Number</button>
                            </div>
                        </div>
                    </section>

                    <section class="register-stage" data-reset-step="3" hidden>
                        <p class="register-stage__copy"><strong data-reset-target="password-mobile">Your mobile number</strong> verified. Create your new password to continue.</p>

                        <div class="register-grid register-grid--password">
                            <div class="register-field">
                                <label for="forgotPassword">New Password</label>
                                <div class="register-input-wrap register-input-wrap--password">
                                    <input id="forgotPassword" name="password" type="password" placeholder="Create a strong password" minlength="8" autocomplete="new-password" required>
                                    <button class="register-toggle-password" type="button" data-toggle-password="forgotPassword" aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="register-field">
                                <label for="forgotConfirmPassword">Confirm Password</label>
                                <div class="register-input-wrap register-input-wrap--password">
                                    <input id="forgotConfirmPassword" type="password" placeholder="Re-enter your password" minlength="8" autocomplete="new-password" required>
                                    <button class="register-toggle-password" type="button" data-toggle-password="forgotConfirmPassword" aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="register-actions register-actions--password">
                            <button class="register-submit register-submit--password" id="forgotCompleteButton" type="submit">Update Password</button>
                        </div>
                        <button class="register-text-link register-text-link--password" type="button" data-reset-back-to-otp>Back</button>
                    </section>
                </form>

                <p class="register-footer">Remembered your password? <a href="login.php">Sign in</a>.</p>
            </div>
        </section>
    </main>

    <?= emarioh_render_vendor_runtime_assets(false); ?>
    <script src="assets/js/auth-api.js"></script>
    <script src="assets/js/auth-forgot-password-page.js"></script>
</body>
</html>
