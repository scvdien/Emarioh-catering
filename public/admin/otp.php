<?php require dirname(__DIR__, 2) . '/app/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin SMS OTP verification UI for Emarioh Catering Services.">
    <title>Admin OTP Verification | Emarioh Catering Services</title>
    <?= emarioh_render_vendor_head_assets(); ?>
    <link rel="stylesheet" href="assets/css/pages/login.css?v=20260403a">
    <link rel="stylesheet" href="assets/css/pages/admin-auth.css?v=20260408a">
</head>
<body class="auth-page auth-page--login admin-access-page">
    <main class="login-shell">
        <section class="login-panel login-panel--admin" aria-label="Admin OTP verification form">
            <div class="login-topbar">
                <a href="public-page.php" class="login-brand" aria-label="Back to Emarioh public page">
                    <span class="login-brand__mark">
                        <img src="assets/images/logo.jpg" alt="Emarioh Catering Services logo">
                    </span>
                    <span>
                        <span class="login-brand__name">EMARIOH</span>
                        <span class="login-brand__sub">Catering Services</span>
                    </span>
                </a>
                <a class="login-back" href="registration.php?setup=admin" data-admin-back-link><i class="bi bi-arrow-left"></i><span data-admin-back-label>Back to log in</span></a>
            </div>

            <div class="login-content">
                <div class="login-heading">
                    <span class="admin-auth-badge">SMS Verification</span>
                    <h1 data-admin-otp-title>Verify Admin Login</h1>
                    <p data-admin-otp-subtitle>Enter the 6-digit OTP sent to the registered admin mobile number.</p>
                </div>

                <div class="login-card">
                    <div class="admin-auth-note">
                        <strong data-admin-otp-note-title>Demo SMS OTP screen.</strong>
                        <span data-admin-otp-note-copy>The verification form is interactive, but the code is not sent by a live SMS gateway yet.</span>
                    </div>

                    <p class="admin-auth-otp-copy">Code sent to <strong data-admin-mobile-target>your admin mobile number</strong>.</p>

                    <form class="login-form" id="adminOtpForm" novalidate>
                        <div class="admin-auth-otp-row" role="group" aria-label="Enter 6 digit OTP">
                            <input class="admin-auth-otp-input" type="text" inputmode="numeric" maxlength="1" autocomplete="one-time-code" data-admin-otp-input aria-label="OTP digit 1">
                            <input class="admin-auth-otp-input" type="text" inputmode="numeric" maxlength="1" data-admin-otp-input aria-label="OTP digit 2">
                            <input class="admin-auth-otp-input" type="text" inputmode="numeric" maxlength="1" data-admin-otp-input aria-label="OTP digit 3">
                            <input class="admin-auth-otp-input" type="text" inputmode="numeric" maxlength="1" data-admin-otp-input aria-label="OTP digit 4">
                            <input class="admin-auth-otp-input" type="text" inputmode="numeric" maxlength="1" data-admin-otp-input aria-label="OTP digit 5">
                            <input class="admin-auth-otp-input" type="text" inputmode="numeric" maxlength="1" data-admin-otp-input aria-label="OTP digit 6">
                        </div>

                        <p class="admin-auth-feedback" data-admin-otp-feedback hidden></p>

                        <button class="login-submit" type="submit" data-admin-otp-submit>Open Admin Dashboard</button>
                    </form>

                    <div class="admin-auth-link-row">
                        <button class="admin-auth-text-link" type="button" data-admin-resend>Resend OTP</button>
                        <a class="admin-auth-text-link" href="registration.php?setup=admin" data-admin-edit-link>Edit Number</a>
                    </div>

                    <p class="login-footer" data-admin-otp-footer>After verification, this prototype will open the admin dashboard home screen.</p>
                </div>
            </div>
        </section>
    </main>

    <?= emarioh_render_vendor_runtime_assets(false); ?>
    <script src="assets/js/pages/admin-auth-ui.js?v=20260408a"></script>
</body>
</html>
