<?php require __DIR__ . '/app/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Shared login UI for Emarioh Catering Services client portal and admin dashboard.">
    <title>Log In | Emarioh Catering Services</title>
    <?= emarioh_render_vendor_head_assets(); ?>
    <link rel="stylesheet" href="assets/css/auth.css?v=20260331f">
    <link rel="stylesheet" href="assets/css/login.css?v=20260418b">
</head>
<body class="auth-page auth-page--login" data-auth-guard="guest">
    <main class="login-shell">
        <section class="login-panel" aria-label="Login form">
            <div class="login-topbar">
                <a href="public-page.php" class="login-brand" aria-label="Back to Emarioh public page">
                    <span class="login-brand__mark"><img src="assets/images/logo.jpg" alt="Emarioh Catering Services logo"></span>
                    <span><span class="login-brand__name">EMARIOH</span><span class="login-brand__sub">Catering Services</span></span>
                </a>
                <a class="login-back" href="public-page.php">
                    <i class="bi bi-arrow-left"></i>
                    <span class="login-back__desktop">Back to website</span>
                    <span class="login-back__mobile">Back</span>
                </a>
            </div>
            <div class="login-content">
                <div class="login-heading">
                    <h1 id="loginPageTitle">Log In</h1>
                    <p id="loginPageSubtitle">Use your mobile number and password to access your account.</p>
                </div>
                <div class="login-card">
                    <p class="login-inline-note" id="loginIntroNote" hidden></p>
                    <p class="login-inline-note" id="adminSetupNotice" hidden>No admin account detected yet. Complete the first-time admin setup in <a href="registration.php?setup=admin">registration</a> before using the shared log in.</p>
                    <form class="login-form" id="loginForm" novalidate>
                        <div class="login-field">
                            <label for="loginIdentity">Mobile Number</label>
                            <div class="login-input-wrap">
                                <input id="loginIdentity" name="mobile" type="tel" inputmode="numeric" placeholder="0917 555 2481" autocomplete="tel" required>
                                <span class="login-input-icon" aria-hidden="true"><i class="bi bi-phone"></i></span>
                            </div>
                        </div>
                        <div class="login-field">
                            <label for="loginPassword">Password</label>
                            <div class="login-input-wrap">
                                <input id="loginPassword" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required>
                                <button class="login-toggle-password" type="button" data-toggle-password="loginPassword" aria-label="Show password"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="login-meta">
                            <label class="login-check" for="rememberMe"><input id="rememberMe" name="remember_me" type="checkbox"><span>Keep me signed in</span></label>
                            <a class="login-helper" href="forgot-password.php">Forgot password?</a>
                        </div>
                        <button class="login-submit" type="submit">Log In</button>
                    </form>
                    <p class="login-feedback" id="loginFeedback" hidden></p>
                    <a class="login-submit" id="adminSetupLink" href="registration.php?setup=admin" hidden>Open First-Time Setup</a>
                    <p class="login-footer" id="clientLoginFooter">Client account? <a href="registration.php">Register here</a>.</p>
                    <p class="login-footer" id="adminDetectedFooter" hidden>Admin account detected. <button class="login-text-link" type="button" id="adminSetupResetButton">Reset Admin</button></p>
                </div>
            </div>
        </section>
    </main>
    <?= emarioh_render_vendor_runtime_assets(false); ?>
    <script src="assets/js/auth-api.js"></script>
    <script src="assets/js/auth-login-page.js"></script>
</body>
</html>
