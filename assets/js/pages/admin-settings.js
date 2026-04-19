document.addEventListener("DOMContentLoaded", () => {
    const pageBody = document.body;
    const catalogApi = window.EmariohPackageCatalog;
    const paymentSettingsApi = window.EmariohPaymentSettings;
    const filterButtons = Array.from(document.querySelectorAll("[data-settings-filter]"));
    const sections = Array.from(document.querySelectorAll("[data-settings-section]"));
    const settingsPanel = document.querySelector("[data-settings-panel]");
    const settingsHub = document.querySelector("[data-settings-hub]");
    const settingsOpenLinks = Array.from(document.querySelectorAll("[data-settings-open]"));
    const settingsMenuLinks = Array.from(document.querySelectorAll("[data-settings-menu-item]"));
    const settingsBackButtons = Array.from(document.querySelectorAll("[data-settings-back]"));
    const settingsBackLabels = Array.from(document.querySelectorAll("[data-settings-back-label]"));
    const mobileSettingsMedia = window.matchMedia("(max-width: 1199.98px)");
    const galleryCategoryFilter = document.getElementById("galleryCategoryFilter");
    const galleryList = document.getElementById("galleryManagerList");
    const galleryEmptyState = document.getElementById("galleryManagerEmptyState");
    const galleryUploadForm = document.getElementById("galleryUploadForm");
    const galleryDeleteForm = document.getElementById("galleryDeleteForm");
    const galleryDeleteItemId = document.getElementById("galleryDeleteItemId");
    const galleryImageInput = document.getElementById("galleryImageInput");
    const galleryCaptionInput = document.getElementById("galleryCaptionInput");
    const gallerySaveButton = document.getElementById("gallerySaveButton");
    const gallerySaveConfirmModalElement = document.getElementById("gallerySaveConfirmModal");
    const gallerySaveConfirmButton = document.getElementById("gallerySaveConfirmButton");
    const gallerySaveConfirmModalText = document.getElementById("gallerySaveConfirmModalText");
    const gallerySaveConfirmSummary = document.getElementById("gallerySaveConfirmSummary");
    const galleryDeleteModalElement = document.getElementById("galleryDeleteModal");
    const galleryDeleteModalText = document.getElementById("galleryDeleteModalText");
    const galleryDeleteModalTargetTitle = document.getElementById("galleryDeleteModalTargetTitle");
    const galleryDeleteModalTargetFile = document.getElementById("galleryDeleteModalTargetFile");
    const galleryDeleteConfirmButton = document.getElementById("galleryDeleteConfirmButton");
    const adminAccountForm = document.getElementById("adminAccountForm");
    const adminAccountActionNote = document.getElementById("adminAccountActionNote");
    const adminAccountSaveButton = document.getElementById("adminAccountSaveButton");
    const adminAccountConfirmModalElement = document.getElementById("adminAccountConfirmModal");
    const adminAccountConfirmButton = document.getElementById("adminAccountConfirmButton");
    const adminAccountConfirmModalText = document.getElementById("adminAccountConfirmModalText");
    const adminAccountConfirmSummary = document.getElementById("adminAccountConfirmSummary");
    const adminFullNameInput = adminAccountForm?.querySelector('[name="admin_full_name"]');
    const adminCurrentPasswordInput = adminAccountForm?.querySelector('[name="admin_current_password"]');
    const adminNewPasswordInput = adminAccountForm?.querySelector('[name="admin_new_password"]');
    const adminConfirmPasswordInput = adminAccountForm?.querySelector('[name="admin_confirm_password"]');
    const adminMobileInput = document.getElementById("adminMobileInput");
    const adminMobileChangeButton = document.getElementById("adminMobileChangeButton");
    const adminMobileStatusNote = document.getElementById("adminMobileStatusNote");
    const adminMobileChangeModalElement = document.getElementById("adminMobileChangeModal");
    const adminMobileCurrentDisplay = document.getElementById("adminMobileCurrentDisplay");
    const adminMobileDraftInput = document.getElementById("adminMobileDraftInput");
    const adminMobileDraftHelp = document.getElementById("adminMobileDraftHelp");
    const adminMobileDraftStatus = document.getElementById("adminMobileDraftStatus");
    const adminMobileOtpPanel = document.getElementById("adminMobileOtpPanel");
    const adminMobileOtpEntry = document.getElementById("adminMobileOtpEntry");
    const adminMobileOtpInput = document.getElementById("adminMobileOtpInput");
    const adminMobileOtpSendButton = document.getElementById("adminMobileOtpSendButton");
    const adminMobileOtpResendButton = document.getElementById("adminMobileOtpResendButton");
    const adminMobileOtpVerifyButton = document.getElementById("adminMobileOtpVerifyButton");
    const adminMobileOtpTarget = document.getElementById("adminMobileOtpTarget");
    const adminMobileOtpFeedback = document.getElementById("adminMobileOtpFeedback");
    const adminMobileApplyButton = document.getElementById("adminMobileApplyButton");
    const heroImageForm = document.getElementById("heroImageForm");
    const heroImageInput = document.getElementById("heroImageInput");
    const heroImageSaveButton = document.getElementById("heroImageSaveButton");
    const contactDetailsForm = document.getElementById("contactDetailsForm");
    const contactDetailsSaveButton = document.getElementById("contactDetailsSaveButton");
    const contactServiceAreaInput = document.getElementById("contactServiceAreaInput");
    const contactPublicEmailInput = document.getElementById("contactPublicEmailInput");
    const contactPrimaryMobileInput = document.getElementById("contactPrimaryMobileInput");
    const contactBusinessHoursInput = document.getElementById("contactBusinessHoursInput");
    const contactDetailsConfirmModalElement = document.getElementById("contactDetailsConfirmModal");
    const contactDetailsConfirmModalText = document.getElementById("contactDetailsConfirmModalText");
    const contactDetailsConfirmSummary = document.getElementById("contactDetailsConfirmSummary");
    const contactDetailsConfirmButton = document.getElementById("contactDetailsConfirmButton");
    const serviceCardsForm = document.getElementById("serviceCardsForm");
    const serviceCardsSaveButton = document.getElementById("serviceCardsSaveButton");
    const serviceCardsConfirmModalElement = document.getElementById("serviceCardsConfirmModal");
    const serviceCardsConfirmButton = document.getElementById("serviceCardsConfirmButton");
    const serviceCardsConfirmModalText = document.getElementById("serviceCardsConfirmModalText");
    const serviceCardsConfirmSummary = document.getElementById("serviceCardsConfirmSummary");
    const smsTemplatesForm = document.getElementById("smsTemplatesForm");
    const smsTemplatesSaveButton = document.getElementById("smsTemplatesSaveButton");
    const smsTemplatesConfirmModalElement = document.getElementById("smsTemplatesConfirmModal");
    const smsTemplatesConfirmButton = document.getElementById("smsTemplatesConfirmButton");
    const smsTemplatesConfirmModalText = document.getElementById("smsTemplatesConfirmModalText");
    const smsTemplatesConfirmSummary = document.getElementById("smsTemplatesConfirmSummary");
    const packageDownPaymentConfirmModalElement = document.getElementById("packageDownPaymentConfirmModal");
    const packageDownPaymentConfirmButton = document.getElementById("packageDownPaymentConfirmButton");
    const packageDownPaymentConfirmModalText = document.getElementById("packageDownPaymentConfirmModalText");
    const packageDownPaymentConfirmSummary = document.getElementById("packageDownPaymentConfirmSummary");
    const heroImageConfirmModalElement = document.getElementById("heroImageConfirmModal");
    const heroImageConfirmButton = document.getElementById("heroImageConfirmButton");
    const heroImageConfirmModalText = document.getElementById("heroImageConfirmModalText");
    const heroImageConfirmTargetTitle = document.getElementById("heroImageConfirmTargetTitle");
    const heroImageConfirmTargetFile = document.getElementById("heroImageConfirmTargetFile");
    const gallerySaveConfirmModal = gallerySaveConfirmModalElement && window.bootstrap?.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(gallerySaveConfirmModalElement)
        : null;
    const galleryDeleteModal = galleryDeleteModalElement && window.bootstrap?.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(galleryDeleteModalElement)
        : null;
    const adminAccountConfirmModal = adminAccountConfirmModalElement && window.bootstrap?.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(adminAccountConfirmModalElement)
        : null;
    const adminMobileChangeModal = adminMobileChangeModalElement && window.bootstrap?.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(adminMobileChangeModalElement)
        : null;
    const heroImageConfirmModal = heroImageConfirmModalElement && window.bootstrap?.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(heroImageConfirmModalElement)
        : null;
    const contactDetailsConfirmModal = contactDetailsConfirmModalElement && window.bootstrap?.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(contactDetailsConfirmModalElement)
        : null;
    const serviceCardsConfirmModal = serviceCardsConfirmModalElement && window.bootstrap?.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(serviceCardsConfirmModalElement)
        : null;
    const smsTemplatesConfirmModal = smsTemplatesConfirmModalElement && window.bootstrap?.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(smsTemplatesConfirmModalElement)
        : null;
    const packageDownPaymentConfirmModal = packageDownPaymentConfirmModalElement && window.bootstrap?.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(packageDownPaymentConfirmModalElement)
        : null;
    const paymentSettingsForm = document.getElementById("paymentSettingsForm");
    const paymentSettingsFeedback = document.getElementById("paymentSettingsFeedback");
    const paymentGatewayField = document.getElementById("paymentGatewayField");
    const paymentMethodField = document.getElementById("paymentMethodField");
    const paymentAllowFullPaymentField = document.getElementById("paymentAllowFullPayment");
    const paymentBalanceDueRuleField = document.getElementById("paymentBalanceDueRule");
    const paymentReceiptRequirementField = document.getElementById("paymentReceiptRequirement");
    const paymentConfirmationRuleField = document.getElementById("paymentConfirmationRule");
    const paymentAcceptedWalletsLabelField = document.getElementById("paymentAcceptedWalletsLabel");
    const paymentSupportMobileField = document.getElementById("paymentSupportMobile");
    const paymentInstructionTextField = document.getElementById("paymentInstructionText");
    const packageDownPaymentForm = document.getElementById("packageDownPaymentForm");
    const packageDownPaymentList = document.getElementById("packageDownPaymentList");
    const packageDownPaymentFeedback = document.getElementById("packageDownPaymentFeedback");
    const packageDownPaymentSaveButton = document.getElementById("packageDownPaymentSaveButton");
    const packageDownPaymentSaveButtonLabel = packageDownPaymentSaveButton?.querySelector("span") || null;
    let pendingGalleryDeleteCard = null;
    let pendingGalleryDeleteId = "";
    let isAdminAccountSubmitting = false;
    let adminMobileOtpCountdown = 0;
    let adminMobileOtpTimerId = null;
    let adminMobileOtpRequestedMobile = "";
    let adminMobileOtpVerifiedMobile = "";
    let adminMobileOtpMaskedTarget = "";
    let isGallerySubmitting = false;
    let isHeroImageSubmitting = false;
    let isContactDetailsSubmitting = false;
    let isServiceCardsSubmitting = false;
    let isSmsTemplatesSubmitting = false;
    let isPackageDownPaymentSubmitting = false;
    let pendingPackageDownPaymentCatalog = null;

    function escapeHtml(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    function getDigits(value) {
        return String(value || "").replace(/\D/g, "");
    }

    function normalizeNameValue(value) {
        return String(value || "").replace(/\s+/g, " ").trim();
    }

    function normalizeMobileValue(value) {
        const digits = getDigits(value);

        if (digits.length === 10 && digits.startsWith("9")) {
            return `63${digits}`;
        }

        if (digits.length === 11 && digits.startsWith("0")) {
            return `63${digits.slice(1)}`;
        }

        return digits;
    }

    function maskMobileValue(value) {
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
    }

    function getAdminOriginalMobile() {
        return normalizeMobileValue(adminMobileInput?.dataset.originalMobile || "");
    }

    function getAdminCurrentMobile() {
        return normalizeMobileValue(adminMobileInput?.value || "");
    }

    function getAdminOriginalName() {
        return normalizeNameValue(adminFullNameInput?.dataset.originalValue || "");
    }

    function getAdminCurrentName() {
        return normalizeNameValue(adminFullNameInput?.value || "");
    }

    function getAdminDraftMobile() {
        return normalizeMobileValue(adminMobileDraftInput?.value || "");
    }

    function isValidNormalizedMobile(value) {
        return /^639\d{9}$/.test(String(value || ""));
    }

    function formatMobileInputValue(value) {
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
    }

    function formatMobileForDisplay(value) {
        const normalized = normalizeMobileValue(value);

        if (isValidNormalizedMobile(normalized)) {
            const localMobile = `0${normalized.slice(2)}`;
            return `${localMobile.slice(0, 4)} ${localMobile.slice(4, 7)} ${localMobile.slice(7)}`;
        }

        return formatMobileInputValue(value);
    }

    function summarizeSmsTemplateValue(value) {
        const normalizedValue = String(value || "").replace(/\s+/g, " ").trim();

        if (!normalizedValue) {
            return "No message entered";
        }

        if (normalizedValue.length <= 88) {
            return normalizedValue;
        }

        return `${normalizedValue.slice(0, 85).trimEnd()}...`;
    }

    function collectSmsTemplateDrafts() {
        return Array.from(smsTemplatesForm?.querySelectorAll('textarea[name^="sms_templates["]') || []).map((field) => {
            const fieldWrapper = field.closest(".settings-field");
            const labelText = fieldWrapper?.querySelector(".settings-field__label")?.textContent || "SMS Template";

            return {
                label: String(labelText).trim() || "SMS Template",
                currentValue: String(field.value || "").trim(),
                originalValue: String(field.defaultValue || "").trim()
            };
        });
    }

    function setButtonsBusy(buttons, isBusy, busyLabel) {
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
    }

    function clearAdminAccountFlashQuery() {
        const currentUrl = new URL(window.location.href);

        if (!currentUrl.searchParams.has("admin_account")) {
            return;
        }

        currentUrl.searchParams.delete("admin_account");
        window.history.replaceState({}, document.title, `${currentUrl.pathname}${currentUrl.search}${currentUrl.hash}`);
    }

    function syncAdminAccountActionNoteFlash() {
        if (!adminAccountActionNote) {
            return;
        }

        const flashState = adminAccountActionNote.dataset.flashState || "";
        const flashMessage = adminAccountActionNote.dataset.flashMessage || "";
        const flashIcon = adminAccountActionNote.dataset.flashIcon || "bi-check2-circle";
        const defaultMessage = adminAccountActionNote.dataset.defaultMessage || "Review your changes before saving.";
        const defaultIcon = adminAccountActionNote.dataset.defaultIcon || "bi-info-circle";
        const noteIcon = adminAccountActionNote.querySelector("i");
        const noteCopy = adminAccountActionNote.querySelector("span");

        if (!flashState || !flashMessage || !noteIcon || !noteCopy) {
            return;
        }

        clearAdminAccountFlashQuery();

        window.setTimeout(() => {
            adminAccountActionNote.classList.remove(`is-${flashState}`, "is-flash");
            adminAccountActionNote.dataset.flashState = "";
            adminAccountActionNote.dataset.flashMessage = "";
            adminAccountActionNote.dataset.flashIcon = defaultIcon;
            noteIcon.className = `bi ${defaultIcon}`;
            noteCopy.textContent = defaultMessage;
        }, 4000);
    }

    function setAdminAccountActionNote(message = "", state = "") {
        if (!adminAccountActionNote) {
            return;
        }

        const noteIcon = adminAccountActionNote.querySelector("i");
        const noteCopy = adminAccountActionNote.querySelector("span");
        const defaultMessage = adminAccountActionNote.dataset.defaultMessage || "Review your changes before saving.";
        const defaultIcon = adminAccountActionNote.dataset.defaultIcon || "bi-info-circle";

        if (!noteIcon || !noteCopy) {
            return;
        }

        adminAccountActionNote.classList.remove("is-success", "is-warning", "is-danger", "is-flash");
        adminAccountActionNote.dataset.flashState = "";
        adminAccountActionNote.dataset.flashMessage = "";
        adminAccountActionNote.dataset.flashIcon = defaultIcon;

        if (!message) {
            noteIcon.className = `bi ${defaultIcon}`;
            noteCopy.textContent = defaultMessage;
            return;
        }

        let nextIcon = defaultIcon;

        if (state === "success") {
            nextIcon = "bi-check2-circle";
        } else if (state === "danger") {
            nextIcon = "bi-exclamation-circle";
        }

        if (state) {
            adminAccountActionNote.classList.add(`is-${state}`);
        }

        noteIcon.className = `bi ${nextIcon}`;
        noteCopy.textContent = message;
    }

    function getAdminPasswordPayload() {
        return {
            currentPassword: adminCurrentPasswordInput?.value || "",
            newPassword: adminNewPasswordInput?.value || "",
            confirmPassword: adminConfirmPasswordInput?.value || ""
        };
    }

    function isAdminPasswordChangeRequested() {
        const payload = getAdminPasswordPayload();
        return payload.currentPassword !== ""
            || payload.newPassword !== ""
            || payload.confirmPassword !== "";
    }

    function hasAdminAccountChanges() {
        return getAdminCurrentName() !== getAdminOriginalName()
            || isAdminMobileChanged()
            || isAdminPasswordChangeRequested();
    }

    function validateAdminAccountPasswordInputs(showFeedback = true) {
        const payload = getAdminPasswordPayload();
        const passwordChangeRequested = isAdminPasswordChangeRequested();
        let invalidField = null;
        let message = "";

        if (!passwordChangeRequested) {
            return {
                isValid: true,
                passwordChangeRequested
            };
        }

        if (payload.currentPassword === "") {
            invalidField = adminCurrentPasswordInput;
            message = "Enter the current password before saving a password change.";
        } else if (payload.newPassword === "") {
            invalidField = adminNewPasswordInput;
            message = "Enter a new password before saving.";
        } else if (payload.newPassword.length < 8) {
            invalidField = adminNewPasswordInput;
            message = "Use at least 8 characters for the new password.";
        } else if (payload.confirmPassword === "") {
            invalidField = adminConfirmPasswordInput;
            message = "Confirm the new password before saving.";
        } else if (payload.newPassword !== payload.confirmPassword) {
            invalidField = adminConfirmPasswordInput;
            message = "New password and confirmation do not match.";
        }

        if (!invalidField || !message) {
            return {
                isValid: true,
                passwordChangeRequested
            };
        }

        if (showFeedback) {
            setAdminAccountActionNote(message, "danger");
        }

        invalidField.focus();

        return {
            isValid: false,
            passwordChangeRequested
        };
    }

    function syncAdminAccountConfirmContent() {
        const changedItems = [];
        const hasNameChange = getAdminCurrentName() !== getAdminOriginalName();
        const hasMobileChange = isAdminMobileChanged();
        const hasPasswordChange = isAdminPasswordChangeRequested();

        if (hasNameChange) {
            changedItems.push({
                label: "Admin Name",
                value: adminFullNameInput?.value?.trim() || "No name provided"
            });
        }

        if (hasMobileChange) {
            changedItems.push({
                label: "Mobile Number",
                value: formatMobileForDisplay(adminMobileInput?.value || "") || "No mobile number provided"
            });
        }

        if (hasPasswordChange) {
            changedItems.push({
                label: "Password",
                value: "The admin password will be updated."
            });
        }

        const hasChanges = changedItems.length > 0;

        if (adminAccountConfirmModalText) {
            adminAccountConfirmModalText.textContent = hasChanges
                ? "Save these admin account changes?"
                : "No admin account changes were detected.";
        }

        if (adminAccountConfirmSummary) {
            adminAccountConfirmSummary.innerHTML = hasChanges
                ? changedItems.map((item) => `
                    <div class="gallery-delete-modal__target">
                        <strong>${escapeHtml(item.label)}</strong>
                        <span>${escapeHtml(item.value)}</span>
                    </div>
                `).join("")
                : `
                    <div class="gallery-delete-modal__target">
                        <strong>No changes detected</strong>
                        <span>Update a field first, then save again.</span>
                    </div>
                `;
        }

        if (adminAccountConfirmButton && !isAdminAccountSubmitting) {
            adminAccountConfirmButton.disabled = !hasChanges;
            adminAccountConfirmButton.textContent = "Yes, Save Changes";
        }

        return hasChanges;
    }

    function submitAdminAccountForm() {
        if (!adminAccountForm || isAdminAccountSubmitting) {
            return;
        }

        isAdminAccountSubmitting = true;
        setButtonsBusy([adminAccountSaveButton, adminAccountConfirmButton], true, "Saving...");
        adminAccountConfirmModal?.hide();
        adminAccountForm.submit();
    }

    function openAdminAccountConfirm() {
        if (!adminAccountForm) {
            return;
        }

        const hasChanges = syncAdminAccountConfirmContent();

        if (!hasChanges) {
            setAdminAccountActionNote("No admin account changes were detected.", "warning");
            return;
        }

        setAdminAccountActionNote("", "");

        if (!adminAccountConfirmModal) {
            const shouldSubmit = window.confirm("Save these admin account changes?");

            if (shouldSubmit) {
                submitAdminAccountForm();
            }

            return;
        }

        adminAccountConfirmModal.show();
    }

    function isAdminMobileChanged() {
        const currentMobile = getAdminCurrentMobile();
        return currentMobile !== "" && currentMobile !== getAdminOriginalMobile();
    }

    function syncAdminMobileCurrentDisplay() {
        if (!adminMobileCurrentDisplay) {
            return;
        }

        const currentLabel = formatMobileForDisplay(adminMobileInput?.value || getAdminOriginalMobile());
        adminMobileCurrentDisplay.textContent = currentLabel || "No mobile number on file";
    }

    function setAdminMobileStatus(type = "", message = "") {
        if (!adminMobileStatusNote) {
            return;
        }

        adminMobileStatusNote.className = "settings-mobile-status-note is-hidden";
        adminMobileStatusNote.textContent = "";

        if (!message) {
            return;
        }

        adminMobileStatusNote.classList.remove("is-hidden");
        adminMobileStatusNote.classList.add(`is-${type || "info"}`);
        adminMobileStatusNote.textContent = message;
    }

    function syncAdminMobileFieldState() {
        if (!adminMobileInput) {
            return;
        }

        adminMobileInput.readOnly = true;
        adminMobileInput.classList.add("is-readonly");
        syncAdminMobileCurrentDisplay();

        if (!isAdminMobileChanged()) {
            setAdminMobileStatus("", "");
            return;
        }

        if (getAdminCurrentMobile() === adminMobileOtpVerifiedMobile) {
            setAdminMobileStatus("success", "New mobile number verified. Save changes to apply it.");
            return;
        }

        setAdminMobileStatus("warning", "This mobile number still needs OTP verification.");
    }

    function setAdminMobileOtpFeedback(type = "", message = "") {
        if (!adminMobileOtpFeedback) {
            return;
        }

        adminMobileOtpFeedback.className = "settings-mobile-otp__feedback is-hidden";
        adminMobileOtpFeedback.textContent = "";

        if (!message) {
            return;
        }

        adminMobileOtpFeedback.classList.remove("is-hidden");
        adminMobileOtpFeedback.classList.add(`is-${type || "info"}`);
        adminMobileOtpFeedback.textContent = message;
    }

    function setAdminMobileDraftStatus(type = "info", message = "") {
        if (!adminMobileDraftStatus) {
            return;
        }

        adminMobileDraftStatus.className = "settings-mobile-modal__status";
        adminMobileDraftStatus.classList.add(`is-${type || "info"}`);
        adminMobileDraftStatus.textContent = message || "Enter a new number.";
    }

    function getAdminMobileDraftState() {
        const rawValue = adminMobileDraftInput?.value?.trim() || "";
        const currentMobile = getAdminDraftMobile();
        const originalMobile = getAdminOriginalMobile();
        const hasRequestedCurrentMobile = currentMobile !== "" && currentMobile === adminMobileOtpRequestedMobile;
        const isVerifiedCurrentMobile = currentMobile !== "" && currentMobile === adminMobileOtpVerifiedMobile;
        const maskedTarget = adminMobileOtpMaskedTarget || maskMobileValue(adminMobileDraftInput?.value || "");

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
    }

    function clearAdminMobileOtpTimer() {
        if (adminMobileOtpTimerId) {
            window.clearInterval(adminMobileOtpTimerId);
            adminMobileOtpTimerId = null;
        }
    }

    function updateAdminMobileOtpResendState() {
        if (!adminMobileOtpResendButton) {
            return;
        }

        const currentMobile = getAdminDraftMobile();
        const hasRequestedCurrentMobile = currentMobile !== "" && currentMobile === adminMobileOtpRequestedMobile;
        const isVerifiedCurrentMobile = currentMobile !== "" && currentMobile === adminMobileOtpVerifiedMobile;

        adminMobileOtpResendButton.hidden = !hasRequestedCurrentMobile || isVerifiedCurrentMobile;

        if (!hasRequestedCurrentMobile || isVerifiedCurrentMobile) {
            adminMobileOtpResendButton.disabled = false;
            adminMobileOtpResendButton.textContent = "Resend";
            return;
        }

        if (adminMobileOtpCountdown > 0) {
            adminMobileOtpResendButton.disabled = true;
            adminMobileOtpResendButton.textContent = `Resend in ${adminMobileOtpCountdown}s`;
            return;
        }

        adminMobileOtpResendButton.disabled = false;
        adminMobileOtpResendButton.textContent = "Resend";
    }

    function startAdminMobileOtpTimer() {
        clearAdminMobileOtpTimer();
        adminMobileOtpCountdown = 45;
        updateAdminMobileOtpResendState();

        adminMobileOtpTimerId = window.setInterval(() => {
            adminMobileOtpCountdown -= 1;

            if (adminMobileOtpCountdown <= 0) {
                adminMobileOtpCountdown = 0;
                clearAdminMobileOtpTimer();
            }

            updateAdminMobileOtpResendState();
        }, 1000);
    }

    function syncAdminMobileOtpPanel() {
        if (!adminMobileOtpPanel || !adminMobileOtpSendButton || !adminMobileOtpEntry || !adminMobileOtpVerifyButton) {
            return;
        }

        const currentMobile = getAdminDraftMobile();
        const hasRequestedCurrentMobile = currentMobile !== "" && currentMobile === adminMobileOtpRequestedMobile;
        const isVerifiedCurrentMobile = currentMobile !== "" && currentMobile === adminMobileOtpVerifiedMobile;
        const maskedTarget = adminMobileOtpMaskedTarget || maskMobileValue(adminMobileDraftInput?.value || "");
        const draftState = getAdminMobileDraftState();

        adminMobileOtpPanel.hidden = false;
        syncAdminMobileCurrentDisplay();
        setAdminMobileDraftStatus(draftState.type, draftState.message);

        if (adminMobileDraftHelp) {
            adminMobileDraftHelp.textContent = isVerifiedCurrentMobile
                ? "You can use this number now."
                : "Example: 0917 123 4567";
        }

        if (adminMobileOtpTarget) {
            adminMobileOtpTarget.textContent = maskedTarget;
        }

        adminMobileOtpSendButton.hidden = false;
        adminMobileOtpSendButton.disabled = draftState.key !== "ready";
        adminMobileOtpSendButton.textContent = "Send OTP";
        adminMobileOtpSendButton.hidden = hasRequestedCurrentMobile || isVerifiedCurrentMobile;
        adminMobileOtpEntry.hidden = !hasRequestedCurrentMobile && !isVerifiedCurrentMobile;
        adminMobileOtpInput && (adminMobileOtpInput.disabled = isVerifiedCurrentMobile);
        adminMobileOtpVerifyButton.disabled = isVerifiedCurrentMobile;
        adminMobileOtpVerifyButton.textContent = isVerifiedCurrentMobile ? "Verified" : "Verify";
        adminMobileApplyButton && (adminMobileApplyButton.disabled = !isVerifiedCurrentMobile);

        if (!isVerifiedCurrentMobile && !hasRequestedCurrentMobile) {
            adminMobileOtpInput && (adminMobileOtpInput.value = "");
        }

        updateAdminMobileOtpResendState();
    }

    async function requestAdminMobileOtp(isResend = false) {
        if (!adminMobileDraftInput || !window.EmariohAuth?.post) {
            return;
        }

        adminMobileDraftInput.setCustomValidity("");

        if (!adminMobileDraftInput.value.trim()) {
            adminMobileDraftInput.setCustomValidity("Enter the new mobile number first.");
            adminMobileDraftInput.reportValidity();
            return;
        }

        if (getAdminDraftMobile() === getAdminOriginalMobile()) {
            adminMobileDraftInput.setCustomValidity("Enter a different mobile number first.");
            adminMobileDraftInput.reportValidity();
            return;
        }

        if (!isValidNormalizedMobile(getAdminDraftMobile())) {
            adminMobileDraftInput.setCustomValidity("Enter a valid mobile number.");
            adminMobileDraftInput.reportValidity();
            return;
        }

        setButtonsBusy(
            [isResend ? adminMobileOtpResendButton : adminMobileOtpSendButton],
            true,
            "Sending..."
        );

        try {
            adminMobileDraftInput.value = formatMobileForDisplay(adminMobileDraftInput.value);

            const response = await window.EmariohAuth.post("request-admin-mobile-otp.php", {
                full_name: adminFullNameInput?.value?.trim() || "",
                mobile: adminMobileDraftInput.value.trim()
            });

            adminMobileOtpRequestedMobile = getAdminDraftMobile();
            adminMobileOtpMaskedTarget = response.masked_mobile || maskMobileValue(adminMobileDraftInput.value);
            adminMobileOtpInput && (adminMobileOtpInput.value = "");
            startAdminMobileOtpTimer();
            syncAdminMobileOtpPanel();

            if (adminMobileOtpInput) {
                adminMobileOtpInput.focus();
            }

            if (response.demo_otp) {
                setAdminMobileOtpFeedback("info", `Development OTP: ${response.demo_otp}. Use this while SMS delivery is not connected yet.`);
            } else {
                setAdminMobileOtpFeedback("info", response.message || "OTP sent. Enter it to continue.");
            }
        } catch (error) {
            setAdminMobileOtpFeedback("error", error.message || "OTP request failed. Please try again.");
            adminMobileDraftInput.focus();
        } finally {
            setButtonsBusy([adminMobileOtpSendButton, adminMobileOtpResendButton], false);
            syncAdminMobileOtpPanel();
        }
    }

    async function verifyAdminMobileOtp() {
        if (!adminMobileOtpInput || !adminMobileDraftInput || !window.EmariohAuth?.post) {
            return;
        }

        const currentMobile = getAdminDraftMobile();
        const enteredOtp = getDigits(adminMobileOtpInput.value).slice(0, 6);

        if (!currentMobile || currentMobile !== adminMobileOtpRequestedMobile) {
            setAdminMobileOtpFeedback("error", "Send an OTP to this mobile number first.");
            adminMobileOtpSendButton?.focus();
            return;
        }

        if (enteredOtp.length !== 6) {
            setAdminMobileOtpFeedback("error", "Enter the 6-digit OTP.");
            adminMobileOtpInput.focus();
            return;
        }

        setButtonsBusy([adminMobileOtpVerifyButton], true, "Verifying...");

        try {
            await window.EmariohAuth.post("verify-admin-mobile-otp.php", {
                mobile: adminMobileDraftInput?.value?.trim() || "",
                otp: enteredOtp
            });

            adminMobileOtpVerifiedMobile = currentMobile;
            adminMobileOtpMaskedTarget = adminMobileOtpMaskedTarget || maskMobileValue(adminMobileDraftInput.value);
            clearAdminMobileOtpTimer();
            adminMobileOtpCountdown = 0;
            setAdminMobileOtpFeedback("success", "Verified. Click Use & Save.");
        } catch (error) {
            setAdminMobileOtpFeedback("error", error.message || "OTP verification failed.");
        } finally {
            syncAdminMobileOtpPanel();
        }
    }

    function handleAdminMobileDraftInputChange() {
        const currentMobile = getAdminDraftMobile();
        const matchesRequestedMobile = currentMobile !== "" && currentMobile === adminMobileOtpRequestedMobile;
        const matchesVerifiedMobile = currentMobile !== "" && currentMobile === adminMobileOtpVerifiedMobile;

        if (!matchesRequestedMobile && !matchesVerifiedMobile) {
            clearAdminMobileOtpTimer();
            adminMobileOtpCountdown = 0;
            adminMobileOtpRequestedMobile = "";
            adminMobileOtpMaskedTarget = "";
            adminMobileOtpInput && (adminMobileOtpInput.value = "");
            setAdminMobileOtpFeedback("", "");
        }

        syncAdminMobileOtpPanel();
    }

    function openAdminMobileChangeModal() {
        if (!adminMobileDraftInput) {
            return;
        }

        const currentMobile = getAdminCurrentMobile();
        const hasPendingFormMobile = currentMobile !== "" && currentMobile !== getAdminOriginalMobile();

        if (hasPendingFormMobile) {
            adminMobileDraftInput.value = formatMobileForDisplay(adminMobileInput?.value || currentMobile);
        } else if (adminMobileOtpVerifiedMobile) {
            adminMobileDraftInput.value = formatMobileForDisplay(adminMobileOtpVerifiedMobile);
        } else if (adminMobileOtpRequestedMobile) {
            adminMobileDraftInput.value = formatMobileForDisplay(adminMobileOtpRequestedMobile);
        } else {
            adminMobileDraftInput.value = "";
        }

        handleAdminMobileDraftInputChange();

        if (adminMobileChangeModal) {
            adminMobileChangeModal.show();
            return;
        }

        adminMobileDraftInput.focus();

        if (adminMobileDraftInput.value) {
            adminMobileDraftInput.select();
        }
    }

    function applyVerifiedAdminMobile() {
        if (!adminMobileInput || !adminMobileDraftInput || !adminAccountForm) {
            return;
        }

        const currentDraftMobile = getAdminDraftMobile();

        if (!currentDraftMobile || currentDraftMobile !== adminMobileOtpVerifiedMobile) {
            setAdminMobileOtpFeedback("error", "Verify the OTP for this mobile number first.");
            adminMobileOtpInput?.focus();
            return;
        }

        adminMobileInput.value = formatMobileForDisplay(adminMobileDraftInput.value.trim());
        syncAdminMobileFieldState();

        if (adminMobileChangeModal) {
            adminMobileChangeModal.hide();
        }

        adminAccountForm.requestSubmit();
    }

    function getGalleryCards() {
        return Array.from(document.querySelectorAll("[data-gallery-manager-category]"));
    }

    function getGalleryCardDetails(card) {
        const title = card?.querySelector(".public-gallery-card__meta strong")?.textContent?.trim() || "Gallery image";
        const fileName = card?.querySelector(".public-gallery-card__meta span")?.textContent?.trim() || "Selected file";

        return { title, fileName };
    }

    function getGalleryImageFileName() {
        const selectedFile = galleryImageInput?.files?.[0];
        return selectedFile?.name?.trim() || "";
    }

    function getGalleryCategoryLabel() {
        return galleryCategoryFilter?.selectedOptions?.[0]?.textContent?.trim() || "Gallery";
    }

    function getGalleryCaptionValue() {
        return galleryCaptionInput?.value?.trim() || "";
    }

    function buildGalleryTitleFromFileName(fileName) {
        const titleFromFile = String(fileName || "")
            .replace(/\.[^.]+$/, "")
            .replace(/[_-]+/g, " ")
            .replace(/\s+/g, " ")
            .trim();

        return titleFromFile || "Gallery image";
    }

    function syncGalleryManager() {
        const galleryCards = getGalleryCards();
        const activeCategory = galleryCategoryFilter?.value.trim().toLowerCase() || "";
        let visibleCount = 0;

        galleryCards.forEach((card) => {
            const categories = String(card.dataset.galleryManagerCategory || "")
                .toLowerCase()
                .split(/\s+/)
                .filter(Boolean);
            const isVisible = !activeCategory || categories.includes(activeCategory);

            card.hidden = !isVisible;

            if (isVisible) {
                visibleCount += 1;
            }
        });

        if (galleryEmptyState) {
            galleryEmptyState.hidden = visibleCount > 0;
        }
    }

    function syncGallerySaveConfirmContent() {
        const selectedFileName = getGalleryImageFileName();
        const hasSelectedFile = selectedFileName !== "";
        const categoryLabel = getGalleryCategoryLabel();
        const captionValue = getGalleryCaptionValue();
        const galleryTitle = captionValue || buildGalleryTitleFromFileName(selectedFileName);

        if (gallerySaveConfirmModalText) {
            gallerySaveConfirmModalText.textContent = hasSelectedFile
                ? `Add "${galleryTitle}" to the ${categoryLabel} gallery?`
                : "Choose a gallery image first before saving changes.";
        }

        if (gallerySaveConfirmSummary) {
            gallerySaveConfirmSummary.innerHTML = hasSelectedFile
                ? `
                    <div class="gallery-delete-modal__target">
                        <strong>${escapeHtml(galleryTitle)}</strong>
                        <span>${escapeHtml(selectedFileName)}</span>
                    </div>
                    <div class="gallery-delete-modal__target">
                        <strong>Category</strong>
                        <span>${escapeHtml(categoryLabel)}</span>
                    </div>
                `
                : `
                    <div class="gallery-delete-modal__target">
                        <strong>No image selected</strong>
                        <span>Choose an image above, then save again.</span>
                    </div>
                `;
        }

        if (gallerySaveConfirmButton && !isGallerySubmitting) {
            gallerySaveConfirmButton.disabled = !hasSelectedFile;
            gallerySaveConfirmButton.textContent = "Yes, Add To Gallery";
        }

        return hasSelectedFile;
    }

    function openGallerySaveConfirm() {
        if (!galleryUploadForm || !galleryImageInput) {
            return;
        }

        if (!galleryUploadForm.reportValidity()) {
            return;
        }

        const hasSelectedFile = syncGallerySaveConfirmContent();

        if (!gallerySaveConfirmModal) {
            if (!hasSelectedFile) {
                galleryImageInput.reportValidity();
                galleryImageInput.focus();
                return;
            }

            const galleryTitle = getGalleryCaptionValue() || buildGalleryTitleFromFileName(getGalleryImageFileName());
            const shouldSubmit = window.confirm(`Add "${galleryTitle}" to the ${getGalleryCategoryLabel()} gallery?`);

            if (shouldSubmit) {
                submitGalleryUploadForm();
            }

            return;
        }

        gallerySaveConfirmModal.show();
    }

    function submitGalleryUploadForm() {
        if (!galleryUploadForm || isGallerySubmitting) {
            return;
        }

        isGallerySubmitting = true;
        gallerySaveButton?.setAttribute("disabled", "disabled");

        if (gallerySaveConfirmButton) {
            gallerySaveConfirmButton.disabled = true;
            gallerySaveConfirmButton.textContent = "Saving...";
        }

        gallerySaveConfirmModal?.hide();
        galleryUploadForm.submit();
    }

    function confirmGalleryDelete(card) {
        const { title, fileName } = getGalleryCardDetails(card);
        const galleryItemId = String(card?.dataset.galleryItemId || "").trim();

        pendingGalleryDeleteCard = card;
        pendingGalleryDeleteId = galleryItemId;

        if (!galleryDeleteModal || !galleryDeleteModalText || !galleryDeleteModalTargetTitle || !galleryDeleteModalTargetFile) {
            const confirmed = window.confirm(`Delete "${title}" from the gallery?`);

            if (confirmed) {
                if (galleryDeleteForm && galleryDeleteItemId && pendingGalleryDeleteId !== "") {
                    galleryDeleteItemId.value = pendingGalleryDeleteId;
                    galleryDeleteForm.submit();
                    return;
                }

                pendingGalleryDeleteCard?.remove();
                pendingGalleryDeleteCard = null;
                pendingGalleryDeleteId = "";
                syncGalleryManager();
            }

            return;
        }

        galleryDeleteModalText.textContent = `Delete "${title}" from the gallery list? This action removes the image entry from the page manager.`;
        galleryDeleteModalTargetTitle.textContent = title;
        galleryDeleteModalTargetFile.textContent = fileName;
        if (galleryDeleteConfirmButton) {
            galleryDeleteConfirmButton.disabled = false;
            galleryDeleteConfirmButton.textContent = "Yes, Delete Image";
        }
        galleryDeleteModal.show();
    }

    function getHeroImageFileName() {
        const selectedFile = heroImageInput?.files?.[0];
        return selectedFile?.name?.trim() || "";
    }

    function getServiceCardEditors() {
        return Array.from(serviceCardsForm?.querySelectorAll("[data-service-card-editor]") || []);
    }

    function syncServiceCardsConfirmContent() {
        const serviceCardEditors = getServiceCardEditors();
        const hasServiceCards = serviceCardEditors.length > 0;

        if (serviceCardsConfirmModalText) {
            serviceCardsConfirmModalText.textContent = hasServiceCards
                ? "Save these service card updates to the public page?"
                : "No service cards are available to save right now.";
        }

        if (serviceCardsConfirmSummary) {
            serviceCardsConfirmSummary.innerHTML = hasServiceCards
                ? serviceCardEditors.map((editor) => {
                    const serviceLabel = editor.querySelector(".public-service-editor__title")?.textContent?.trim() || "Service";
                    const serviceTitle = editor.querySelector('input[name*="[title]"]')?.value?.trim() || "Untitled service";
                    const selectedFileName = editor.querySelector('input[type="file"]')?.files?.[0]?.name?.trim() || "Keep current image";

                    return `
                        <div class="gallery-delete-modal__target">
                            <strong>${escapeHtml(serviceLabel)}: ${escapeHtml(serviceTitle)}</strong>
                            <span>${escapeHtml(selectedFileName)}</span>
                        </div>
                    `;
                }).join("")
                : `
                    <div class="gallery-delete-modal__target">
                        <strong>No service cards found</strong>
                        <span>Nothing to save right now.</span>
                    </div>
                `;
        }

        if (serviceCardsConfirmButton && !isServiceCardsSubmitting) {
            serviceCardsConfirmButton.disabled = !hasServiceCards;
            serviceCardsConfirmButton.textContent = "Yes, Save Services";
        }

        return hasServiceCards;
    }

    function syncHeroImageConfirmContent() {
        const selectedFileName = getHeroImageFileName();
        const hasSelectedFile = selectedFileName !== "";

        if (heroImageConfirmModalText) {
            heroImageConfirmModalText.textContent = hasSelectedFile
                ? "Save this new hero image to the public page?"
                : "Choose a hero image first before saving changes.";
        }

        if (heroImageConfirmTargetTitle) {
            heroImageConfirmTargetTitle.textContent = hasSelectedFile ? "Selected file" : "No image selected";
        }

        if (heroImageConfirmTargetFile) {
            heroImageConfirmTargetFile.textContent = hasSelectedFile
                ? selectedFileName
                : "Pick an image in the field above, then save again.";
        }

        if (heroImageConfirmButton && !isHeroImageSubmitting) {
            heroImageConfirmButton.disabled = !hasSelectedFile;
            heroImageConfirmButton.textContent = "Yes, Save Hero Image";
        }

        return hasSelectedFile;
    }

    function getContactDetailsValues() {
        return {
            serviceArea: contactServiceAreaInput?.value?.trim() || "",
            publicEmail: contactPublicEmailInput?.value?.trim() || "",
            primaryMobile: contactPrimaryMobileInput?.value?.trim() || "",
            businessHours: contactBusinessHoursInput?.value?.trim() || ""
        };
    }

    function syncContactDetailsConfirmContent() {
        const values = getContactDetailsValues();
        const hasCompleteContactDetails = Object.values(values).every((value) => value !== "");

        if (contactDetailsConfirmModalText) {
            contactDetailsConfirmModalText.textContent = hasCompleteContactDetails
                ? "Save these contact details to the public page?"
                : "Complete all contact fields first before saving changes.";
        }

        if (contactDetailsConfirmSummary) {
            contactDetailsConfirmSummary.innerHTML = hasCompleteContactDetails
                ? `
                    <div class="gallery-delete-modal__target">
                        <strong>Service Area</strong>
                        <span>${escapeHtml(values.serviceArea)}</span>
                    </div>
                    <div class="gallery-delete-modal__target">
                        <strong>Public Email</strong>
                        <span>${escapeHtml(values.publicEmail)}</span>
                    </div>
                    <div class="gallery-delete-modal__target">
                        <strong>Mobile Number</strong>
                        <span>${escapeHtml(values.primaryMobile)}</span>
                    </div>
                    <div class="gallery-delete-modal__target">
                        <strong>Business Hours</strong>
                        <span>${escapeHtml(values.businessHours)}</span>
                    </div>
                `
                : `
                    <div class="gallery-delete-modal__target">
                        <strong>Incomplete contact details</strong>
                        <span>Fill in all contact fields above, then save again.</span>
                    </div>
                `;
        }

        if (contactDetailsConfirmButton && !isContactDetailsSubmitting) {
            contactDetailsConfirmButton.disabled = !hasCompleteContactDetails;
            contactDetailsConfirmButton.textContent = "Yes, Save Contacts";
        }

        return hasCompleteContactDetails;
    }

    function openServiceCardsConfirm() {
        if (!serviceCardsForm) {
            return;
        }

        if (!serviceCardsForm.reportValidity()) {
            return;
        }

        const hasServiceCards = syncServiceCardsConfirmContent();

        if (!serviceCardsConfirmModal) {
            if (!hasServiceCards) {
                return;
            }

            const shouldSubmit = window.confirm("Save these service card updates to the public page?");

            if (shouldSubmit) {
                submitServiceCardsForm();
            }

            return;
        }

        serviceCardsConfirmModal.show();
    }

    function openHeroImageConfirm() {
        if (!heroImageForm || !heroImageInput) {
            return;
        }

        if (!heroImageForm.reportValidity()) {
            return;
        }

        const hasSelectedFile = syncHeroImageConfirmContent();

        if (!heroImageConfirmModal) {
            if (!hasSelectedFile) {
                heroImageInput.reportValidity();
                heroImageInput.focus();
                return;
            }

            const selectedFileName = getHeroImageFileName();
            const shouldSubmit = window.confirm(`Save "${selectedFileName}" as the hero image?`);

            if (shouldSubmit) {
                submitHeroImageForm();
            }

            return;
        }

        heroImageConfirmModal.show();
    }

    function openContactDetailsConfirm() {
        if (!contactDetailsForm) {
            return;
        }

        if (!contactDetailsForm.reportValidity()) {
            return;
        }

        const hasCompleteContactDetails = syncContactDetailsConfirmContent();

        if (!contactDetailsConfirmModal) {
            if (!hasCompleteContactDetails) {
                return;
            }

            const shouldSubmit = window.confirm("Save these contact details to the public page?");

            if (shouldSubmit) {
                submitContactDetailsForm();
            }

            return;
        }

        contactDetailsConfirmModal.show();
    }

    function syncSmsTemplatesConfirmContent() {
        const smsTemplateDrafts = collectSmsTemplateDrafts();
        const changedTemplates = smsTemplateDrafts.filter((template) => template.currentValue !== template.originalValue);
        const changedCount = changedTemplates.length;

        if (smsTemplatesConfirmModalText) {
            smsTemplatesConfirmModalText.textContent = changedCount > 0
                ? `Save ${changedCount} SMS template update${changedCount === 1 ? "" : "s"}?`
                : "Save these SMS templates without any text changes?";
        }

        if (smsTemplatesConfirmSummary) {
            smsTemplatesConfirmSummary.innerHTML = changedCount > 0
                ? changedTemplates.map((template) => `
                    <div class="gallery-delete-modal__target">
                        <strong>${escapeHtml(template.label)}</strong>
                        <span>${escapeHtml(summarizeSmsTemplateValue(template.currentValue))}</span>
                    </div>
                `).join("")
                : `
                    <div class="gallery-delete-modal__target">
                        <strong>No template changes detected</strong>
                        <span>The current SMS template text will stay the same after saving.</span>
                    </div>
                `;
        }

        if (smsTemplatesConfirmButton && !isSmsTemplatesSubmitting) {
            smsTemplatesConfirmButton.disabled = false;
            smsTemplatesConfirmButton.textContent = "Yes, Save Templates";
        }

        return changedCount;
    }

    function openSmsTemplatesConfirm() {
        if (!smsTemplatesForm) {
            return;
        }

        if (!smsTemplatesForm.reportValidity()) {
            return;
        }

        const changedCount = syncSmsTemplatesConfirmContent();

        if (!smsTemplatesConfirmModal) {
            const shouldSubmit = window.confirm(
                changedCount > 0
                    ? `Save ${changedCount} SMS template update${changedCount === 1 ? "" : "s"}?`
                    : "Save these SMS templates?"
            );

            if (shouldSubmit) {
                submitSmsTemplatesForm();
            }

            return;
        }

        smsTemplatesConfirmModal.show();
    }

    function buildPackageDownPaymentSubmissionCatalog() {
        if (!packageDownPaymentForm || !catalogApi) {
            return null;
        }

        const toggleMap = new Map(
            Array.from(packageDownPaymentForm.querySelectorAll("[data-package-down-payment-toggle]"))
                .map((input) => [input.dataset.packageId || "", input.checked])
        );
        const inputMap = new Map(
            Array.from(packageDownPaymentForm.querySelectorAll("[data-package-down-payment-input]"))
                .map((input) => [input.dataset.packageId || "", input.value.trim()])
        );
        const tierInputMap = new Map();

        Array.from(packageDownPaymentForm.querySelectorAll("[data-package-down-payment-tier-input]"))
            .forEach((input) => {
                const packageId = String(input.dataset.packageId || "").trim();
                const tierLabel = String(input.dataset.tierLabel || "").trim();

                if (!packageId || !tierLabel) {
                    return;
                }

                if (!tierInputMap.has(packageId)) {
                    tierInputMap.set(packageId, new Map());
                }

                tierInputMap.get(packageId).set(tierLabel, input.value.trim());
            });

        let invalidTierDetails = null;
        const invalidPackage = catalogApi.getCatalog().find((packageItem) => {
            const allowDownPayment = toggleMap.has(packageItem.id)
                ? toggleMap.get(packageItem.id)
                : Boolean(packageItem.allowDownPayment);
            const pricingTiers = Array.isArray(packageItem.pricingTiers)
                ? packageItem.pricingTiers.filter((tier) => String(tier?.label || "").trim())
                : [];
            const hasMultiplePricingTiers = pricingTiers.length > 1;

            if (!allowDownPayment) {
                return false;
            }

            if (hasMultiplePricingTiers) {
                const tierValues = tierInputMap.get(packageItem.id) || new Map();
                const missingTier = pricingTiers.find((tier) => {
                    const tierLabel = String(tier?.label || "").trim();
                    const downPaymentAmount = tierValues.has(tierLabel)
                        ? tierValues.get(tierLabel)
                        : catalogApi.getPackageDownPaymentAmount(packageItem, tierLabel);

                    return !String(downPaymentAmount || "").trim();
                });

                if (missingTier) {
                    invalidTierDetails = {
                        packageId: packageItem.id,
                        tierLabel: String(missingTier.label || "").trim()
                    };
                    return true;
                }

                return false;
            }

            const downPaymentAmount = inputMap.has(packageItem.id)
                ? inputMap.get(packageItem.id)
                : (packageItem.downPaymentAmount || "");

            return !String(downPaymentAmount || "").trim();
        });

        if (invalidPackage) {
            const invalidField = invalidTierDetails
                ? Array.from(packageDownPaymentForm.querySelectorAll("[data-package-down-payment-tier-input]"))
                    .find((input) => (
                        input.dataset.packageId === invalidTierDetails.packageId
                        && input.dataset.tierLabel === invalidTierDetails.tierLabel
                    ))
                : Array.from(packageDownPaymentForm.querySelectorAll("[data-package-down-payment-input]"))
                    .find((input) => input.dataset.packageId === invalidPackage.id);

            invalidField?.focus();
            setPackageDownPaymentFeedback(
                invalidTierDetails
                    ? `Enter a down payment amount for "${invalidPackage.name}" (${invalidTierDetails.tierLabel}) or turn off its down payment toggle.`
                    : `Enter a down payment amount for "${invalidPackage.name}" or turn off its down payment toggle.`,
                "error"
            );
            return null;
        }

        return catalogApi.getCatalog().map((packageItem) => ({
            ...packageItem,
            allowDownPayment: toggleMap.has(packageItem.id)
                ? toggleMap.get(packageItem.id)
                : Boolean(packageItem.allowDownPayment),
            downPaymentAmount: (() => {
                const pricingTiers = Array.isArray(packageItem.pricingTiers)
                    ? packageItem.pricingTiers.filter((tier) => String(tier?.label || "").trim())
                    : [];

                if (pricingTiers.length <= 1) {
                    return inputMap.has(packageItem.id)
                        ? inputMap.get(packageItem.id)
                        : catalogApi.getPackageDownPaymentAmount(packageItem);
                }

                return inputMap.has(packageItem.id)
                    ? inputMap.get(packageItem.id)
                    : (packageItem.downPaymentAmount || "");
            })(),
            downPaymentTiers: (() => {
                const pricingTiers = Array.isArray(packageItem.pricingTiers)
                    ? packageItem.pricingTiers.filter((tier) => String(tier?.label || "").trim())
                    : [];

                if (pricingTiers.length === 1) {
                    const singleAmount = inputMap.has(packageItem.id)
                        ? inputMap.get(packageItem.id)
                        : catalogApi.getPackageDownPaymentAmount(packageItem);

                    return [{
                        label: String(pricingTiers[0]?.label || "").trim(),
                        amount: singleAmount
                    }];
                }

                if (pricingTiers.length > 1) {
                    return pricingTiers.map((tier) => {
                        const tierLabel = String(tier?.label || "").trim();
                        const tierValues = tierInputMap.get(packageItem.id) || new Map();

                        return {
                            label: tierLabel,
                            amount: tierValues.has(tierLabel)
                                ? tierValues.get(tierLabel)
                                : catalogApi.getPackageDownPaymentAmount(packageItem, tierLabel)
                        };
                    });
                }

                return [];
            })()
        }));
    }

    function buildPackageDownPaymentPreview(nextCatalog) {
        return (Array.isArray(nextCatalog) ? nextCatalog : [])
            .filter((packageItem) => Boolean(packageItem?.allowDownPayment))
            .map((packageItem) => {
                const tierLines = Array.isArray(packageItem?.downPaymentTiers) && packageItem.downPaymentTiers.length
                    ? packageItem.downPaymentTiers
                        .filter((tier) => String(tier?.amount || "").trim() !== "")
                        .map((tier) => `${String(tier?.label || "").trim()}: ${String(tier?.amount || "").trim()}`)
                        .join(" | ")
                    : "";
                const amountLine = tierLines || String(packageItem?.downPaymentAmount || "").trim() || "No amount";

                return `
                    <div class="gallery-delete-modal__target">
                        <strong>${escapeHtml(packageItem.name || "Service")}</strong>
                        <span>${escapeHtml(amountLine)}</span>
                    </div>
                `;
            }).join("");
    }

    function openPackageDownPaymentConfirm(nextCatalog) {
        const enabledCount = Array.isArray(nextCatalog)
            ? nextCatalog.filter((packageItem) => Boolean(packageItem?.allowDownPayment)).length
            : 0;
        const summaryMarkup = buildPackageDownPaymentPreview(nextCatalog);

        pendingPackageDownPaymentCatalog = nextCatalog;

        if (packageDownPaymentConfirmModalText) {
            packageDownPaymentConfirmModalText.textContent = enabledCount > 0
                ? `Save ${enabledCount} service rule${enabledCount === 1 ? "" : "s"} with down payment enabled?`
                : "Save these service rules with no down payment enabled?";
        }

        if (packageDownPaymentConfirmSummary) {
            packageDownPaymentConfirmSummary.innerHTML = summaryMarkup || `
                <div class="gallery-delete-modal__target">
                    <strong>No down payment enabled</strong>
                    <span>All services will stay full payment only.</span>
                </div>
            `;
        }

        if (packageDownPaymentConfirmButton && !isPackageDownPaymentSubmitting) {
            packageDownPaymentConfirmButton.disabled = false;
            packageDownPaymentConfirmButton.textContent = "Yes, Save Rules";
        }

        if (!packageDownPaymentConfirmModal) {
            const shouldSubmit = window.confirm(
                enabledCount > 0
                    ? `Save ${enabledCount} service rule${enabledCount === 1 ? "" : "s"} with down payment enabled?`
                    : "Save these service rules?"
            );

            if (shouldSubmit) {
                requestPackageDownPaymentSubmit(true);
            }

            return;
        }

        packageDownPaymentConfirmModal.show();
    }

    function submitServiceCardsForm() {
        if (!serviceCardsForm || isServiceCardsSubmitting) {
            return;
        }

        isServiceCardsSubmitting = true;
        serviceCardsSaveButton?.setAttribute("disabled", "disabled");

        if (serviceCardsConfirmButton) {
            serviceCardsConfirmButton.disabled = true;
            serviceCardsConfirmButton.textContent = "Saving...";
        }

        serviceCardsConfirmModal?.hide();
        serviceCardsForm.submit();
    }

    function submitHeroImageForm() {
        if (!heroImageForm || isHeroImageSubmitting) {
            return;
        }

        isHeroImageSubmitting = true;
        heroImageSaveButton?.setAttribute("disabled", "disabled");

        if (heroImageConfirmButton) {
            heroImageConfirmButton.disabled = true;
            heroImageConfirmButton.textContent = "Saving...";
        }

        heroImageConfirmModal?.hide();
        heroImageForm.submit();
    }

    function submitContactDetailsForm() {
        if (!contactDetailsForm || isContactDetailsSubmitting) {
            return;
        }

        isContactDetailsSubmitting = true;
        contactDetailsSaveButton?.setAttribute("disabled", "disabled");

        if (contactDetailsConfirmButton) {
            contactDetailsConfirmButton.disabled = true;
            contactDetailsConfirmButton.textContent = "Saving...";
        }

        contactDetailsConfirmModal?.hide();
        contactDetailsForm.submit();
    }

    function submitSmsTemplatesForm() {
        if (!smsTemplatesForm || isSmsTemplatesSubmitting) {
            return;
        }

        isSmsTemplatesSubmitting = true;
        smsTemplatesSaveButton?.setAttribute("disabled", "disabled");

        if (smsTemplatesConfirmButton) {
            smsTemplatesConfirmButton.disabled = true;
            smsTemplatesConfirmButton.textContent = "Saving...";
        }

        smsTemplatesConfirmModal?.hide();
        smsTemplatesForm.submit();
    }

    function setPaymentSettingsFeedback(message = "", state = "") {
        if (!paymentSettingsFeedback) {
            return;
        }

        paymentSettingsFeedback.textContent = message;
        paymentSettingsFeedback.className = "package-down-payment-feedback";

        if (state) {
            paymentSettingsFeedback.classList.add(`is-${state}`);
        }
    }

    function setPackageDownPaymentFeedback(message = "", state = "") {
        if (!packageDownPaymentFeedback) {
            return;
        }

        packageDownPaymentFeedback.textContent = message;
        packageDownPaymentFeedback.className = "package-down-payment-feedback";

        if (state) {
            packageDownPaymentFeedback.classList.add(`is-${state}`);
        }
    }

    function requestPackageDownPaymentSubmit(skipConfirm = false) {
        if (!packageDownPaymentForm) {
            return;
        }

        if (!skipConfirm) {
            const preparedCatalog = buildPackageDownPaymentSubmissionCatalog();

            if (!preparedCatalog) {
                return;
            }

            openPackageDownPaymentConfirm(preparedCatalog);
            return;
        }

        if (typeof packageDownPaymentForm.requestSubmit === "function") {
            packageDownPaymentForm.requestSubmit();
            return;
        }

        const submitEvent = new Event("submit", {
            bubbles: true,
            cancelable: true
        });

        packageDownPaymentForm.dispatchEvent(submitEvent);
    }

    function setPackageDownPaymentSaveButtonLabel(label) {
        if (packageDownPaymentSaveButtonLabel) {
            packageDownPaymentSaveButtonLabel.textContent = label;
            return;
        }

        if (packageDownPaymentSaveButton) {
            packageDownPaymentSaveButton.textContent = label;
        }
    }

    async function postJson(url, payload) {
        const response = await fetch(url, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json"
            },
            credentials: "same-origin",
            body: JSON.stringify(payload || {})
        });
        const rawText = await response.text();
        let parsedPayload = {};

        try {
            parsedPayload = rawText ? JSON.parse(rawText) : {};
        } catch (error) {
            parsedPayload = {};
        }

        if (!response.ok || parsedPayload.ok === false) {
            throw new Error(parsedPayload.message || "Request failed.");
        }

        return parsedPayload;
    }

    function renderPaymentSettings() {
        if (!paymentSettingsApi) {
            return;
        }

        const settings = paymentSettingsApi.getSettings();

        if (paymentGatewayField) {
            paymentGatewayField.value = settings.paymentGateway;
        }

        if (paymentMethodField) {
            paymentMethodField.value = `${settings.activeMethod} only`;
        }

        if (paymentAllowFullPaymentField) {
            paymentAllowFullPaymentField.value = settings.allowFullPayment ? "yes" : "no";
        }

        if (paymentBalanceDueRuleField) {
            paymentBalanceDueRuleField.value = settings.balanceDueRule;
        }

        if (paymentReceiptRequirementField) {
            paymentReceiptRequirementField.value = settings.receiptRequirement;
        }

        if (paymentConfirmationRuleField) {
            paymentConfirmationRuleField.value = settings.confirmationRule;
        }

        if (paymentAcceptedWalletsLabelField) {
            paymentAcceptedWalletsLabelField.value = settings.acceptedWalletsLabel;
        }

        if (paymentSupportMobileField) {
            paymentSupportMobileField.value = settings.supportMobile;
        }

        if (paymentInstructionTextField) {
            paymentInstructionTextField.value = settings.instructionText;
        }
    }

    function renderPackageDownPaymentManager() {
        if (!packageDownPaymentList) {
            return;
        }

        if (!catalogApi) {
            packageDownPaymentList.innerHTML = '<p class="package-down-payment-empty">Package catalog unavailable right now.</p>';
            return;
        }

        const catalog = catalogApi.getCatalog();

        if (!catalog.length) {
            packageDownPaymentList.innerHTML = '<p class="package-down-payment-empty">No services available yet. Add a package first, then set its payment rule here.</p>';
            return;
        }

        packageDownPaymentList.innerHTML = catalog.map((packageItem) => {
            const statusMeta = catalogApi.STATUS_META[packageItem.status] || catalogApi.STATUS_META.review;
            const groupMeta = catalogApi.GROUPS[packageItem.group] || catalogApi.GROUPS["per-head"];
            const allowDownPayment = Boolean(packageItem.allowDownPayment);
            const pricingTiers = Array.isArray(packageItem.pricingTiers)
                ? packageItem.pricingTiers.filter((tier) => String(tier?.label || "").trim() && String(tier?.price || "").trim())
                : [];
            const hasMultiplePricingTiers = pricingTiers.length > 1;
            const amountFields = hasMultiplePricingTiers
                ? `
                    <div class="package-down-payment-card__tier-list">
                        ${pricingTiers.map((tier) => `
                            <label class="package-down-payment-card__field package-down-payment-card__tier-field">
                                <span class="settings-field__label">${escapeHtml(tier.label)}</span>
                                <input
                                    class="settings-input"
                                    type="text"
                                    data-package-down-payment-tier-input
                                    data-package-id="${escapeHtml(packageItem.id)}"
                                    data-tier-label="${escapeHtml(tier.label)}"
                                    value="${escapeHtml(catalogApi.getPackageDownPaymentAmount(packageItem, tier.label))}"
                                    placeholder="Enter amount"
                                    ${allowDownPayment ? "" : "disabled"}
                                >
                                <span class="package-down-payment-card__tier-note">${escapeHtml(tier.price)}</span>
                            </label>
                        `).join("")}
                    </div>
                `
                : `
                    <label class="package-down-payment-card__field">
                        <span class="settings-field__label">Down Payment</span>
                        <input class="settings-input" type="text" data-package-down-payment-input data-package-id="${escapeHtml(packageItem.id)}" value="${escapeHtml(catalogApi.getPackageDownPaymentAmount(packageItem) || "")}" placeholder="Enter amount"${allowDownPayment ? "" : " disabled"}>
                    </label>
                `;

            return `
                <article class="package-down-payment-card${allowDownPayment ? "" : " is-full-payment"}" data-package-payment-card>
                    <div class="package-down-payment-card__copy">
                        <div class="package-down-payment-card__header">
                            <strong>${escapeHtml(packageItem.name)}</strong>
                            <span class="status-pill status-pill--${escapeHtml(statusMeta.pillClass)}">${escapeHtml(statusMeta.label)}</span>
                        </div>
                        <div class="package-down-payment-card__details">
                            <span>${escapeHtml(groupMeta.shortLabel)} | ${escapeHtml(packageItem.category)}</span>
                            <span class="package-down-payment-card__meta">${escapeHtml(packageItem.rateLabel)}</span>
                        </div>
                    </div>
                    <div class="package-down-payment-card__controls">
                        <label class="package-down-payment-card__toggle">
                            <input class="package-down-payment-card__toggle-input" type="checkbox" data-package-down-payment-toggle data-package-id="${escapeHtml(packageItem.id)}"${allowDownPayment ? " checked" : ""}>
                            <span class="package-down-payment-card__toggle-copy">
                                <strong>Allow Down Payment</strong>
                            </span>
                        </label>
                        ${amountFields}
                    </div>
                </article>
            `;
        }).join("");
    }

    function syncPackagePaymentCard(toggle) {
        const packageCard = toggle?.closest("[data-package-payment-card]");
        const packageFields = Array.from(
            packageCard?.querySelectorAll("[data-package-down-payment-input], [data-package-down-payment-tier-input]") || []
        );
        const allowDownPayment = Boolean(toggle?.checked);

        if (!packageCard || !packageFields.length) {
            return;
        }

        packageCard.classList.toggle("is-full-payment", !allowDownPayment);
        packageFields.forEach((field) => {
            field.disabled = !allowDownPayment;
        });
    }

    function isMobileSettingsLayout() {
        return mobileSettingsMedia.matches;
    }

    function getMobileSettingsView() {
        const mobileView = pageBody?.dataset.mobileSettingsView || "";
        if (mobileView === "detail") {
            return mobileView;
        }

        return "hub";
    }

    function getActiveSettingsSectionId() {
        return String(pageBody?.dataset.activeSettingsSection || "").trim();
    }

    function syncMobileSettingsBackLabels() {
        if (!settingsBackLabels.length || !isMobileSettingsLayout()) {
            return;
        }

        const backLabel = getMobileSettingsView() === "detail"
            ? "Back to settings menu"
            : "Back to profile menu";

        settingsBackLabels.forEach((label) => {
            label.textContent = backLabel;
        });
    }

    function setMobileSettingsView(view, sectionId = "") {
        if (!pageBody || !isMobileSettingsLayout()) {
            return;
        }

        pageBody.dataset.mobileSettingsView = view === "detail" ? "detail" : "hub";
        syncMobileSettingsBackLabels();
    }

    function setSettingsPanelExpanded(isExpanded) {
        if (!settingsPanel) {
            return;
        }

        const shouldCollapse = !isExpanded && isMobileSettingsLayout();
        settingsPanel.dataset.mobileState = shouldCollapse ? "collapsed" : "expanded";
        settingsPanel.setAttribute("aria-hidden", shouldCollapse ? "true" : "false");
    }

    function collapseSettingsPanel({ scroll = false } = {}) {
        if (!isMobileSettingsLayout()) {
            return;
        }

        setMobileSettingsView("hub");
        setSettingsPanelExpanded(false);

        if (scroll) {
            window.requestAnimationFrame(() => {
                settingsHub?.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });
            });
        }
    }

    function showSection(sectionId) {
        sections.forEach((section) => {
            const shouldShow = sectionId !== "" && section.dataset.settingsSection === sectionId;
            section.hidden = !shouldShow;
        });

        filterButtons.forEach((button) => {
            const isActive = sectionId !== "" && button.dataset.settingsFilter === sectionId;
            button.classList.toggle("is-active", isActive);
            button.setAttribute("aria-pressed", String(isActive));
        });

        if (!sectionId) {
            return;
        }

        if (window.location.hash !== `#${sectionId}`) {
            const currentUrl = new URL(window.location.href);
            currentUrl.hash = `#${sectionId}`;
            window.history.replaceState(null, "", `${currentUrl.pathname}${currentUrl.search}${currentUrl.hash}`);
        }
    }

    if (filterButtons.length && sections.length) {
        const allowedSectionIds = filterButtons
            .map((button) => button.dataset.settingsFilter)
            .filter(Boolean);
        const defaultSectionId = filterButtons[0].dataset.settingsFilter;

        function getHashSectionId() {
            return window.location.hash.replace("#", "");
        }

        function openSettingsSection(sectionId, { scroll = false } = {}) {
            if (!allowedSectionIds.includes(sectionId)) {
                return;
            }

            if (isMobileSettingsLayout()) {
                setMobileSettingsView("detail", sectionId);
            }

            setSettingsPanelExpanded(true);
            showSection(sectionId);

            if (scroll) {
                window.requestAnimationFrame(() => {
                    settingsPanel?.scrollIntoView({
                        behavior: "smooth",
                        block: "start"
                    });
                });
            }
        }

        function syncSettingsPanelState() {
            const hashSectionId = getHashSectionId();
            const activeSectionId = getActiveSettingsSectionId();

            if (isMobileSettingsLayout()) {
                if (allowedSectionIds.includes(hashSectionId)) {
                    openSettingsSection(hashSectionId);
                    return;
                }

                if (allowedSectionIds.includes(activeSectionId)) {
                    openSettingsSection(activeSectionId);
                    return;
                }

                setMobileSettingsView("hub");
                setSettingsPanelExpanded(false);
                showSection("");
                return;
            }

            if (allowedSectionIds.includes(hashSectionId)) {
                openSettingsSection(hashSectionId);
                return;
            }

            if (allowedSectionIds.includes(activeSectionId)) {
                openSettingsSection(activeSectionId);
                return;
            }

            setSettingsPanelExpanded(true);
            if (defaultSectionId) {
                showSection(defaultSectionId);
            }
        }

        syncSettingsPanelState();

        filterButtons.forEach((button) => {
            button.addEventListener("click", () => {
                const sectionId = button.dataset.settingsFilter;

                if (!sectionId) {
                    return;
                }

                openSettingsSection(sectionId);
            });
        });

        settingsOpenLinks.forEach((link) => {
            link.addEventListener("click", (event) => {
                const sectionId = link.dataset.settingsOpen || "";

                if (!allowedSectionIds.includes(sectionId)) {
                    return;
                }

                event.preventDefault();
                openSettingsSection(sectionId, { scroll: true });
            });
        });

        settingsMenuLinks.forEach((link) => {
            link.addEventListener("click", (event) => {
                const sectionId = link.dataset.settingsMenuItem || "";

                if (!allowedSectionIds.includes(sectionId)) {
                    return;
                }

                event.preventDefault();
                openSettingsSection(sectionId, { scroll: true });
            });
        });

        settingsBackButtons.forEach((button) => {
            button.addEventListener("click", () => {
                collapseSettingsPanel({ scroll: true });
            });
        });

        window.addEventListener("hashchange", () => {
            syncSettingsPanelState();
        });

        mobileSettingsMedia.addEventListener?.("change", () => {
            syncSettingsPanelState();
        });
    } else {
        setSettingsPanelExpanded(true);
    }

    syncMobileSettingsBackLabels();

    adminMobileChangeButton?.addEventListener("click", () => {
        openAdminMobileChangeModal();
    });

    adminMobileChangeModalElement?.addEventListener("shown.bs.modal", () => {
        adminMobileDraftInput?.focus();

        if (adminMobileDraftInput?.value) {
            adminMobileDraftInput.select();
        }
    });

    adminMobileDraftInput?.addEventListener("input", () => {
        adminMobileDraftInput.value = formatMobileInputValue(adminMobileDraftInput.value);
        adminMobileDraftInput.setCustomValidity("");
        handleAdminMobileDraftInputChange();
    });

    adminMobileDraftInput?.addEventListener("change", () => {
        adminMobileDraftInput.value = formatMobileForDisplay(adminMobileDraftInput.value);
        adminMobileDraftInput.setCustomValidity("");
        handleAdminMobileDraftInputChange();
    });

    adminMobileOtpInput?.addEventListener("input", () => {
        adminMobileOtpInput.value = getDigits(adminMobileOtpInput.value).slice(0, 6);
    });

    adminMobileOtpInput?.addEventListener("keydown", (event) => {
        if (event.key !== "Enter") {
            return;
        }

        event.preventDefault();
        verifyAdminMobileOtp();
    });

    adminMobileOtpSendButton?.addEventListener("click", () => {
        requestAdminMobileOtp(false);
    });

    adminMobileOtpResendButton?.addEventListener("click", () => {
        requestAdminMobileOtp(true);
    });

    adminMobileOtpVerifyButton?.addEventListener("click", () => {
        verifyAdminMobileOtp();
    });

    adminMobileApplyButton?.addEventListener("click", () => {
        applyVerifiedAdminMobile();
    });

    adminAccountForm?.addEventListener("submit", (event) => {
        if (isAdminAccountSubmitting) {
            return;
        }

        const passwordValidation = validateAdminAccountPasswordInputs(true);

        if (!passwordValidation.isValid) {
            event.preventDefault();
            return;
        }

        if (!isAdminMobileChanged()) {
            event.preventDefault();
            openAdminAccountConfirm();
            return;
        }

        if (getAdminCurrentMobile() === adminMobileOtpVerifiedMobile) {
            event.preventDefault();
            openAdminAccountConfirm();
            return;
        }

        event.preventDefault();
        setAdminAccountActionNote("Verify the OTP sent to the new mobile number before saving the admin account.", "warning");
        syncAdminMobileFieldState();
        openAdminMobileChangeModal();
        setAdminMobileOtpFeedback("error", "Verify the OTP sent to the new mobile number before saving.");

        if (getAdminDraftMobile() === adminMobileOtpRequestedMobile) {
            adminMobileOtpInput?.focus();
            return;
        }

        adminMobileDraftInput?.focus();
    });

    if (adminMobileInput?.dataset.mobileVerified === "1") {
        adminMobileOtpVerifiedMobile = getAdminCurrentMobile();
    }

    adminAccountConfirmButton?.addEventListener("click", () => {
        submitAdminAccountForm();
    });

    adminAccountConfirmModalElement?.addEventListener("show.bs.modal", () => {
        syncAdminAccountConfirmContent();
    });

    adminAccountConfirmModalElement?.addEventListener("hidden.bs.modal", () => {
        if (isAdminAccountSubmitting) {
            return;
        }

        syncAdminAccountConfirmContent();
    });

    syncAdminAccountActionNoteFlash();
    syncAdminMobileFieldState();
    syncAdminMobileOtpPanel();

    gallerySaveButton?.addEventListener("click", () => {
        openGallerySaveConfirm();
    });

    galleryUploadForm?.addEventListener("submit", (event) => {
        if (isGallerySubmitting) {
            return;
        }

        event.preventDefault();
        openGallerySaveConfirm();
    });

    gallerySaveConfirmButton?.addEventListener("click", () => {
        if (!galleryUploadForm) {
            return;
        }

        submitGalleryUploadForm();
    });

    gallerySaveConfirmModalElement?.addEventListener("show.bs.modal", () => {
        syncGallerySaveConfirmContent();
    });

    gallerySaveConfirmModalElement?.addEventListener("hidden.bs.modal", () => {
        if (isGallerySubmitting) {
            return;
        }

        syncGallerySaveConfirmContent();
    });

    galleryCategoryFilter?.addEventListener("change", () => {
        syncGalleryManager();
    });

    galleryList?.addEventListener("click", (event) => {
        const deleteButton = event.target.closest("[data-gallery-delete]");

        if (!deleteButton) {
            return;
        }

        const galleryCard = deleteButton.closest("[data-gallery-manager-category]");

        if (!galleryCard) {
            return;
        }

        confirmGalleryDelete(galleryCard);
    });

    galleryDeleteConfirmButton?.addEventListener("click", () => {
        if (!pendingGalleryDeleteCard) {
            return;
        }

        if (galleryDeleteForm && galleryDeleteItemId && pendingGalleryDeleteId !== "") {
            galleryDeleteConfirmButton.disabled = true;
            galleryDeleteConfirmButton.textContent = "Deleting...";
            galleryDeleteItemId.value = pendingGalleryDeleteId;
            galleryDeleteModal?.hide();
            galleryDeleteForm.submit();
            return;
        }

        pendingGalleryDeleteCard.remove();
        pendingGalleryDeleteCard = null;
        pendingGalleryDeleteId = "";
        galleryDeleteModal?.hide();
        syncGalleryManager();
    });

    galleryDeleteModalElement?.addEventListener("hidden.bs.modal", () => {
        pendingGalleryDeleteCard = null;
        pendingGalleryDeleteId = "";

        if (galleryDeleteConfirmButton) {
            galleryDeleteConfirmButton.disabled = false;
            galleryDeleteConfirmButton.textContent = "Yes, Delete Image";
        }
    });

    serviceCardsSaveButton?.addEventListener("click", () => {
        openServiceCardsConfirm();
    });

    serviceCardsForm?.addEventListener("submit", (event) => {
        if (isServiceCardsSubmitting) {
            return;
        }

        event.preventDefault();
        openServiceCardsConfirm();
    });

    serviceCardsConfirmButton?.addEventListener("click", () => {
        if (!serviceCardsForm) {
            return;
        }

        submitServiceCardsForm();
    });

    serviceCardsConfirmModalElement?.addEventListener("show.bs.modal", () => {
        syncServiceCardsConfirmContent();
    });

    serviceCardsConfirmModalElement?.addEventListener("hidden.bs.modal", () => {
        if (isServiceCardsSubmitting) {
            return;
        }

        syncServiceCardsConfirmContent();
    });

    smsTemplatesSaveButton?.addEventListener("click", () => {
        openSmsTemplatesConfirm();
    });

    smsTemplatesForm?.addEventListener("submit", (event) => {
        if (isSmsTemplatesSubmitting) {
            return;
        }

        event.preventDefault();
        openSmsTemplatesConfirm();
    });

    smsTemplatesConfirmButton?.addEventListener("click", () => {
        if (!smsTemplatesForm) {
            return;
        }

        submitSmsTemplatesForm();
    });

    smsTemplatesConfirmModalElement?.addEventListener("show.bs.modal", () => {
        syncSmsTemplatesConfirmContent();
    });

    smsTemplatesConfirmModalElement?.addEventListener("hidden.bs.modal", () => {
        if (isSmsTemplatesSubmitting) {
            return;
        }

        syncSmsTemplatesConfirmContent();
    });

    heroImageSaveButton?.addEventListener("click", () => {
        if (!heroImageConfirmModal) {
            openHeroImageConfirm();
        }
    });

    heroImageForm?.addEventListener("submit", (event) => {
        if (isHeroImageSubmitting) {
            return;
        }

        event.preventDefault();
        openHeroImageConfirm();
    });

    heroImageConfirmButton?.addEventListener("click", () => {
        if (!heroImageForm) {
            return;
        }

        submitHeroImageForm();
    });

    heroImageConfirmModalElement?.addEventListener("show.bs.modal", () => {
        syncHeroImageConfirmContent();
    });

    heroImageConfirmModalElement?.addEventListener("hidden.bs.modal", () => {
        if (isHeroImageSubmitting) {
            return;
        }

        syncHeroImageConfirmContent();
    });

    contactDetailsSaveButton?.addEventListener("click", () => {
        openContactDetailsConfirm();
    });

    contactDetailsForm?.addEventListener("submit", (event) => {
        if (isContactDetailsSubmitting) {
            return;
        }

        event.preventDefault();
        openContactDetailsConfirm();
    });

    contactDetailsConfirmButton?.addEventListener("click", () => {
        if (!contactDetailsForm) {
            return;
        }

        submitContactDetailsForm();
    });

    contactDetailsConfirmModalElement?.addEventListener("show.bs.modal", () => {
        syncContactDetailsConfirmContent();
    });

    contactDetailsConfirmModalElement?.addEventListener("hidden.bs.modal", () => {
        if (isContactDetailsSubmitting) {
            return;
        }

        syncContactDetailsConfirmContent();
    });

    packageDownPaymentConfirmModalElement?.addEventListener("hidden.bs.modal", () => {
        if (isPackageDownPaymentSubmitting) {
            return;
        }

        pendingPackageDownPaymentCatalog = null;

        if (packageDownPaymentConfirmButton) {
            packageDownPaymentConfirmButton.disabled = false;
            packageDownPaymentConfirmButton.textContent = "Yes, Save Rules";
        }
    });

    paymentSettingsForm?.addEventListener("input", () => {
        setPaymentSettingsFeedback("");
    });

    paymentSettingsForm?.addEventListener("submit", (event) => {
        event.preventDefault();

        if (!paymentSettingsApi) {
            return;
        }

        const nextSettings = paymentSettingsApi.saveSettings({
            paymentGateway: paymentGatewayField?.value,
            activeMethod: "PayMongo QRPh",
            acceptedWalletsLabel: paymentAcceptedWalletsLabelField?.value,
            allowFullPayment: paymentAllowFullPaymentField?.value !== "no",
            balanceDueRule: paymentBalanceDueRuleField?.value,
            receiptRequirement: paymentReceiptRequirementField?.value,
            confirmationRule: paymentConfirmationRuleField?.value,
            supportMobile: paymentSupportMobileField?.value,
            instructionText: paymentInstructionTextField?.value
        });

        renderPaymentSettings(nextSettings);
        setPaymentSettingsFeedback("PayMongo payment settings saved successfully.", "success");
    });

    packageDownPaymentForm?.addEventListener("input", (event) => {
        pendingPackageDownPaymentCatalog = null;
        setPackageDownPaymentFeedback("");
        setPackageDownPaymentSaveButtonLabel("Save Service Rules");

        const toggle = event.target.closest("[data-package-down-payment-toggle]");

        if (toggle) {
            syncPackagePaymentCard(toggle);
        }
    });

    packageDownPaymentSaveButton?.addEventListener("click", (event) => {
        event.preventDefault();
        requestPackageDownPaymentSubmit();
    });

    packageDownPaymentConfirmButton?.addEventListener("click", () => {
        if (!pendingPackageDownPaymentCatalog || isPackageDownPaymentSubmitting) {
            return;
        }

        if (packageDownPaymentConfirmButton) {
            packageDownPaymentConfirmButton.disabled = true;
            packageDownPaymentConfirmButton.textContent = "Saving...";
        }

        requestPackageDownPaymentSubmit(true);
        packageDownPaymentConfirmModal?.hide();
    });

    packageDownPaymentForm?.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (!catalogApi || isPackageDownPaymentSubmitting) {
            return;
        }

        if (!pendingPackageDownPaymentCatalog) {
            requestPackageDownPaymentSubmit();
            return;
        }
        const nextCatalog = pendingPackageDownPaymentCatalog || buildPackageDownPaymentSubmissionCatalog();

        if (!nextCatalog) {
            return;
        }

        try {
            isPackageDownPaymentSubmitting = true;
            packageDownPaymentSaveButton?.setAttribute("disabled", "disabled");
            setPackageDownPaymentSaveButtonLabel("Saving...");
            setPackageDownPaymentFeedback("Saving service rules...", "info");

            const responsePayload = await postJson("api/packages/save-service-rules.php", {
                packages: nextCatalog
            });
            const savedCatalog = Array.isArray(responsePayload.catalog) && responsePayload.catalog.length
                ? responsePayload.catalog
                : nextCatalog;

            catalogApi.saveCatalog(savedCatalog);
            setPackageDownPaymentFeedback(responsePayload.message || "Service down payment rules saved successfully.", "success");
            setPackageDownPaymentSaveButtonLabel("Saved");
        } catch (error) {
            setPackageDownPaymentFeedback(
                error?.message || "Service down payment rules could not be saved right now.",
                "error"
            );
            setPackageDownPaymentSaveButtonLabel("Save Service Rules");
        } finally {
            pendingPackageDownPaymentCatalog = null;
            isPackageDownPaymentSubmitting = false;
            packageDownPaymentSaveButton?.removeAttribute("disabled");
        }
    });

    if (catalogApi?.PACKAGE_CHANGE_EVENT) {
        window.addEventListener(catalogApi.PACKAGE_CHANGE_EVENT, () => {
            renderPackageDownPaymentManager();
        });
    }

    if (paymentSettingsApi?.SETTINGS_CHANGE_EVENT) {
        window.addEventListener(paymentSettingsApi.SETTINGS_CHANGE_EVENT, () => {
            renderPaymentSettings();
        });
    }

    renderPaymentSettings();
    renderPackageDownPaymentManager();
    syncGalleryManager();
});
