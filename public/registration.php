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
    <link rel="stylesheet" href="assets/css/pages/registration.css?v=20261011b">
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
                    <p class="register-subtitle" id="registerPageSubtitle">Register your client portal access in 3 easy steps.</p>
                </div>
                <div class="register-mode-banner is-warning" id="registerModeBanner" hidden></div>

                <div class="register-steps" aria-label="Registration progress">
                    <div class="register-step-chip is-active" data-step-indicator="1">
                        <span class="register-step-chip__count">1</span>
                        <strong>Account</strong>
                        <span>Details</span>
                    </div>
                    <div class="register-step-chip" data-step-indicator="2">
                        <span class="register-step-chip__count">2</span>
                        <strong>OTP</strong>
                        <span>Verify</span>
                    </div>
                    <div class="register-step-chip" data-step-indicator="3">
                        <span class="register-step-chip__count">3</span>
                        <strong>Password</strong>
                        <span>Secure</span>
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
                                <div class="register-input-wrap register-input-wrap--phone">
                                    <span class="register-phone-prefix" aria-hidden="true">
                                        <span class="register-phone-flag">🇵🇭</span>
                                        <span class="register-phone-code">+63</span>
                                    </span>
                                    <input id="registerPhone" name="mobile" type="tel" inputmode="numeric" placeholder="917 555 2481" maxlength="13" autocomplete="tel" required>
                                </div>
                            </div>
                        </div>

                        <label class="register-agree" for="agreeTerms">
                            <input id="agreeTerms" name="agree_terms" type="checkbox" required>
                            <span id="registerConsentCopy">I agree to the <button type="button" class="register-policy-link" data-open-policy="terms">Terms of Service</button> and <button type="button" class="register-policy-link" data-open-policy="privacy">Privacy Policy</button>.</span>
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
        <footer class="register-page-footer">
            <span>&copy; <?= date('Y') ?> Emarioh Catering Services</span>
            <span>Online Booking &amp; Reservation System</span>
        </footer>
    </main>

    <!-- Terms of Service & Privacy Policy Modal -->
    <div class="policy-modal-backdrop" id="policyModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="policyModalTitle">
        <div class="policy-modal">
            <div class="policy-modal__header">
                <div class="policy-segmented-tabs" role="tablist">
                    <button type="button" class="policy-segmented-tab is-active" data-policy-tab="terms" role="tab" aria-selected="true">
                        Terms of Service
                    </button>
                    <button type="button" class="policy-segmented-tab" data-policy-tab="privacy" role="tab" aria-selected="false">
                        Privacy Policy
                    </button>
                </div>
                <button type="button" class="policy-modal__close" data-close-policy aria-label="Close modal">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="policy-modal__body">
                <!-- Terms of Service Pane -->
                <div class="policy-modal__pane is-active" data-policy-pane="terms">
                    <div class="policy-header-lead">
                        <span class="policy-tag"><i class="bi bi-file-earmark-check"></i> Client Agreement</span>
                        <h2 class="policy-title" id="policyModalTitle">Terms of Service</h2>
                        <p class="policy-meta">Emarioh Catering Services • Last Updated: October 2026</p>
                    </div>

                    <div class="policy-content">
                        <h4>1. Acceptance of Terms</h4>
                        <p>By creating an account or accessing the Emarioh Catering Services client portal, you acknowledge that you have read, understood, and agreed to be bound by these Terms of Service and our Privacy Policy. If you do not agree, please refrain from registering or using this service.</p>

                        <h4>2. Account Registration and Security</h4>
                        <ul>
                            <li>You agree to provide accurate, current, and complete information, including your full legal name and an active Philippine mobile number (+63).</li>
                            <li>Your account is verified using a One-Time Password (OTP) sent to your registered mobile number.</li>
                            <li>You are solely responsible for maintaining the confidentiality of your account credentials and password. Any booking or activity made under your account shall be considered authorized by you.</li>
                        </ul>

                        <h4>3. Booking and Reservation Guidelines</h4>
                        <ul>
                            <li>Submitting a booking or reservation request through the portal does not automatically guarantee confirmation. All reservations are subject to date availability, logistics assessment, and management approval.</li>
                            <li>An official booking confirmation will only be issued once the reservation has been approved by Emarioh Catering Services and the required downpayment or deposit has been acknowledged.</li>
                        </ul>

                        <h4>4. Payments, Downpayments, and Billing</h4>
                        <ul>
                            <li>A non-refundable downpayment or reservation deposit is required to secure and block your chosen event date.</li>
                            <li>The remaining balance must be settled according to the payment schedule stated in your official booking contract or invoice prior to or on the event date.</li>
                            <li>Accepted payment methods and official receipts will be reflected through your client portal billing dashboard.</li>
                        </ul>

                        <h4>5. Rescheduling and Cancellation</h4>
                        <ul>
                            <li>Requests for date rescheduling must be submitted in writing or through your client portal within the prescribed advance notice period and are subject to calendar availability.</li>
                            <li>Cancellations made after the confirmation window may result in the forfeiture of downpayments or incur cancellation fees to cover incurred logistics and food preparation costs.</li>
                        </ul>

                        <h4>6. Client Responsibilities</h4>
                        <ul>
                            <li>The client is responsible for ensuring timely venue access, adequate space, and utility provisions (e.g., water and electrical outlets where applicable) for catering setup.</li>
                            <li>Final guest headcount, menu selections, and dietary requirements must be finalized and confirmed within the agreed cutoff timeframe prior to the event.</li>
                        </ul>

                        <h4>7. Force Majeure</h4>
                        <p>Emarioh Catering Services shall not be held liable for failure or delay in performance resulting from events beyond reasonable control, including but not limited to severe natural disasters, government restrictions, or extreme weather conditions. In such cases, both parties will make reasonable efforts to reschedule.</p>

                        <h4>8. Changes to Terms</h4>
                        <p>Emarioh Catering Services reserves the right to modify these terms at any time. Any updates will take effect upon posting to the client portal.</p>
                    </div>
                </div>

                <!-- Privacy Policy Pane -->
                <div class="policy-modal__pane" data-policy-pane="privacy" hidden>
                    <div class="policy-header-lead">
                        <span class="policy-tag"><i class="bi bi-shield-lock"></i> Republic Act No. 10173</span>
                        <h2 class="policy-title">Privacy Policy</h2>
                        <p class="policy-meta">Philippine Data Privacy Act of 2012 • Last Updated: October 2026</p>
                    </div>

                    <div class="policy-content">
                        <h4>1. Information We Collect</h4>
                        <p>We collect personal information necessary to facilitate your client account and manage your catering reservations:</p>
                        <ul>
                            <li><strong>Personal Identifiers:</strong> Full Name and Mobile Number (used for OTP SMS verification and primary contact).</li>
                            <li><strong>Event Details:</strong> Event dates, venue location/address, guest estimates, and catering package preferences.</li>
                            <li><strong>Billing Records:</strong> Payment receipts, transaction references, and statement summaries.</li>
                        </ul>

                        <h4>2. How We Use Your Information</h4>
                        <p>Your information is processed strictly for legitimate business purposes:</p>
                        <ul>
                            <li>To verify your identity and protect account security via SMS OTP authentication.</li>
                            <li>To process, prepare, schedule, and execute your catering reservations and event services.</li>
                            <li>To send transactional notifications, payment reminders, and booking updates.</li>
                            <li>To address customer inquiries and provide support.</li>
                        </ul>

                        <h4>3. Data Protection and Confidentiality</h4>
                        <ul>
                            <li>Emarioh Catering Services employs standard technical, administrative, and organizational safeguards to protect your personal information against unauthorized access, disclosure, alteration, or misuse.</li>
                            <li>We do <strong>NOT</strong> sell, rent, trade, or distribute your personal data to third-party marketing entities.</li>
                        </ul>

                        <h4>4. Data Retention</h4>
                        <p>Your account information and transaction records are retained only for as long as necessary to fulfill the purposes outlined in this policy, comply with accounting and legal reporting requirements, and resolve any disputes.</p>

                        <h4>5. Your Data Privacy Rights</h4>
                        <p>Under the Philippine Data Privacy Act of 2012, you are entitled to:</p>
                        <ul>
                            <li>Access and review the personal information we hold about you.</li>
                            <li>Request corrections to inaccurate, incomplete, or outdated data via your client portal.</li>
                            <li>Request deletion or suspension of your personal information, subject to applicable contractual and legal record-keeping obligations.</li>
                        </ul>

                        <h4>6. Inquiries and Contact</h4>
                        <p>If you have questions or concerns regarding our privacy practices or wish to exercise your data privacy rights, please reach out to our management through the contact channels available on our website.</p>
                    </div>
                </div>
            </div>

            <div class="policy-modal__footer">
                <button type="button" class="policy-modal__btn-close" data-close-policy>Close</button>
            </div>
        </div>
    </div>

    <?= emarioh_render_vendor_runtime_assets(false); ?>
    <script src="assets/js/auth-api.js"></script>
    <script src="assets/js/pages/auth-registration-page.js?v=20261011a"></script>
</body>
</html>

