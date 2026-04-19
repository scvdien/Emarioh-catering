document.addEventListener("DOMContentLoaded", () => {
    const inboxList = document.getElementById("adminMessageInboxList");
    const emptyState = document.getElementById("adminMessagesEmpty");
    const emptyStateTitle = emptyState?.querySelector("h3") || null;
    const emptyStateCopy = emptyState?.querySelector("p") || null;
    const detailTitle = document.getElementById("adminMessageDetailTitle");
    const detailContact = document.getElementById("adminMessageDetailContact");
    const detailList = document.getElementById("adminMessageDetailList");
    const detailMeta = document.getElementById("adminMessageDetailMeta");
    const topbarStatus = document.getElementById("adminMessagesTopbarStatus");
    const inboxCount = document.getElementById("adminMessagesInboxCount");
    const emailLink = document.getElementById("adminMessageEmailLink");
    const deleteButton = document.getElementById("adminMessageDeleteButton");
    const modalElement = document.getElementById("adminInquiryModal");
    const deleteModalElement = document.getElementById("adminInquiryDeleteModal");
    const deleteModalText = document.getElementById("adminInquiryDeleteModalText");
    const deleteConfirmButton = document.getElementById("adminInquiryDeleteConfirmButton");
    const inquiryModal = modalElement && window.bootstrap ? new window.bootstrap.Modal(modalElement) : null;
    const deleteConfirmModal = deleteModalElement && window.bootstrap ? new window.bootstrap.Modal(deleteModalElement) : null;
    const LIST_ENDPOINT = "api/inquiries/list.php";
    const MARK_READ_ENDPOINT = "api/inquiries/mark-read.php";
    const DELETE_ENDPOINT = "api/inquiries/delete.php";

    const STATUS_META = {
        unread: {
            label: "Unread",
            badgeClass: "message-badge--unread"
        },
        read: {
            label: "Read",
            badgeClass: "message-badge--read"
        }
    };

    let messages = [];
    let selectedMessageId = "";
    let pendingDeleteMessageId = "";
    let isDeletingMessage = false;
    let isLoading = false;
    let lastErrorMessage = "";

    function escapeHtml(value = "") {
        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    async function requestJson(url, options = {}) {
        const requestOptions = Object.assign({
            credentials: "same-origin",
            headers: {
                Accept: "application/json"
            }
        }, options || {});
        const response = await fetch(url, requestOptions);
        const rawText = await response.text();
        let payload = {};

        try {
            payload = rawText ? JSON.parse(rawText) : {};
        } catch (error) {
            payload = {};
        }

        if (!response.ok || payload.ok === false) {
            throw new Error(payload.message || "The inquiry inbox could not be loaded right now.");
        }

        return payload;
    }

    function normalizeStatus(status) {
        return status === "unread" || status === "new" ? "unread" : "read";
    }

    function getStatusMeta(status) {
        return STATUS_META[normalizeStatus(status)] || STATUS_META.read;
    }

    function formatDateTime(isoString) {
        const parsedDate = new Date(isoString);

        if (Number.isNaN(parsedDate.getTime())) {
            return "";
        }

        return parsedDate.toLocaleString("en-PH", {
            month: "short",
            day: "numeric",
            hour: "numeric",
            minute: "2-digit"
        });
    }

    function formatRelativeLabel(isoString) {
        const parsedDate = new Date(isoString);

        if (Number.isNaN(parsedDate.getTime())) {
            return "";
        }

        const today = new Date();
        const todayStart = new Date(today.getFullYear(), today.getMonth(), today.getDate());
        const targetStart = new Date(parsedDate.getFullYear(), parsedDate.getMonth(), parsedDate.getDate());
        const diffDays = Math.round((todayStart.getTime() - targetStart.getTime()) / 86400000);

        if (diffDays <= 0) {
            return "Today";
        }

        if (diffDays === 1) {
            return "Yesterday";
        }

        return parsedDate.toLocaleDateString("en-PH", {
            month: "short",
            day: "numeric"
        });
    }

    function formatCountLabel(total) {
        return `${total} ${total === 1 ? "inquiry" : "inquiries"}`;
    }

    function formatTopbarSummary(unreadTotal, total) {
        if (!total) {
            return "No inquiries yet";
        }

        if (!unreadTotal) {
            return `${total} ${total === 1 ? "inquiry" : "inquiries"} in inbox`;
        }

        return `${unreadTotal} unread ${unreadTotal === 1 ? "inquiry" : "inquiries"} to review`;
    }

    function buildMailtoHref(message) {
        const email = String(message?.email || "").trim();
        const subject = `Re: ${message?.category || "Website Inquiry"} - Emarioh Catering Services`;
        return `mailto:${email}?subject=${encodeURIComponent(subject)}`;
    }

    function setEmailActionState(isDisabled, href = "", disabledLabel = "Reply by Email") {
        if (!emailLink) {
            return;
        }

        if (isDisabled) {
            emailLink.removeAttribute("href");
            emailLink.setAttribute("aria-disabled", "true");
            emailLink.classList.add("is-disabled");
            emailLink.textContent = disabledLabel;
            return;
        }

        emailLink.setAttribute("href", href);
        emailLink.removeAttribute("aria-disabled");
        emailLink.classList.remove("is-disabled");
        emailLink.textContent = "Reply by Email";
    }

    function setDeleteActionState(isDisabled, disabledLabel = "Delete Inquiry") {
        if (!deleteButton) {
            return;
        }

        deleteButton.disabled = isDisabled;
        deleteButton.classList.toggle("is-disabled", isDisabled);
        deleteButton.textContent = disabledLabel;
    }

    function setDeleteConfirmState(isDisabled, label = "Delete Inquiry") {
        if (!deleteConfirmButton) {
            return;
        }

        deleteConfirmButton.disabled = isDisabled;
        deleteConfirmButton.classList.toggle("is-disabled", isDisabled);
        deleteConfirmButton.textContent = label;
    }

    function setEmptyState(title, copy) {
        if (emptyStateTitle) {
            emptyStateTitle.textContent = title;
        }

        if (emptyStateCopy) {
            emptyStateCopy.textContent = copy;
        }
    }

    function getSelectedMessage() {
        return messages.find((message) => String(message.id) === selectedMessageId) || null;
    }

    function renderSummary() {
        if (topbarStatus) {
            if (isLoading && !messages.length) {
                topbarStatus.textContent = "Loading inquiries...";
            } else if (lastErrorMessage && !messages.length) {
                topbarStatus.textContent = "Inbox unavailable";
            } else {
                const unreadTotal = messages.filter((message) => normalizeStatus(message.status) === "unread").length;
                topbarStatus.textContent = formatTopbarSummary(unreadTotal, messages.length);
            }
        }

        if (inboxCount) {
            inboxCount.textContent = formatCountLabel(messages.length);
        }
    }

    function renderInboxList() {
        if (!inboxList || !emptyState) {
            return;
        }

        if (isLoading && !messages.length) {
            inboxList.hidden = true;
            emptyState.hidden = false;
            setEmptyState("Loading inquiries...", "Please wait while the inbox updates.");
            return;
        }

        if (lastErrorMessage && !messages.length) {
            inboxList.hidden = true;
            emptyState.hidden = false;
            setEmptyState("Inbox unavailable", lastErrorMessage);
            return;
        }

        const hasMessages = messages.length > 0;
        emptyState.hidden = hasMessages;
        inboxList.hidden = !hasMessages;

        if (!hasMessages) {
            setEmptyState("No website inquiries yet", "Messages submitted from the public contact form will appear here.");
            inboxList.innerHTML = "";
            return;
        }

        inboxList.innerHTML = messages.map((message) => {
            const normalizedStatus = normalizeStatus(message.status);
            const isSelected = String(message.id) === selectedMessageId;
            const isUnread = normalizedStatus === "unread";
            const emailLabel = String(message.email || "").trim() || "No email provided";

            return `
                <button class="admin-message-list-item ${isSelected ? "is-selected" : ""} ${isUnread ? "is-unread" : "is-read"}" type="button" data-message-open="${escapeHtml(message.id)}">
                    <span class="admin-message-list-item__body">
                        <span class="admin-message-list-item__topline">
                            <span class="admin-message-list-item__name-row">
                                <span class="admin-message-list-item__name">${escapeHtml(message.name)}</span>
                                ${isUnread ? '<span class="admin-message-unread-dot" aria-hidden="true"></span>' : ""}
                            </span>
                            <span class="admin-message-list-item__time">${escapeHtml(formatRelativeLabel(message.submittedAt))}</span>
                        </span>
                        <span class="admin-message-list-item__email">${escapeHtml(emailLabel)} &middot; ${escapeHtml(message.category)}</span>
                        <span class="admin-message-list-item__preview">${escapeHtml(message.message)}</span>
                    </span>
                </button>
            `;
        }).join("");
    }

    function renderDetailPanel() {
        const message = getSelectedMessage();

        if (!detailTitle || !detailContact || !detailList || !detailMeta) {
            return;
        }

        if (!message) {
            detailTitle.textContent = "No inquiry selected";
            detailContact.textContent = "Choose an inquiry from the inbox to view the customer email and full message.";
            detailMeta.innerHTML = "";
            detailList.innerHTML = '<p class="admin-message-empty-detail">Select an inquiry from the list to see the complete customer message.</p>';
            setEmailActionState(true);
            setDeleteActionState(true);
            return;
        }

        const normalizedStatus = normalizeStatus(message.status);
        const statusMeta = getStatusMeta(normalizedStatus);
        const emailValue = String(message.email || "").trim();
        const mailtoHref = emailValue ? buildMailtoHref(message) : "";
        const submittedLabel = formatDateTime(message.submittedAt);

        detailTitle.textContent = message.name;
        if (emailValue) {
            detailContact.innerHTML = `<a class="admin-message-detail-email" href="${escapeHtml(mailtoHref)}">${escapeHtml(emailValue)}</a>`;
        } else {
            detailContact.textContent = "No email provided by the client.";
        }
        detailMeta.innerHTML = `
            <span class="admin-message-detail-meta__item admin-message-detail-meta__item--status">${escapeHtml(statusMeta.label)}</span>
            <span class="admin-message-detail-meta__divider" aria-hidden="true">&bull;</span>
            <span class="admin-message-detail-meta__item">${escapeHtml(submittedLabel)}</span>
            <span class="admin-message-detail-meta__divider" aria-hidden="true">&bull;</span>
            <span class="admin-message-detail-meta__item">${escapeHtml(message.reference || "Website Inquiry")}</span>
        `;
        detailList.innerHTML = `
            <div class="admin-inquiry-message-card">
                <p class="admin-inquiry-message-card__copy">${escapeHtml(message.message)}</p>
            </div>
        `;

        setEmailActionState(!emailValue, mailtoHref, "No email provided");
        setDeleteActionState(normalizedStatus !== "read", normalizedStatus === "read" ? "Delete Inquiry" : "Read first to delete");
    }

    function renderAll() {
        renderSummary();
        renderInboxList();
        renderDetailPanel();
    }

    async function loadMessages() {
        isLoading = true;
        lastErrorMessage = "";
        renderAll();

        try {
            const payload = await requestJson(LIST_ENDPOINT);
            messages = Array.isArray(payload.messages) ? payload.messages : [];

            if (selectedMessageId && !messages.some((message) => String(message.id) === selectedMessageId)) {
                selectedMessageId = "";
            }
        } catch (error) {
            messages = [];
            selectedMessageId = "";
            lastErrorMessage = error instanceof Error
                ? error.message
                : "The inquiry inbox could not be loaded right now.";
        } finally {
            isLoading = false;
            renderAll();
        }
    }

    async function markMessageRead(messageId) {
        try {
            const payload = await requestJson(MARK_READ_ENDPOINT, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json"
                },
                credentials: "same-origin",
                body: JSON.stringify({
                    inquiry_id: Number(messageId)
                })
            });
            const updatedInquiry = payload.inquiry;

            if (updatedInquiry && typeof updatedInquiry === "object") {
                messages = messages.map((message) => {
                    return String(message.id) === String(updatedInquiry.id) ? updatedInquiry : message;
                });
                renderAll();
            }
        } catch (error) {
            return;
        }
    }

    function resetDeleteConfirmState() {
        pendingDeleteMessageId = "";
        isDeletingMessage = false;
        setDeleteConfirmState(false, "Delete Inquiry");
    }

    function openDeleteConfirm(message) {
        if (!message || normalizeStatus(message.status) !== "read" || isDeletingMessage) {
            return;
        }

        pendingDeleteMessageId = String(message.id);
        setDeleteConfirmState(false, "Delete Inquiry");

        if (deleteModalText) {
            deleteModalText.textContent = `Delete this inquiry from ${message.name}? This action cannot be undone.`;
        }

        if (deleteConfirmModal) {
            deleteConfirmModal.show();
            return;
        }

        const shouldDelete = window.confirm(`Delete this inquiry from ${message.name}? This action cannot be undone.`);

        if (!shouldDelete) {
            resetDeleteConfirmState();
            return;
        }

        void confirmDeleteMessage();
    }

    async function confirmDeleteMessage() {
        const messageId = pendingDeleteMessageId;
        const selectedMessage = messages.find((message) => String(message.id) === String(messageId));

        if (!selectedMessage || isDeletingMessage) {
            return;
        }

        isDeletingMessage = true;
        setDeleteActionState(true, "Deleting...");
        setDeleteConfirmState(true, "Deleting...");

        try {
            await markMessageRead(messageId);
            await requestJson(DELETE_ENDPOINT, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json"
                },
                credentials: "same-origin",
                body: JSON.stringify({
                    inquiry_id: Number(messageId)
                })
            });

            messages = messages.filter((message) => String(message.id) !== String(messageId));
            resetDeleteConfirmState();
            deleteConfirmModal?.hide();
            inquiryModal?.hide();
            renderAll();
        } catch (error) {
            isDeletingMessage = false;
            setDeleteActionState(false);
            setDeleteConfirmState(false, "Delete Inquiry");
            window.alert(error instanceof Error ? error.message : "The inquiry could not be deleted right now.");
        }
    }

    function openMessage(messageId) {
        selectedMessageId = String(messageId || "");

        const selectedMessage = getSelectedMessage();

        if (!selectedMessage) {
            return;
        }

        if (normalizeStatus(selectedMessage.status) === "unread") {
            messages = messages.map((message) => {
                if (String(message.id) !== selectedMessageId) {
                    return message;
                }

                return Object.assign({}, message, {
                    status: "read",
                    readAt: message.readAt || new Date().toISOString()
                });
            });

            void markMessageRead(selectedMessageId);
        }

        renderAll();
        inquiryModal?.show();
    }

    inboxList?.addEventListener("click", (event) => {
        const clickTarget = event.target instanceof Element ? event.target : null;
        const trigger = clickTarget ? clickTarget.closest("[data-message-open]") : null;

        if (!trigger) {
            return;
        }

        openMessage(trigger.dataset.messageOpen || "");
    });

    deleteButton?.addEventListener("click", () => {
        const selectedMessage = getSelectedMessage();

        if (!selectedMessage) {
            return;
        }

        openDeleteConfirm(selectedMessage);
    });

    deleteConfirmButton?.addEventListener("click", () => {
        void confirmDeleteMessage();
    });

    modalElement?.addEventListener("hidden.bs.modal", () => {
        selectedMessageId = "";
        renderAll();
    });

    deleteModalElement?.addEventListener("hidden.bs.modal", () => {
        resetDeleteConfirmState();
    });

    void loadMessages();
});
