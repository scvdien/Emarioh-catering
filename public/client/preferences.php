<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';

$db = emarioh_db();
$currentUser = emarioh_require_page_role('client');
$clientProfile = emarioh_find_client_profile($db, (int) $currentUser['id']);

$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$clientFullName = (string) ($currentUser['full_name'] ?? '');
$clientMobileRaw = (string) ($currentUser['mobile'] ?? '');
$clientMobile = emarioh_format_mobile($clientMobileRaw);
$clientAlternateContact = trim((string) ($clientProfile['alternate_contact'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emarioh Catering Services Client Account Settings</title>
    <?= emarioh_render_vendor_head_assets(); ?>
    <link rel="stylesheet" href="assets/css/index.css?v=20260410f">
    <link rel="stylesheet" href="assets/css/pages/admin-settings.css?v=20260418w">
    <link rel="stylesheet" href="assets/css/pages/client-preferences.css?v=20260418i">
    <link rel="stylesheet" href="assets/css/client-sidebar-parity.css?v=20260418e">
</head>
<body class="dashboard-page admin-settings-page client-dashboard-page client-settings-page" data-auth-guard="client">
    <div class="dashboard-shell container-fluid">
        <div class="dashboard-frame">
            <aside class="dashboard-sidebar offcanvas-xl offcanvas-start border-0" tabindex="-1" id="dashboardSidebar" aria-labelledby="dashboardSidebarLabel">
                <div class="offcanvas-header sidebar-mobile-header d-xl-none">
                    <div class="sidebar-mobile-brand">
                        <span class="sidebar-brand__frame sidebar-brand__frame--small">
                            <img src="assets/images/logo.jpg" alt="Emarioh Catering Services" class="sidebar-brand__logo">
                        </span>
                        <div class="sidebar-brand__copy">
                            <span class="sidebar-brand__name">Emarioh</span>
                            <span class="sidebar-brand__sub">Client Portal</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body p-0">
                    <div class="sidebar-inner d-flex flex-column h-100">
                        <div class="sidebar-brand">
                            <a href="client-dashboard.php" class="sidebar-brand__link text-decoration-none" id="dashboardSidebarLabel" aria-label="Emarioh Catering Services Client Portal">
                                <span class="sidebar-brand__frame">
                                    <img src="assets/images/logo.jpg" alt="Emarioh Catering Services" class="sidebar-brand__logo">
                                </span>
                                <span class="sidebar-brand__copy">
                                    <span class="sidebar-brand__name">Emarioh</span>
                                    <span class="sidebar-brand__sub">Client Portal</span>
                                </span>
                            </a>
                        </div>
                        <div class="sidebar-divider" aria-hidden="true"></div>
                        <nav class="dashboard-nav nav flex-column" aria-label="Client portal navigation">
                            <a class="nav-link" href="client-dashboard.php"><span class="nav-link__icon"><i class="bi bi-grid-1x2-fill"></i></span><span>Dashboard</span></a>
                            <a class="nav-link" href="client-bookings.php"><span class="nav-link__icon"><i class="bi bi-calendar2-plus"></i></span><span>Book Event</span></a>
                            <a class="nav-link" href="client-my-bookings.php"><span class="nav-link__icon"><i class="bi bi-calendar2-check"></i></span><span>My Bookings</span></a>
                            <a class="nav-link" href="client-billing.php"><span class="nav-link__icon"><i class="bi bi-receipt-cutoff"></i></span><span>Billing</span></a>
                            <a class="nav-link active" href="client-preferences.php" aria-current="page"><span class="nav-link__icon"><i class="bi bi-gear"></i></span><span>Account Settings</span></a>
                        </nav>
                        <div class="sidebar-footer mt-auto">
                            <a class="sidebar-logout" href="logout.php" data-logout-link><span class="sidebar-logout__icon"><i class="bi bi-box-arrow-right"></i></span><span class="sidebar-logout__label">Log out</span></a>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="dashboard-main">
                <header class="dashboard-topbar">
                    <div class="topbar-leading">
                        <button class="btn mobile-menu-button d-xl-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#dashboardSidebar" aria-controls="dashboardSidebar" aria-label="Open navigation">
                            <i class="bi bi-list"></i>
                        </button>
                        <div class="topbar-copy">
                            <h1 class="topbar-copy__title">Account Settings</h1>
                        </div>
                    </div>
                </header>

                <main class="dashboard-content settings-dashboard-content">
                    <section class="surface-card settings-profile-hub d-xl-none" data-client-settings-hub aria-labelledby="clientSettingsHubTitle">
                        <div class="settings-profile-hub__header">
                            <span class="settings-profile-hub__icon" aria-hidden="true"><i class="bi bi-person-circle"></i></span>
                            <div class="settings-profile-hub__copy">
                                <p class="settings-profile-hub__eyebrow">Client Account</p>
                                <h2 id="clientSettingsHubTitle"><?= $escape($clientFullName !== '' ? $clientFullName : 'Client Profile') ?></h2>
                                <p class="settings-profile-hub__summary">Review your profile details, update your password, and manage your account from one place.</p>
                            </div>
                        </div>
                        <div class="settings-profile-hub__grid">
                            <a class="settings-profile-shortcut" href="#clientAccountOverview" data-client-settings-open data-target-section="profile-details">
                                <span class="settings-profile-shortcut__content">
                                    <span class="settings-profile-shortcut__icon" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
                                    <span class="settings-profile-shortcut__copy">
                                        <strong>Profile Details</strong>
                                        <span>Update your name, mobile number, and alternate contact.</span>
                                    </span>
                                </span>
                                <span class="settings-profile-shortcut__chevron" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
                            </a>
                            <a class="settings-profile-shortcut" href="#clientSecurityPanel" data-client-settings-open data-target-section="password-security">
                                <span class="settings-profile-shortcut__content">
                                    <span class="settings-profile-shortcut__icon" aria-hidden="true"><i class="bi bi-shield-lock"></i></span>
                                    <span class="settings-profile-shortcut__copy">
                                        <strong>Password &amp; Security</strong>
                                        <span>Change your password without affecting bookings or billing.</span>
                                    </span>
                                </span>
                                <span class="settings-profile-shortcut__chevron" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
                            </a>
                        </div>
                        <a class="settings-profile-logout" href="logout.php" data-logout-link>
                            <span class="settings-profile-logout__content">
                                <span class="settings-profile-shortcut__icon" aria-hidden="true"><i class="bi bi-box-arrow-right"></i></span>
                                <span class="settings-profile-shortcut__copy">
                                    <strong>Log Out</strong>
                                    <span>Sign out of your client account.</span>
                                </span>
                            </span>
                            <span class="settings-profile-shortcut__chevron" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
                        </a>
                    </section>

                    <section class="surface-card settings-panel" data-client-settings-panel data-mobile-state="collapsed" aria-hidden="true">
                        <div class="settings-panel__mobile-actions d-xl-none">
                            <button class="settings-panel__back" type="button" data-client-settings-back>
                                <i class="bi bi-chevron-left" aria-hidden="true"></i>
                                <span>Back to account</span>
                            </button>
                        </div>
                        <nav class="settings-tabs settings-filter-bar settings-filter-bar--client" aria-label="Account quick filters">
                            <a class="settings-tab is-active" href="#clientAccountOverview" data-client-settings-tab data-target-section="profile-details" aria-current="page">Profile Details</a>
                            <a class="settings-tab" href="#clientSecurityPanel" data-client-settings-tab data-target-section="password-security">Password &amp; Security</a>
                        </nav>
                        <section class="settings-section" id="clientAccountOverview" data-settings-section="profile-details">
                            <div class="settings-section__intro">
                                <div class="settings-section__intro-copy">
                                    <p class="settings-section__eyebrow">Client Portal</p>
                                    <h2>Manage Your Account</h2>
                                    <p>Keep your main contact details updated for booking and billing coordination.</p>
                                </div>
                            </div>

                            <div class="settings-account-shell">
                                <article class="settings-block settings-block--account">
                                    <form class="settings-account-form" id="clientAccountForm">
                                        <div class="settings-account-layout">
                                            <section class="settings-account-section" aria-labelledby="clientProfileTitle">
                                                <div class="settings-account-section__header">
                                                    <span class="settings-account-section__icon" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
                                                    <div class="settings-account-section__copy">
                                                        <h4 id="clientProfileTitle">Profile Details</h4>
                                                    </div>
                                                </div>
                                                <div class="settings-field-grid settings-field-grid--account">
                                                    <label class="settings-field">
                                                        <span class="settings-field__label">Client Name</span>
                                                        <input class="settings-input" type="text" value="<?= $escape($clientFullName) ?>" autocomplete="name" data-auth-input="full_name">
                                                    </label>
                                                    <div class="settings-field">
                                                        <span class="settings-field__label">Mobile Number</span>
                                                        <input
                                                            class="settings-input is-readonly"
                                                            type="tel"
                                                            id="clientMobileInput"
                                                            value="<?= $escape($clientMobile) ?>"
                                                            maxlength="20"
                                                            inputmode="numeric"
                                                            autocomplete="tel"
                                                            data-auth-input="mobile"
                                                            data-original-mobile="<?= $escape($clientMobileRaw) ?>"
                                                            readonly
                                                            required
                                                        >
                                                        <div class="settings-mobile-field-footer">
                                                            <span class="settings-field__hint">Used for sign-in OTP.</span>
                                                            <button class="settings-mobile-change-btn" type="button" id="clientMobileChangeButton">Change Mobile Number</button>
                                                        </div>
                                                        <p class="settings-mobile-status-note is-hidden" id="clientMobileStatusNote"></p>
                                                    </div>
                                                    <label class="settings-field settings-field--full">
                                                        <span class="settings-field__label">Alternate Contact</span>
                                                        <input class="settings-input" id="alternateContactInput" type="text" value="<?= $escape($clientAlternateContact) ?>" placeholder="Name and mobile number">
                                                    </label>
                                                </div>
                                            </section>
                                        </div>

                                        <div class="settings-account-actions">
                                            <p class="settings-account-actions__note" id="clientAccountFeedback" hidden aria-live="polite"><i class="bi bi-info-circle"></i><span></span></p>
                                            <button class="action-btn action-btn--primary" id="clientAccountSaveButton" type="submit"><i class="bi bi-check2-circle"></i><span>Save Account Changes</span></button>
                                        </div>
                                    </form>
                                </article>
                            </div>
                        </section>

                        <section class="settings-section" id="clientSecurityPanel" data-settings-section="password-security" hidden>
                            <div class="settings-section__intro settings-section__intro--security">
                                <div class="settings-section__intro-copy">
                                    <p class="settings-section__eyebrow">Client Portal</p>
                                    <h2>Password &amp; Security</h2>
                                    <p>Update your login password separately without affecting your bookings, billing history, or saved profile details.</p>
                                </div>
                            </div>

                            <div class="settings-account-shell settings-account-shell--security">
                                <article class="settings-block settings-block--account">
                                    <form class="settings-account-form" id="clientSecurityForm">
                                        <div class="settings-account-layout">
                                            <section class="settings-account-section" aria-labelledby="clientSecurityTitle">
                                                <div class="settings-account-section__header">
                                                    <span class="settings-account-section__icon" aria-hidden="true"><i class="bi bi-shield-lock"></i></span>
                                                    <div class="settings-account-section__copy">
                                                        <h4 id="clientSecurityTitle">Update Password</h4>
                                                    </div>
                                                </div>
                                                <div class="settings-field-grid settings-field-grid--account settings-field-grid--security">
                                                    <label class="settings-field settings-field--full">
                                                        <span class="settings-field__label">Current Password</span>
                                                        <input
                                                            class="settings-input"
                                                            type="password"
                                                            id="clientCurrentPasswordInput"
                                                            placeholder="Enter current password"
                                                            autocomplete="current-password"
                                                        >
                                                    </label>
                                                    <label class="settings-field">
                                                        <span class="settings-field__label">New Password</span>
                                                        <input
                                                            class="settings-input"
                                                            type="password"
                                                            id="clientNewPasswordInput"
                                                            placeholder="Enter new password"
                                                            autocomplete="new-password"
                                                        >
                                                    </label>
                                                    <label class="settings-field">
                                                        <span class="settings-field__label">Confirm Password</span>
                                                        <input
                                                            class="settings-input"
                                                            type="password"
                                                            id="clientConfirmPasswordInput"
                                                            placeholder="Confirm new password"
                                                            autocomplete="new-password"
                                                        >
                                                    </label>
                                                </div>
                                            </section>
                                        </div>

                                        <div class="settings-account-actions settings-account-actions--end">
                                            <p class="settings-account-actions__note" id="clientSecurityFeedback" aria-live="polite"><i class="bi bi-info-circle"></i><span>Review your changes before saving.</span></p>
                                            <button class="action-btn action-btn--primary" id="clientPasswordUpdateButton" type="submit"><i class="bi bi-shield-check"></i><span>Update Password</span></button>
                                        </div>
                                    </form>
                                </article>
                            </div>
                        </section>
                    </section>
                </main>

                <?= emarioh_render_client_mobile_nav(basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'client-dashboard.php'))) ?>
            </div>
        </div>
    </div>

    <div class="modal fade booking-modal settings-mobile-modal" id="clientMobileChangeModal" tabindex="-1" aria-labelledby="clientMobileChangeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content booking-modal__content">
                <div class="modal-header booking-modal__header">
                    <div>
                        <h2 class="booking-modal__title" id="clientMobileChangeModalLabel">Change Mobile Number</h2>
                        <p class="settings-mobile-modal__subtitle">Enter a new number and verify the OTP.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body booking-modal__body">
                    <div class="settings-mobile-modal__content">
                        <div class="settings-mobile-modal__intro">
                            <div class="settings-mobile-modal__current">
                                <span class="settings-mobile-modal__current-label">Current mobile number</span>
                                <strong id="clientMobileCurrentDisplay"><?= $escape($clientMobile) ?></strong>
                            </div>
                        </div>

                        <label class="settings-field settings-field--full">
                            <span class="settings-field__label">New Mobile Number</span>
                            <input
                                class="settings-input"
                                type="tel"
                                id="clientMobileDraftInput"
                                maxlength="20"
                                inputmode="numeric"
                                autocomplete="tel"
                                placeholder="Enter new mobile number"
                            >
                            <span class="settings-field__hint" id="clientMobileDraftHelp">Example: 0917 123 4567</span>
                        </label>

                        <p class="settings-mobile-modal__status is-info" id="clientMobileDraftStatus">Enter a new number.</p>

                        <div class="settings-mobile-otp" id="clientMobileOtpPanel">
                            <div class="settings-mobile-otp__header">
                                <strong>OTP Verification</strong>
                                <span>Send OTP to continue.</span>
                            </div>
                            <div class="settings-mobile-otp__actions">
                                <button class="action-btn action-btn--soft" type="button" id="clientMobileOtpSendButton">Send OTP</button>
                                <button class="action-btn action-btn--ghost" type="button" id="clientMobileOtpResendButton" hidden>Resend</button>
                            </div>
                            <div class="settings-mobile-otp__entry" id="clientMobileOtpEntry" hidden>
                                <div class="settings-mobile-otp__row">
                                    <input
                                        class="settings-input settings-input--otp"
                                        type="text"
                                        id="clientMobileOtpInput"
                                        inputmode="numeric"
                                        maxlength="6"
                                        placeholder="Enter 6-digit OTP"
                                        autocomplete="one-time-code"
                                    >
                                    <button class="action-btn action-btn--primary" type="button" id="clientMobileOtpVerifyButton">Verify</button>
                                </div>
                                <span class="settings-field__hint">Sent to <strong id="clientMobileOtpTarget">your new mobile number</strong>.</span>
                            </div>
                            <p class="settings-mobile-otp__feedback is-hidden" id="clientMobileOtpFeedback"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer booking-modal__footer">
                    <button class="action-btn action-btn--ghost" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="action-btn action-btn--primary" type="button" id="clientMobileApplyButton" disabled>Use &amp; Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade booking-modal gallery-delete-modal" id="clientPasswordConfirmModal" tabindex="-1" aria-labelledby="clientPasswordConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content booking-modal__content">
                <div class="modal-header booking-modal__header">
                    <div>
                        <h2 class="booking-modal__title" id="clientPasswordConfirmModalLabel">Update Password</h2>
                        <p class="gallery-delete-modal__subtitle" id="clientPasswordConfirmModalText">Save your new password for this account?</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body booking-modal__body"></div>
                <div class="modal-footer booking-modal__footer">
                    <button class="action-btn action-btn--ghost" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="action-btn action-btn--primary" type="button" id="clientPasswordConfirmButton">Yes, Update Password</button>
                </div>
            </div>
        </div>
    </div>

    <?= emarioh_render_vendor_runtime_assets(true); ?>
    <script src="assets/js/auth-api.js"></script>
    <script src="assets/js/logout-confirmation.js?v=20260418a"></script>
    <script src="assets/js/pages/client-dashboard.js"></script>
    <script>
        const settingsTabs = document.querySelectorAll("[data-client-settings-tab]");
        const settingsSections = document.querySelectorAll("[data-settings-section]");
        const clientAccountForm = document.getElementById("clientAccountForm");
        const clientAccountSaveButton = document.getElementById("clientAccountSaveButton");
        const fullNameInput = clientAccountForm?.querySelector('[data-auth-input="full_name"]');
        const clientMobileInput = document.getElementById("clientMobileInput");
        const alternateContactInput = document.getElementById("alternateContactInput");
        const clientSecurityForm = document.getElementById("clientSecurityForm");
        const clientCurrentPasswordInput = document.getElementById("clientCurrentPasswordInput");
        const clientNewPasswordInput = document.getElementById("clientNewPasswordInput");
        const clientConfirmPasswordInput = document.getElementById("clientConfirmPasswordInput");
        const clientSecurityFeedback = document.getElementById("clientSecurityFeedback");
        const clientPasswordUpdateButton = document.getElementById("clientPasswordUpdateButton");
        const clientPasswordConfirmModalElement = document.getElementById("clientPasswordConfirmModal");
        const clientPasswordConfirmText = document.getElementById("clientPasswordConfirmModalText");
        const clientPasswordConfirmButton = document.getElementById("clientPasswordConfirmButton");
        const accountNote = document.querySelector("#clientAccountForm .settings-account-actions__note");
        const accountNoteIcon = accountNote?.querySelector("i");
        const accountNoteText = accountNote?.querySelector("span");
        const clientMobileChangeButton = document.getElementById("clientMobileChangeButton");
        const clientMobileStatusNote = document.getElementById("clientMobileStatusNote");
        const clientMobileChangeModalElement = document.getElementById("clientMobileChangeModal");
        const clientMobileCurrentDisplay = document.getElementById("clientMobileCurrentDisplay");
        const clientMobileDraftInput = document.getElementById("clientMobileDraftInput");
        const clientMobileDraftHelp = document.getElementById("clientMobileDraftHelp");
        const clientMobileDraftStatus = document.getElementById("clientMobileDraftStatus");
        const clientMobileOtpPanel = document.getElementById("clientMobileOtpPanel");
        const clientMobileOtpEntry = document.getElementById("clientMobileOtpEntry");
        const clientMobileOtpInput = document.getElementById("clientMobileOtpInput");
        const clientMobileOtpSendButton = document.getElementById("clientMobileOtpSendButton");
        const clientMobileOtpResendButton = document.getElementById("clientMobileOtpResendButton");
        const clientMobileOtpVerifyButton = document.getElementById("clientMobileOtpVerifyButton");
        const clientMobileOtpTarget = document.getElementById("clientMobileOtpTarget");
        const clientMobileOtpFeedback = document.getElementById("clientMobileOtpFeedback");
        const clientMobileApplyButton = document.getElementById("clientMobileApplyButton");
        const clientMobileChangeModal = clientMobileChangeModalElement && window.bootstrap?.Modal
            ? window.bootstrap.Modal.getOrCreateInstance(clientMobileChangeModalElement)
            : null;
        const clientPasswordConfirmModal = clientPasswordConfirmModalElement && window.bootstrap?.Modal
            ? window.bootstrap.Modal.getOrCreateInstance(clientPasswordConfirmModalElement)
            : null;
        const settingsPanel = document.querySelector(".settings-panel");
        const settingsDashboardContent = document.querySelector(".settings-dashboard-content");
        const clientSettingsPanel = document.querySelector("[data-client-settings-panel]");
        const clientSettingsHub = document.querySelector("[data-client-settings-hub]");
        const clientSettingsOpenLinks = Array.from(document.querySelectorAll("[data-client-settings-open]"));
        const clientSettingsBackButtons = Array.from(document.querySelectorAll("[data-client-settings-back]"));
        const mobileSettingsMedia = window.matchMedia("(max-width: 1199.98px)");
        let clientMobileOtpCountdown = 0;
        let clientMobileOtpTimerId = null;
        let clientMobileOtpRequestedMobile = "";
        let clientMobileOtpVerifiedMobile = "";
        let clientMobileOtpMaskedTarget = "";
        let isClientPasswordSubmitting = false;

        const getDigits = (value) => String(value || "").replace(/\D/g, "");
        const normalizeMobileValue = (value) => {
            const digits = getDigits(value);

            if (digits.length === 10 && digits.startsWith("9")) {
                return `63${digits}`;
            }

            if (digits.length === 11 && digits.startsWith("0")) {
                return `63${digits.slice(1)}`;
            }

            return digits;
        };
        const isValidNormalizedMobile = (value) => /^639\d{9}$/.test(String(value || ""));
        const formatMobileInputValue = (value) => {
            const digits = getDigits(value);

            if (!digits) {
                return "";
            }

            if (digits.startsWith("63")) {
                const trimmed = digits.slice(0, 12);
                return [
                    trimmed.slice(0, 2),
                    trimmed.slice(2, 5),
                    trimmed.slice(5, 8),
                    trimmed.slice(8, 12)
                ].filter(Boolean).join(" ");
            }

            if (digits.startsWith("9")) {
                const trimmed = digits.slice(0, 10);
                return [
                    trimmed.slice(0, 3),
                    trimmed.slice(3, 6),
                    trimmed.slice(6, 10)
                ].filter(Boolean).join(" ");
            }

            const trimmed = digits.slice(0, 11);
            return [
                trimmed.slice(0, 4),
                trimmed.slice(4, 7),
                trimmed.slice(7, 11)
            ].filter(Boolean).join(" ");
        };
        const formatMobileForDisplay = (value) => {
            const normalized = normalizeMobileValue(value);

            if (isValidNormalizedMobile(normalized)) {
                const localMobile = `0${normalized.slice(2)}`;
                return `${localMobile.slice(0, 4)} ${localMobile.slice(4, 7)} ${localMobile.slice(7)}`;
            }

            return formatMobileInputValue(value);
        };
        const maskMobileValue = (value) => {
            const normalized = normalizeMobileValue(value);

            if (isValidNormalizedMobile(normalized)) {
                const localMobile = `0${normalized.slice(2)}`;
                return `${localMobile.slice(0, 4)} *** ${localMobile.slice(-4)}`;
            }

            const digits = getDigits(value);

            if (!digits) {
                return "your new mobile number";
            }

            if (digits.length <= 4) {
                return digits;
            }

            return `${digits.slice(0, 4)} *** ${digits.slice(-4)}`;
        };
        const getClientOriginalMobile = () => normalizeMobileValue(clientMobileInput?.dataset.originalMobile || "");
        const getClientCurrentMobile = () => normalizeMobileValue(clientMobileInput?.value || "");
        const getClientDraftMobile = () => normalizeMobileValue(clientMobileDraftInput?.value || "");
        const isClientMobileChanged = () => {
            const currentMobile = getClientCurrentMobile();
            return currentMobile !== "" && currentMobile !== getClientOriginalMobile();
        };
        const setButtonsBusy = (buttons, isBusy, busyLabel) => {
            buttons.forEach((button) => {
                if (!button) {
                    return;
                }

                if (!button.dataset.defaultLabel) {
                    button.dataset.defaultLabel = button.textContent;
                }

                button.disabled = isBusy;
                button.textContent = isBusy ? busyLabel : button.dataset.defaultLabel;
            });
        };
        const setIconButtonBusy = (button, isBusy, busyLabel) => {
            if (!button) {
                return;
            }

            const label = button.querySelector("span");

            if (label && !button.dataset.defaultLabel) {
                button.dataset.defaultLabel = label.textContent;
            }

            button.disabled = isBusy;

            if (label) {
                label.textContent = isBusy ? busyLabel : (button.dataset.defaultLabel || label.textContent);
            }
        };

        const setAccountFeedback = (message, state) => {
            if (!accountNote || !accountNoteText || !accountNoteIcon) {
                return;
            }

            accountNote.hidden = !message;
            accountNote.classList.remove("is-success", "is-danger");
            accountNoteIcon.className = "bi";

            if (state === "success") {
                accountNote.classList.add("is-success");
                accountNoteIcon.classList.add("bi-check-circle");
            } else if (state === "danger") {
                accountNote.classList.add("is-danger");
                accountNoteIcon.classList.add("bi-exclamation-circle");
            } else {
                accountNoteIcon.classList.add("bi-info-circle");
            }

            accountNoteText.textContent = message;
        };

        const setSecurityFeedback = (message, state) => {
            const feedbackIcon = clientSecurityFeedback?.querySelector("i");
            const feedbackText = clientSecurityFeedback?.querySelector("span");
            const defaultMessage = "Review your changes before saving.";

            if (!clientSecurityFeedback || !feedbackIcon || !feedbackText) {
                return;
            }

            clientSecurityFeedback.classList.remove("is-success", "is-danger");
            feedbackIcon.className = "bi";

            if (!message) {
                feedbackIcon.classList.add("bi-info-circle");
                feedbackText.textContent = defaultMessage;
                return;
            }

            if (state === "success") {
                clientSecurityFeedback.classList.add("is-success");
                feedbackIcon.classList.add("bi-check-circle");
            } else if (state === "danger") {
                clientSecurityFeedback.classList.add("is-danger");
                feedbackIcon.classList.add("bi-exclamation-circle");
            } else {
                feedbackIcon.classList.add("bi-info-circle");
            }

            feedbackText.textContent = message;
        };

        const clearClientPasswordFieldValidity = () => {
            return;
        };

        const getClientPasswordPayload = () => ({
            current_password: clientCurrentPasswordInput?.value || "",
            new_password: clientNewPasswordInput?.value || "",
            confirm_password: clientConfirmPasswordInput?.value || ""
        });

        const validateClientPasswordForm = (showFeedback = true) => {
            clearClientPasswordFieldValidity();

            const payload = getClientPasswordPayload();
            let invalidField = null;
            let message = "";

            if (payload.current_password === "") {
                invalidField = clientCurrentPasswordInput;
                message = "Enter your current password.";
            } else if (payload.new_password === "") {
                invalidField = clientNewPasswordInput;
                message = "Enter a new password.";
            } else if (payload.new_password.length < 8) {
                invalidField = clientNewPasswordInput;
                message = "Use at least 8 characters for the new password.";
            } else if (payload.confirm_password === "") {
                invalidField = clientConfirmPasswordInput;
                message = "Confirm your new password.";
            } else if (payload.new_password !== payload.confirm_password) {
                invalidField = clientConfirmPasswordInput;
                message = "New password and confirmation do not match.";
            }

            if (!invalidField || !message) {
                return payload;
            }

            if (showFeedback) {
                setSecurityFeedback(message, "danger");
            }

            invalidField.focus();
            return null;
        };

        const syncClientPasswordConfirmContent = () => {
            const payload = validateClientPasswordForm(false);
            const isReady = payload !== null;

            if (clientPasswordConfirmText) {
                clientPasswordConfirmText.textContent = isReady
                    ? "Save your new password for this account?"
                    : "Complete the password fields first before continuing.";
            }

            if (clientPasswordConfirmButton && !isClientPasswordSubmitting) {
                clientPasswordConfirmButton.disabled = !isReady;
                clientPasswordConfirmButton.textContent = "Yes, Update Password";
            }

            return payload;
        };

        const focusClientPasswordFieldForError = (message) => {
            const text = String(message || "").toLowerCase();

            if (text.includes("current password")) {
                clientCurrentPasswordInput?.focus();
                return;
            }

            if (text.includes("confirm")) {
                clientConfirmPasswordInput?.focus();
                return;
            }

            clientNewPasswordInput?.focus();
        };

        const submitClientPasswordUpdate = async () => {
            if (!window.EmariohAuth?.post || isClientPasswordSubmitting) {
                return;
            }

            const payload = validateClientPasswordForm(true);

            if (!payload) {
                return;
            }

            isClientPasswordSubmitting = true;
            setIconButtonBusy(clientPasswordUpdateButton, true, "Saving...");
            setButtonsBusy([clientPasswordConfirmButton], true, "Saving...");

            try {
                const response = await window.EmariohAuth.post("update-client-password.php", payload);

                clientPasswordConfirmModal?.hide();
                clientSecurityForm?.reset();
                clearClientPasswordFieldValidity();
                setSecurityFeedback(response?.message || "Password updated successfully.", "success");
            } catch (error) {
                clientPasswordConfirmModal?.hide();
                setSecurityFeedback(error?.message || "We could not update your password right now. Please try again.", "danger");
                focusClientPasswordFieldForError(error?.message || "");
            } finally {
                isClientPasswordSubmitting = false;
                setIconButtonBusy(clientPasswordUpdateButton, false, "Update Password");
                setButtonsBusy([clientPasswordConfirmButton], false);
                syncClientPasswordConfirmContent();
            }
        };

        const openClientPasswordConfirm = () => {
            const payload = validateClientPasswordForm(true);

            if (!payload) {
                return;
            }

            setSecurityFeedback("", "");
            syncClientPasswordConfirmContent();

            if (!clientPasswordConfirmModal) {
                submitClientPasswordUpdate();
                return;
            }

            clientPasswordConfirmModal.show();
        };

        const routePanelAlert = (message, state = "danger") => {
            const text = String(message || "").trim();

            if (!text) {
                return;
            }

            if (/password/i.test(text) || !document.getElementById("clientSecurityPanel")?.hidden) {
                setSecurityFeedback(text, state);
                return;
            }

            setAccountFeedback(text, state);
        };

        const consumeSettingsPanelAlerts = () => {
            if (!settingsPanel) {
                return;
            }

            settingsPanel.querySelectorAll(".alert").forEach((alertElement) => {
                const message = alertElement.textContent?.trim() || "";
                const state = alertElement.classList.contains("alert-success")
                    ? "success"
                    : alertElement.classList.contains("alert-warning")
                        ? "default"
                        : "danger";

                routePanelAlert(message, state);
                alertElement.remove();
            });
        };

        const syncClientMobileCurrentDisplay = () => {
            if (!clientMobileCurrentDisplay) {
                return;
            }

            const currentLabel = formatMobileForDisplay(clientMobileInput?.value || getClientOriginalMobile());
            clientMobileCurrentDisplay.textContent = currentLabel || "No mobile number on file";
        };

        const setClientMobileStatus = (type = "", message = "") => {
            if (!clientMobileStatusNote) {
                return;
            }

            clientMobileStatusNote.className = "settings-mobile-status-note is-hidden";
            clientMobileStatusNote.textContent = "";

            if (!message) {
                return;
            }

            clientMobileStatusNote.classList.remove("is-hidden");
            clientMobileStatusNote.classList.add(`is-${type || "info"}`);
            clientMobileStatusNote.textContent = message;
        };

        const syncClientMobileFieldState = () => {
            if (!clientMobileInput) {
                return;
            }

            clientMobileInput.readOnly = true;
            clientMobileInput.classList.add("is-readonly");
            clientMobileInput.value = formatMobileForDisplay(clientMobileInput.value || getClientOriginalMobile());
            syncClientMobileCurrentDisplay();

            if (!isClientMobileChanged()) {
                setClientMobileStatus("", "");
                return;
            }

            if (getClientCurrentMobile() === clientMobileOtpVerifiedMobile) {
                setClientMobileStatus("success", "New mobile number verified. Save changes to apply it.");
                return;
            }

            setClientMobileStatus("warning", "This mobile number still needs OTP verification.");
        };

        const setClientMobileOtpFeedback = (type = "", message = "") => {
            if (!clientMobileOtpFeedback) {
                return;
            }

            clientMobileOtpFeedback.className = "settings-mobile-otp__feedback is-hidden";
            clientMobileOtpFeedback.textContent = "";

            if (!message) {
                return;
            }

            clientMobileOtpFeedback.classList.remove("is-hidden");
            clientMobileOtpFeedback.classList.add(`is-${type || "info"}`);
            clientMobileOtpFeedback.textContent = message;
        };

        const setClientMobileDraftStatus = (type = "info", message = "") => {
            if (!clientMobileDraftStatus) {
                return;
            }

            clientMobileDraftStatus.className = "settings-mobile-modal__status";
            clientMobileDraftStatus.classList.add(`is-${type || "info"}`);
            clientMobileDraftStatus.textContent = message || "Enter a new number.";
        };

        const getClientMobileDraftState = () => {
            const rawValue = clientMobileDraftInput?.value?.trim() || "";
            const currentMobile = getClientDraftMobile();
            const originalMobile = getClientOriginalMobile();
            const hasRequestedCurrentMobile = currentMobile !== "" && currentMobile === clientMobileOtpRequestedMobile;
            const isVerifiedCurrentMobile = currentMobile !== "" && currentMobile === clientMobileOtpVerifiedMobile;
            const maskedTarget = clientMobileOtpMaskedTarget || maskMobileValue(clientMobileDraftInput?.value || "");

            if (!rawValue) {
                return {
                    key: "empty",
                    type: "info",
                    message: "Enter a new number."
                };
            }

            if (currentMobile === originalMobile) {
                return {
                    key: "same",
                    type: "warning",
                    message: "Use a different number."
                };
            }

            if (!isValidNormalizedMobile(currentMobile)) {
                return {
                    key: "invalid",
                    type: "warning",
                    message: "Use a valid PH number."
                };
            }

            if (isVerifiedCurrentMobile) {
                return {
                    key: "verified",
                    type: "success",
                    message: `${maskedTarget} verified.`
                };
            }

            if (hasRequestedCurrentMobile) {
                return {
                    key: "otp_sent",
                    type: "info",
                    message: `Enter OTP sent to ${maskedTarget}.`
                };
            }

            return {
                key: "ready",
                type: "ready",
                message: `OTP will be sent to ${maskedTarget}.`
            };
        };

        const clearClientMobileOtpTimer = () => {
            if (clientMobileOtpTimerId) {
                window.clearInterval(clientMobileOtpTimerId);
                clientMobileOtpTimerId = null;
            }
        };

        const updateClientMobileOtpResendState = () => {
            if (!clientMobileOtpResendButton) {
                return;
            }

            const currentMobile = getClientDraftMobile();
            const hasRequestedCurrentMobile = currentMobile !== "" && currentMobile === clientMobileOtpRequestedMobile;
            const isVerifiedCurrentMobile = currentMobile !== "" && currentMobile === clientMobileOtpVerifiedMobile;

            clientMobileOtpResendButton.hidden = !hasRequestedCurrentMobile || isVerifiedCurrentMobile;

            if (!hasRequestedCurrentMobile || isVerifiedCurrentMobile) {
                clientMobileOtpResendButton.disabled = false;
                clientMobileOtpResendButton.textContent = "Resend";
                return;
            }

            if (clientMobileOtpCountdown > 0) {
                clientMobileOtpResendButton.disabled = true;
                clientMobileOtpResendButton.textContent = `Resend in ${clientMobileOtpCountdown}s`;
                return;
            }

            clientMobileOtpResendButton.disabled = false;
            clientMobileOtpResendButton.textContent = "Resend";
        };

        const startClientMobileOtpTimer = () => {
            clearClientMobileOtpTimer();
            clientMobileOtpCountdown = 45;
            updateClientMobileOtpResendState();

            clientMobileOtpTimerId = window.setInterval(() => {
                clientMobileOtpCountdown -= 1;

                if (clientMobileOtpCountdown <= 0) {
                    clientMobileOtpCountdown = 0;
                    clearClientMobileOtpTimer();
                }

                updateClientMobileOtpResendState();
            }, 1000);
        };

        const syncClientMobileOtpPanel = () => {
            if (!clientMobileOtpPanel || !clientMobileOtpSendButton || !clientMobileOtpEntry || !clientMobileOtpVerifyButton) {
                return;
            }

            const currentMobile = getClientDraftMobile();
            const hasRequestedCurrentMobile = currentMobile !== "" && currentMobile === clientMobileOtpRequestedMobile;
            const isVerifiedCurrentMobile = currentMobile !== "" && currentMobile === clientMobileOtpVerifiedMobile;
            const maskedTarget = clientMobileOtpMaskedTarget || maskMobileValue(clientMobileDraftInput?.value || "");
            const draftState = getClientMobileDraftState();

            clientMobileOtpPanel.hidden = false;
            syncClientMobileCurrentDisplay();
            setClientMobileDraftStatus(draftState.type, draftState.message);

            if (clientMobileDraftHelp) {
                clientMobileDraftHelp.textContent = isVerifiedCurrentMobile
                    ? "You can use this number now."
                    : "Example: 0917 123 4567";
            }

            if (clientMobileOtpTarget) {
                clientMobileOtpTarget.textContent = maskedTarget;
            }

            clientMobileOtpSendButton.hidden = false;
            clientMobileOtpSendButton.disabled = draftState.key !== "ready";
            clientMobileOtpSendButton.textContent = "Send OTP";
            clientMobileOtpSendButton.hidden = hasRequestedCurrentMobile || isVerifiedCurrentMobile;
            clientMobileOtpEntry.hidden = !hasRequestedCurrentMobile && !isVerifiedCurrentMobile;

            if (clientMobileOtpInput) {
                clientMobileOtpInput.disabled = isVerifiedCurrentMobile;
            }

            clientMobileOtpVerifyButton.disabled = isVerifiedCurrentMobile;
            clientMobileOtpVerifyButton.textContent = isVerifiedCurrentMobile ? "Verified" : "Verify";

            if (clientMobileApplyButton) {
                clientMobileApplyButton.disabled = !isVerifiedCurrentMobile;
            }

            if (!isVerifiedCurrentMobile && !hasRequestedCurrentMobile && clientMobileOtpInput) {
                clientMobileOtpInput.value = "";
            }

            updateClientMobileOtpResendState();
        };

        const requestClientMobileOtp = async (isResend = false) => {
            if (!clientMobileDraftInput || !window.EmariohAuth?.post) {
                return;
            }

            clientMobileDraftInput.setCustomValidity("");

            if (!clientMobileDraftInput.value.trim()) {
                clientMobileDraftInput.setCustomValidity("Enter the new mobile number first.");
                clientMobileDraftInput.reportValidity();
                return;
            }

            if (getClientDraftMobile() === getClientOriginalMobile()) {
                clientMobileDraftInput.setCustomValidity("Enter a different mobile number first.");
                clientMobileDraftInput.reportValidity();
                return;
            }

            if (!isValidNormalizedMobile(getClientDraftMobile())) {
                clientMobileDraftInput.setCustomValidity("Enter a valid mobile number.");
                clientMobileDraftInput.reportValidity();
                return;
            }

            setButtonsBusy(
                [isResend ? clientMobileOtpResendButton : clientMobileOtpSendButton],
                true,
                "Sending..."
            );

            try {
                clientMobileDraftInput.value = formatMobileForDisplay(clientMobileDraftInput.value);

                const response = await window.EmariohAuth.post("request-client-mobile-otp.php", {
                    full_name: fullNameInput?.value?.trim() || "",
                    mobile: clientMobileDraftInput.value.trim()
                });

                clientMobileOtpRequestedMobile = getClientDraftMobile();
                clientMobileOtpMaskedTarget = response.masked_mobile || maskMobileValue(clientMobileDraftInput.value);
                if (clientMobileOtpInput) {
                    clientMobileOtpInput.value = "";
                }
                startClientMobileOtpTimer();
                syncClientMobileOtpPanel();

                if (clientMobileOtpInput) {
                    clientMobileOtpInput.focus();
                }

                if (response.demo_otp) {
                    setClientMobileOtpFeedback("info", `Development OTP: ${response.demo_otp}. Use this while SMS delivery is not connected yet.`);
                } else {
                    setClientMobileOtpFeedback("info", response.message || "OTP sent. Enter it to continue.");
                }
            } catch (error) {
                setClientMobileOtpFeedback("error", error.message || "OTP request failed. Please try again.");
                clientMobileDraftInput.focus();
            } finally {
                setButtonsBusy([clientMobileOtpSendButton, clientMobileOtpResendButton], false);
                syncClientMobileOtpPanel();
            }
        };

        const verifyClientMobileOtp = async () => {
            if (!clientMobileOtpInput || !clientMobileDraftInput || !window.EmariohAuth?.post) {
                return;
            }

            const currentMobile = getClientDraftMobile();
            const enteredOtp = getDigits(clientMobileOtpInput.value).slice(0, 6);

            if (!currentMobile || currentMobile !== clientMobileOtpRequestedMobile) {
                setClientMobileOtpFeedback("error", "Send an OTP to this mobile number first.");
                clientMobileOtpSendButton?.focus();
                return;
            }

            if (enteredOtp.length !== 6) {
                setClientMobileOtpFeedback("error", "Enter the 6-digit OTP.");
                clientMobileOtpInput.focus();
                return;
            }

            setButtonsBusy([clientMobileOtpVerifyButton], true, "Verifying...");

            try {
                await window.EmariohAuth.post("verify-client-mobile-otp.php", {
                    mobile: clientMobileDraftInput?.value?.trim() || "",
                    otp: enteredOtp
                });

                clientMobileOtpVerifiedMobile = currentMobile;
                clientMobileOtpMaskedTarget = clientMobileOtpMaskedTarget || maskMobileValue(clientMobileDraftInput.value);
                clearClientMobileOtpTimer();
                clientMobileOtpCountdown = 0;
                setClientMobileOtpFeedback("success", "Verified. Click Use & Save.");
            } catch (error) {
                setClientMobileOtpFeedback("error", error.message || "OTP verification failed.");
            } finally {
                syncClientMobileOtpPanel();
            }
        };

        const handleClientMobileDraftInputChange = () => {
            const currentMobile = getClientDraftMobile();
            const matchesRequestedMobile = currentMobile !== "" && currentMobile === clientMobileOtpRequestedMobile;
            const matchesVerifiedMobile = currentMobile !== "" && currentMobile === clientMobileOtpVerifiedMobile;

            if (!matchesRequestedMobile && !matchesVerifiedMobile) {
                clearClientMobileOtpTimer();
                clientMobileOtpCountdown = 0;
                clientMobileOtpRequestedMobile = "";
                clientMobileOtpMaskedTarget = "";
                if (clientMobileOtpInput) {
                    clientMobileOtpInput.value = "";
                }
                setClientMobileOtpFeedback("", "");
            }

            syncClientMobileOtpPanel();
        };

        const openClientMobileChangeModal = () => {
            if (!clientMobileDraftInput) {
                return;
            }

            const currentMobile = getClientCurrentMobile();
            const hasPendingFormMobile = currentMobile !== "" && currentMobile !== getClientOriginalMobile();

            if (hasPendingFormMobile) {
                clientMobileDraftInput.value = formatMobileForDisplay(clientMobileInput?.value || currentMobile);
            } else if (clientMobileOtpVerifiedMobile) {
                clientMobileDraftInput.value = formatMobileForDisplay(clientMobileOtpVerifiedMobile);
            } else if (clientMobileOtpRequestedMobile) {
                clientMobileDraftInput.value = formatMobileForDisplay(clientMobileOtpRequestedMobile);
            } else {
                clientMobileDraftInput.value = "";
            }

            handleClientMobileDraftInputChange();

            if (clientMobileChangeModal) {
                clientMobileChangeModal.show();
                return;
            }

            clientMobileDraftInput.focus();

            if (clientMobileDraftInput.value) {
                clientMobileDraftInput.select();
            }
        };

        const applyVerifiedClientMobile = () => {
            if (!clientMobileInput || !clientMobileDraftInput || !clientAccountForm) {
                return;
            }

            const currentDraftMobile = getClientDraftMobile();

            if (!currentDraftMobile || currentDraftMobile !== clientMobileOtpVerifiedMobile) {
                setClientMobileOtpFeedback("error", "Verify the OTP for this mobile number first.");
                clientMobileOtpInput?.focus();
                return;
            }

            clientMobileInput.value = formatMobileForDisplay(clientMobileDraftInput.value.trim());
            syncClientMobileFieldState();

            if (clientMobileChangeModal) {
                clientMobileChangeModal.hide();
            }

            clientAccountForm.requestSubmit();
        };

        const showClientSettingsSection = (targetSection, targetHash = "", options = {}) => {
            if (!targetSection) {
                return;
            }

            const shouldUpdateHash = options.updateHash !== false;

            settingsTabs.forEach((item) => {
                const isActive = item.dataset.targetSection === targetSection;
                item.classList.toggle("is-active", isActive);

                if (isActive) {
                    item.setAttribute("aria-current", "page");
                } else {
                    item.removeAttribute("aria-current");
                }
            });

            settingsSections.forEach((section) => {
                section.hidden = section.dataset.settingsSection !== targetSection;
            });

            const nextHash = targetHash || settingsTabs[0]?.getAttribute("href") || "";

            if (shouldUpdateHash && nextHash && window.location.hash !== nextHash) {
                window.history.replaceState(null, "", nextHash);
            }
        };

        const resolveClientSettingsTarget = (hash) => {
            const normalizedHash = String(hash || "").trim();

            if (normalizedHash === "") {
                return null;
            }

            const matchedTab = Array.from(settingsTabs).find((tab) => tab.getAttribute("href") === normalizedHash);

            if (matchedTab?.dataset.targetSection) {
                return {
                    section: matchedTab.dataset.targetSection,
                    hash: normalizedHash
                };
            }

            const normalizedId = normalizedHash.replace(/^#/, "");
            const matchedSection = Array.from(settingsSections).find((section) =>
                section.id === normalizedId || section.dataset.settingsSection === normalizedId
            );

            if (!matchedSection) {
                return null;
            }

            return {
                section: matchedSection.dataset.settingsSection,
                hash: `#${matchedSection.id}`
            };
        };

        const clearClientSettingsHash = () => {
            if (!window.location.hash) {
                return;
            }

            window.history.replaceState(null, "", `${window.location.pathname}${window.location.search}`);
        };

        const setClientSettingsMobileState = (state) => {
            if (!clientSettingsPanel) {
                return;
            }

            const isMobile = mobileSettingsMedia.matches;
            const isExpanded = !isMobile || state === "expanded";

            clientSettingsPanel.dataset.mobileState = isExpanded ? "expanded" : "collapsed";
            clientSettingsPanel.setAttribute("aria-hidden", isExpanded ? "false" : "true");

            if (settingsDashboardContent) {
                settingsDashboardContent.dataset.mobileState = isExpanded ? "expanded" : "collapsed";
            }

            if (clientSettingsHub) {
                clientSettingsHub.hidden = isMobile && isExpanded;
            }
        };

        const syncClientSettingsMobileLayout = () => {
            const activeTarget = resolveClientSettingsTarget(window.location.hash);
            const shouldExpand = !mobileSettingsMedia.matches
                || Boolean(activeTarget)
                || clientSettingsPanel?.dataset.mobileState === "expanded";

            setClientSettingsMobileState(shouldExpand ? "expanded" : "collapsed");
        };

        settingsTabs.forEach((tab) => {
            tab.addEventListener("click", (event) => {
                event.preventDefault();
                showClientSettingsSection(tab.dataset.targetSection, tab.getAttribute("href") || "");
                setClientSettingsMobileState("expanded");
            });
        });

        clientSettingsOpenLinks.forEach((link) => {
            link.addEventListener("click", (event) => {
                event.preventDefault();
                showClientSettingsSection(link.dataset.targetSection || "", link.getAttribute("href") || "");
                setClientSettingsMobileState("expanded");
            });
        });

        clientSettingsBackButtons.forEach((button) => {
            button.addEventListener("click", () => {
                clearClientSettingsHash();
                setClientSettingsMobileState("collapsed");
            });
        });

        const initialClientHashTarget = resolveClientSettingsTarget(window.location.hash);
        const initialClientSettingsTarget = initialClientHashTarget
            || (settingsTabs[0]
                ? {
                    section: settingsTabs[0].dataset.targetSection,
                    hash: settingsTabs[0].getAttribute("href") || ""
                }
                : null);

        if (initialClientSettingsTarget) {
            showClientSettingsSection(initialClientSettingsTarget.section, initialClientSettingsTarget.hash, {
                updateHash: Boolean(initialClientHashTarget)
            });
        }

        syncClientSettingsMobileLayout();

        window.addEventListener("hashchange", () => {
            const target = resolveClientSettingsTarget(window.location.hash);

            if (!target) {
                syncClientSettingsMobileLayout();
                return;
            }

            showClientSettingsSection(target.section, target.hash);
            setClientSettingsMobileState("expanded");
        });

        if (typeof mobileSettingsMedia.addEventListener === "function") {
            mobileSettingsMedia.addEventListener("change", syncClientSettingsMobileLayout);
        } else if (typeof mobileSettingsMedia.addListener === "function") {
            mobileSettingsMedia.addListener(syncClientSettingsMobileLayout);
        }

        clientMobileChangeButton?.addEventListener("click", () => {
            openClientMobileChangeModal();
        });

        clientMobileChangeModalElement?.addEventListener("shown.bs.modal", () => {
            clientMobileDraftInput?.focus();

            if (clientMobileDraftInput?.value) {
                clientMobileDraftInput.select();
            }
        });

        clientMobileDraftInput?.addEventListener("input", () => {
            clientMobileDraftInput.value = formatMobileInputValue(clientMobileDraftInput.value);
            clientMobileDraftInput.setCustomValidity("");
            handleClientMobileDraftInputChange();
        });

        clientMobileDraftInput?.addEventListener("change", () => {
            clientMobileDraftInput.value = formatMobileForDisplay(clientMobileDraftInput.value);
            clientMobileDraftInput.setCustomValidity("");
            handleClientMobileDraftInputChange();
        });

        clientMobileOtpInput?.addEventListener("input", () => {
            clientMobileOtpInput.value = getDigits(clientMobileOtpInput.value).slice(0, 6);
        });

        clientMobileOtpInput?.addEventListener("keydown", (event) => {
            if (event.key !== "Enter") {
                return;
            }

            event.preventDefault();
            verifyClientMobileOtp();
        });

        clientMobileOtpSendButton?.addEventListener("click", () => {
            requestClientMobileOtp(false);
        });

        clientMobileOtpResendButton?.addEventListener("click", () => {
            requestClientMobileOtp(true);
        });

        clientMobileOtpVerifyButton?.addEventListener("click", () => {
            verifyClientMobileOtp();
        });

        clientMobileApplyButton?.addEventListener("click", () => {
            applyVerifiedClientMobile();
        });

        [clientCurrentPasswordInput, clientNewPasswordInput, clientConfirmPasswordInput].forEach((input) => {
            input?.addEventListener("input", () => {
                input.setCustomValidity("");
                setSecurityFeedback("", "");
            });
        });

        clientSecurityForm?.addEventListener("submit", (event) => {
            event.preventDefault();
            openClientPasswordConfirm();
        });

        clientPasswordConfirmButton?.addEventListener("click", () => {
            submitClientPasswordUpdate();
        });

        clientPasswordConfirmModalElement?.addEventListener("show.bs.modal", () => {
            syncClientPasswordConfirmContent();
        });

        if (settingsPanel && window.MutationObserver) {
            const settingsPanelAlertObserver = new MutationObserver(() => {
                consumeSettingsPanelAlerts();
            });

            settingsPanelAlertObserver.observe(settingsPanel, {
                childList: true,
                subtree: true
            });
        }

        clientAccountForm?.addEventListener("submit", async (event) => {
            event.preventDefault();

            const buttonLabel = clientAccountSaveButton?.querySelector("span");
            const payload = {
                full_name: fullNameInput?.value.trim() || "",
                mobile: clientMobileInput?.value.trim() || "",
                alternate_contact: alternateContactInput?.value.trim() || ""
            };

            if (!payload.full_name || !payload.mobile) {
                setAccountFeedback("Full name and mobile number are required before saving.", "danger");
                return;
            }

            if (isClientMobileChanged() && getClientCurrentMobile() !== clientMobileOtpVerifiedMobile) {
                syncClientMobileFieldState();
                openClientMobileChangeModal();
                setClientMobileOtpFeedback("error", "Verify the OTP sent to the new mobile number before saving.");

                if (getClientDraftMobile() === clientMobileOtpRequestedMobile) {
                    clientMobileOtpInput?.focus();
                } else {
                    clientMobileDraftInput?.focus();
                }

                return;
            }

            if (clientAccountSaveButton) {
                clientAccountSaveButton.disabled = true;
            }

            if (buttonLabel) {
                buttonLabel.textContent = "Saving...";
            }

            setAccountFeedback("Saving your updated contact details...", "default");

            try {
                const response = await window.EmariohAuth.post("update-client-profile.php", payload);
                const user = response?.user || null;
                const profile = response?.profile || null;

                if (user) {
                    if (clientMobileInput) {
                        clientMobileInput.dataset.originalMobile = normalizeMobileValue(user.mobile || clientMobileInput.value);
                    }

                    window.EmariohAuth.state = Object.assign({}, window.EmariohAuth.state || {}, {
                        user
                    });
                    window.EmariohAuth.applyUserContext(user);
                }

                if (alternateContactInput && profile) {
                    alternateContactInput.value = profile.alternate_contact || "";
                }

                clearClientMobileOtpTimer();
                clientMobileOtpCountdown = 0;
                clientMobileOtpRequestedMobile = "";
                clientMobileOtpVerifiedMobile = "";
                clientMobileOtpMaskedTarget = "";
                if (clientMobileOtpInput) {
                    clientMobileOtpInput.value = "";
                }
                setClientMobileOtpFeedback("", "");
                syncClientMobileFieldState();
                syncClientMobileOtpPanel();
                setAccountFeedback(response?.message || "Account details updated successfully.", "success");
            } catch (error) {
                setAccountFeedback(error?.message || "We could not save your account details right now. Please try again.", "danger");
            } finally {
                if (clientAccountSaveButton) {
                    clientAccountSaveButton.disabled = false;
                }

                if (buttonLabel) {
                    buttonLabel.textContent = "Save Account Changes";
                }
            }
        });

        syncClientMobileFieldState();
        syncClientMobileOtpPanel();
        syncClientPasswordConfirmContent();
        consumeSettingsPanelAlerts();
    </script>
</body>
</html>
