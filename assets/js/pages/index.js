document.addEventListener("DOMContentLoaded", () => {
    const CLIENT_PORTAL_STORAGE_KEY = "emariohClientPortalState";
    const sidebarElement = document.getElementById("dashboardSidebar");
    const navLinks = document.querySelectorAll(".dashboard-nav .nav-link");
    const actionMenus = document.querySelectorAll("[data-action-menu]");
    const bookingFilterButtons = document.querySelectorAll("[data-booking-filter]");
    const bookingRows = document.querySelectorAll("[data-booking-row]");
    const bookingViewButtons = document.querySelectorAll("[data-booking-view]");
    const bookingEmptyState = document.querySelector("[data-booking-empty]");
    const bookingTableWrap = document.querySelector(".booking-queue-table");
    const bookingStatusSelect = document.getElementById("bookingStatusSelect");
    const paymentFilterButtons = document.querySelectorAll("[data-payment-filter]");
    const paymentTableBody = document.querySelector(".admin-table--payments tbody");
    const paymentTableWrap = document.querySelector(".payment-queue-table");
    let paymentSourceRows = Array.from(document.querySelectorAll("[data-payment-row]"));
    let paymentRows = [];
    const paymentEmptyState = document.querySelector("[data-payment-empty]");
    const paymentSearchInput = document.getElementById("paymentSearchInput");
    const paymentStatusSelect = document.getElementById("paymentStatusSelect");
    let paymentViewButtons = [];
    let activePaymentViewButton = null;
    const isDbOnlyPaymentTable = document.body?.dataset.paymentSource === "db-only";
    const clientFilterButtons = document.querySelectorAll("[data-client-filter]");
    const clientRows = document.querySelectorAll("[data-client-row]");
    const clientEmptyState = document.querySelector("[data-client-empty]");
    const clientSearchInput = document.getElementById("clientSearchInput");
    const clientViewButtons = document.querySelectorAll("[data-client-view]");
    const mobileClientTableMedia = window.matchMedia("(max-width: 767.98px)");
    const bookingDetailsModalElement = document.getElementById("bookingDetailsModal");
    const bookingDetailsStatusElement = document.getElementById("bookingDetailsStatus");
    const paymentDetailsModalElement = document.getElementById("paymentDetailsModal");
    const paymentDetailsStatusElement = document.getElementById("paymentDetailsStatus");
    const paymentDetailsReceiptEmptyElement = document.getElementById("paymentDetailsReceiptEmpty");
    const paymentDetailsReceiptPreviewElement = document.getElementById("paymentDetailsReceiptPreview");
    const paymentDetailsReceiptImageElement = document.getElementById("paymentDetailsReceiptImage");
    const paymentDetailsReceiptUploadedAtElement = document.getElementById("paymentDetailsReceiptUploadedAt");
    const paymentDetailsReceiptFileNameElement = document.getElementById("paymentDetailsReceiptFileName");
    const paymentDetailsReceiptNoteElement = document.getElementById("paymentDetailsReceiptNote");
    const paymentDetailsOpenReceiptButton = document.getElementById("paymentDetailsOpenReceiptButton");
    const paymentDetailsReminderButton = document.getElementById("paymentDetailsReminderButton");
    const paymentDetailsConfirmButton = document.getElementById("paymentDetailsConfirmButton");
    const paymentActionFeedbackModalElement = document.getElementById("paymentActionFeedbackModal");
    const paymentActionFeedbackModalLabel = document.getElementById("paymentActionFeedbackModalLabel");
    const paymentActionFeedbackText = document.getElementById("paymentActionFeedbackText");
    const paymentActionFeedbackStatus = document.getElementById("paymentActionFeedbackStatus");
    const clientDetailsModalElement = document.getElementById("clientDetailsModal");
    const clientDetailsStatusElement = document.getElementById("clientDetailsStatus");
    const currentPath = window.location.pathname.split("/").pop() || "index.php";
    const sidebarInstance = sidebarElement && window.bootstrap
        ? window.bootstrap.Offcanvas.getOrCreateInstance(sidebarElement)
        : null;
    const bookingDetailsModal = bookingDetailsModalElement && window.bootstrap?.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(bookingDetailsModalElement)
        : null;
    const paymentDetailsModal = paymentDetailsModalElement && window.bootstrap?.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(paymentDetailsModalElement)
        : null;
    const paymentActionFeedbackModal = paymentActionFeedbackModalElement && window.bootstrap?.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(paymentActionFeedbackModalElement, { backdrop: false })
        : null;
    const clientDetailsModal = clientDetailsModalElement && window.bootstrap?.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(clientDetailsModalElement)
        : null;

    const showPaymentActionFeedback = (title, message, state = "success") => {
        const normalizedTitle = String(title || "").trim() || "Payment Update";
        const normalizedMessage = String(message || "").trim() || "Action completed.";

        if (!paymentActionFeedbackModal
            || !paymentActionFeedbackModalLabel
            || !paymentActionFeedbackText
            || !paymentActionFeedbackStatus) {
            window.alert(normalizedMessage);
            return;
        }

        const statusMeta = state === "error"
            ? { label: "Error", className: "status-pill--rejected" }
            : { label: "Success", className: "status-pill--approved" };

        paymentActionFeedbackModalLabel.textContent = normalizedTitle;
        paymentActionFeedbackText.textContent = normalizedMessage;
        paymentActionFeedbackStatus.textContent = statusMeta.label;
        paymentActionFeedbackStatus.className = `status-pill ${statusMeta.className}`;
        paymentActionFeedbackModal.show();
    };

    const resetActionPanelPosition = (panel) => {
        if (!panel) {
            return;
        }

        panel.classList.remove("action-menu__panel--floating");
        panel.style.removeProperty("top");
        panel.style.removeProperty("left");
        panel.style.removeProperty("width");
        panel.style.removeProperty("max-width");
    };

    const closeActionMenu = (menu) => {
        const toggle = menu.querySelector("[data-action-menu-toggle]");
        const panel = menu.querySelector("[data-action-menu-panel]");

        menu.classList.remove("is-open");
        toggle?.setAttribute("aria-expanded", "false");

        if (panel) {
            resetActionPanelPosition(panel);
            panel.hidden = true;
        }
    };

    const closeAllActionMenus = (exceptMenu = null) => {
        actionMenus.forEach((menu) => {
            if (menu !== exceptMenu) {
                closeActionMenu(menu);
            }
        });
    };

    const positionActionPanel = (toggle, panel) => {
        if (!toggle || !panel) {
            return;
        }

        const viewportPadding = 8;
        const toggleRect = toggle.getBoundingClientRect();

        resetActionPanelPosition(panel);
        panel.classList.add("action-menu__panel--floating");

        const maxPanelWidth = Math.max(140, window.innerWidth - (viewportPadding * 2));
        const measuredWidth = panel.offsetWidth || 164;
        const panelWidth = Math.min(measuredWidth, maxPanelWidth);

        panel.style.width = `${panelWidth}px`;
        panel.style.maxWidth = `${maxPanelWidth}px`;

        const panelHeight = panel.offsetHeight;
        const preferredLeft = toggleRect.right - panelWidth;
        const maxLeft = Math.max(viewportPadding, window.innerWidth - panelWidth - viewportPadding);
        const left = Math.min(Math.max(viewportPadding, preferredLeft), maxLeft);
        const openBelowTop = toggleRect.bottom + viewportPadding;
        const openAboveTop = toggleRect.top - panelHeight - viewportPadding;
        const top = openBelowTop + panelHeight <= window.innerHeight - viewportPadding
            ? openBelowTop
            : Math.max(viewportPadding, openAboveTop);

        panel.style.left = `${left}px`;
        panel.style.top = `${top}px`;
    };

    const setBookingDetailValue = (id, value, fallback = "Not provided") => {
        const element = document.getElementById(id);

        if (element) {
            element.textContent = value?.trim() || fallback;
        }
    };

    const setElementHref = (id, href, fallback = "#") => {
        const element = document.getElementById(id);

        if (element) {
            element.setAttribute("href", href?.trim() || fallback);
        }
    };

    const setStatusPill = (element, statusClass, statusLabel, fallbackClass = "pending", fallbackLabel = "Pending") => {
        if (!element) {
            return;
        }

        element.className = "status-pill";
        element.classList.add(`status-pill--${statusClass || fallbackClass}`);
        element.textContent = statusLabel || fallbackLabel;
    };

    const setAsyncButtonState = (button, isBusy, busyText = "Working...") => {
        if (!button) {
            return;
        }

        if (!button.dataset.defaultLabel) {
            button.dataset.defaultLabel = button.textContent.trim() || "Confirm";
        }

        button.disabled = isBusy;
        button.textContent = isBusy ? busyText : (button.dataset.defaultLabel || "Confirm");
    };

    const resolveBookingDetailsSource = (element) => {
        if (!(element instanceof Element)) {
            return null;
        }

        return element.closest("[data-booking-row]") || element;
    };

    const getClientPortalState = () => {
        try {
            const storedValue = window.localStorage.getItem(CLIENT_PORTAL_STORAGE_KEY);
            return storedValue ? JSON.parse(storedValue) : null;
        } catch (error) {
            return null;
        }
    };

    const saveClientPortalState = (state) => {
        try {
            window.localStorage.setItem(CLIENT_PORTAL_STORAGE_KEY, JSON.stringify(state));
            return true;
        } catch (error) {
            return false;
        }
    };

    const parseCurrencyAmount = (value) => {
        const normalizedValue = String(value || "").replace(/[^0-9.]/g, "");
        const parsedValue = Number.parseFloat(normalizedValue);

        return Number.isFinite(parsedValue) ? parsedValue : 0;
    };

    const formatCurrencyAmount = (value) => {
        if (!Number.isFinite(value)) {
            return "PHP 0";
        }

        return `PHP ${new Intl.NumberFormat("en-PH", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(value)}`;
    };

    const formatPortalDateLabel = (dateValue) => {
        if (!dateValue) {
            return "Date pending";
        }

        const date = new Date(dateValue);

        if (Number.isNaN(date.getTime())) {
            return String(dateValue).trim() || "Date pending";
        }

        return new Intl.DateTimeFormat("en-PH", {
            month: "long",
            day: "numeric",
            year: "numeric"
        }).format(date);
    };

    const formatPortalTimeLabel = (timeValue) => {
        if (!timeValue || !String(timeValue).includes(":")) {
            return "Time pending";
        }

        const [hours, minutes] = String(timeValue).split(":").map(Number);
        const date = new Date();

        date.setHours(hours || 0, minutes || 0, 0, 0);

        return new Intl.DateTimeFormat("en-PH", {
            hour: "numeric",
            minute: "2-digit"
        }).format(date);
    };

    const formatAdminDateTimeLabel = (value) => {
        if (!value) {
            return "Not provided";
        }

        if (typeof value === "string" && value.includes("|")) {
            return value.trim();
        }

        const parsedDate = new Date(value);

        if (Number.isNaN(parsedDate.getTime())) {
            return String(value).trim() || "Not provided";
        }

        const dateLabel = new Intl.DateTimeFormat("en-PH", {
            month: "long",
            day: "numeric",
            year: "numeric"
        }).format(parsedDate);
        const timeLabel = new Intl.DateTimeFormat("en-PH", {
            hour: "numeric",
            minute: "2-digit"
        }).format(parsedDate);

        return `${dateLabel} | ${timeLabel}`;
    };

    const sanitizePaymentLogText = (value) => String(value || "")
        .replaceAll("||", " / ")
        .replaceAll("^", "-")
        .trim();

    const appendPaymentLogEntry = (logValue, entry) => {
        const serializedEntry = [
            sanitizePaymentLogText(entry.timeLabel),
            sanitizePaymentLogText(entry.title),
            sanitizePaymentLogText(entry.meta),
            sanitizePaymentLogText(entry.statusClass),
            sanitizePaymentLogText(entry.statusLabel),
            sanitizePaymentLogText(entry.note)
        ].join("^");

        return logValue ? `${logValue}||${serializedEntry}` : serializedEntry;
    };

    const createPortalInvoiceReference = (requestReference) => {
        if (!requestReference) {
            return "INV-TBA";
        }

        return String(requestReference).replace(/^REQ-/, "INV-");
    };

    const buildPortalPaymentLog = (bookingRequest, billingDetails, invoiceNumber, amountLabel, description) => {
        if (billingDetails?.paymentLog) {
            return billingDetails.paymentLog;
        }

        let nextLog = appendPaymentLogEntry("", {
            timeLabel: formatAdminDateTimeLabel(billingDetails?.invoiceCreatedAt || bookingRequest?.submittedAt || new Date().toISOString()),
            title: "Invoice Created",
            meta: `${invoiceNumber} | ${description} | ${amountLabel}`,
            statusClass: "pending",
            statusLabel: "Open",
            note: "Invoice is open and waiting for client payment."
        });

        if (billingDetails?.receiptSubmission?.uploadedAt) {
            nextLog = appendPaymentLogEntry(nextLog, {
                timeLabel: formatAdminDateTimeLabel(billingDetails.receiptSubmission.uploadedAt),
                title: "Proof Sent",
                meta: `Receipt screenshot uploaded by client | ${amountLabel}`,
                statusClass: "review",
                statusLabel: "Needs Review",
                note: billingDetails.receiptSubmission.note?.trim()
                    ? `Client note: ${sanitizePaymentLogText(billingDetails.receiptSubmission.note)}`
                    : "Billing team needs to verify the uploaded receipt."
            });
        }

        if (billingDetails?.statusPillClass === "approved" && billingDetails?.lastPayment) {
            nextLog = appendPaymentLogEntry(nextLog, {
                timeLabel: formatAdminDateTimeLabel(billingDetails.lastPayment),
                title: "Payment Verified",
                meta: billingDetails.pendingBalanceValue > 0
                    ? `Confirmed amount ${billingDetails.amountDue || amountLabel}`
                    : "Invoice settled in full",
                statusClass: "approved",
                statusLabel: "Paid",
                note: billingDetails.paymentNote || "Billing confirmed the submitted receipt."
            });
        }

        return nextLog;
    };

    const createPortalPaymentSourceRow = (state) => {
        const bookingRequest = state?.bookingRequest;
        const billingDetails = state?.billingDetails || {};

        if (!bookingRequest || bookingRequest.status !== "approved") {
            return null;
        }

        const invoiceNumber = String(billingDetails.invoiceNumber || createPortalInvoiceReference(bookingRequest.reference)).trim();
        const amountLabel = String(billingDetails.amountDue || "See latest invoice").trim();
        const description = String(
            billingDetails.description
            || bookingRequest.packageLabel
            || bookingRequest.eventType
            || "Booking Payment"
        ).trim();
        const receiptSubmission = billingDetails.receiptSubmission || null;
        const statusClass = billingDetails.statusPillClass || (receiptSubmission ? "review" : "pending");
        const statusLabel = billingDetails.statusText || (receiptSubmission ? "Needs Review" : "Open");
        const paymentStage = billingDetails.paymentStage || (receiptSubmission ? "Proof Review" : "Awaiting Payment");
        const paymentLog = buildPortalPaymentLog(bookingRequest, billingDetails, invoiceNumber, amountLabel, description);
        const row = document.createElement("tr");
        const cell = document.createElement("td");
        const button = document.createElement("button");

        row.hidden = true;
        row.setAttribute("data-payment-row", "");
        row.dataset.portalPaymentSourceRow = "true";
        row.dataset.paymentStatus = statusClass;
        row.dataset.paymentSearch = [
            bookingRequest.primaryContact,
            bookingRequest.reference,
            description,
            invoiceNumber,
            amountLabel,
            statusLabel,
            paymentStage
        ].join(" ").toLowerCase();

        button.type = "button";
        button.setAttribute("data-payment-view", "");
        button.dataset.paymentSourceType = "portal";
        button.dataset.paymentTitle = invoiceNumber;
        button.dataset.clientName = bookingRequest.primaryContact || "Portal Client";
        button.dataset.bookingReference = bookingRequest.reference || "REQ-TBA";
        button.dataset.eventName = description;
        button.dataset.eventSchedule = `${formatPortalDateLabel(bookingRequest.eventDate)} | ${formatPortalTimeLabel(bookingRequest.eventTime)}`;
        button.dataset.amount = amountLabel;
        button.dataset.dueDate = billingDetails.dueDate ? formatPortalDateLabel(billingDetails.dueDate) : "See invoice";
        button.dataset.method = billingDetails.paymentMethod || "QRPh";
        button.dataset.statusLabel = statusLabel;
        button.dataset.statusClass = statusClass;
        button.dataset.paymentNote = billingDetails.paymentNote
            || (receiptSubmission
                ? "Receipt uploaded by client and waiting for billing team review."
                : "Invoice is open and waiting for client payment.");
        button.dataset.paymentTotalPaid = billingDetails.totalPaid || "PHP 0";
        button.dataset.paymentPendingBalance = billingDetails.pendingBalance || amountLabel;
        button.dataset.paymentLastPayment = billingDetails.lastPayment
            || (receiptSubmission ? formatAdminDateTimeLabel(receiptSubmission.uploadedAt) : "No payment posted yet");
        button.dataset.paymentLoggedAt = billingDetails.lastPayment
            || (receiptSubmission
                ? formatAdminDateTimeLabel(receiptSubmission.uploadedAt)
                : formatAdminDateTimeLabel(billingDetails.invoiceCreatedAt || bookingRequest.submittedAt || new Date().toISOString()));
        button.dataset.paymentStage = paymentStage;
        button.dataset.paymentLog = paymentLog;
        button.dataset.bookingHref = "admin-bookings.php";
        button.dataset.receiptDataUrl = receiptSubmission?.dataUrl || "";
        button.dataset.receiptUploadedAt = receiptSubmission?.uploadedAt || "";
        button.dataset.receiptFileName = receiptSubmission?.fileName || "";
        button.dataset.receiptNote = receiptSubmission?.note || "";

        cell.appendChild(button);
        row.appendChild(cell);

        return row;
    };

    const upsertPortalPaymentSourceRow = () => {
        if (isDbOnlyPaymentTable) {
            return;
        }

        const existingIndex = paymentSourceRows.findIndex((row) => row.dataset.portalPaymentSourceRow === "true");
        const portalSourceRow = createPortalPaymentSourceRow(getClientPortalState());

        if (!portalSourceRow) {
            if (existingIndex >= 0) {
                paymentSourceRows.splice(existingIndex, 1);
            }
            return;
        }

        if (existingIndex >= 0) {
            paymentSourceRows.splice(existingIndex, 1, portalSourceRow);
            return;
        }

        paymentSourceRows.unshift(portalSourceRow);
    };

    const renderPaymentReceiptPreview = (button) => {
        const receiptDataUrl = button?.dataset.receiptDataUrl || "";
        const receiptHref = button?.dataset.receiptHref || "";
        const hasReceiptPreview = Boolean(receiptDataUrl);
        const hasReceipt = hasReceiptPreview || Boolean(receiptHref);
        const canPortalConfirm = hasReceiptPreview
            && button?.dataset.statusClass === "review"
            && button?.dataset.paymentSourceType === "portal";
        const canAdminSync = button?.dataset.paymentConfirmMode === "admin-sync";
        const canConfirm = canPortalConfirm || canAdminSync;
        const confirmLabel = button?.dataset.paymentConfirmLabel?.trim() || "Confirm";
        const canSendReminder = Boolean(
            button?.dataset.paymentReminderTemplate?.trim()
            && button?.dataset.bookingId?.trim()
        );
        const reminderLabel = button?.dataset.paymentReminderLabel?.trim() || "Send Reminder";

        if (!paymentDetailsReceiptEmptyElement
            || !paymentDetailsReceiptPreviewElement
            || !paymentDetailsReceiptImageElement
            || !paymentDetailsReceiptNoteElement
            || !paymentDetailsOpenReceiptButton
            || !paymentDetailsConfirmButton
            || !paymentDetailsReminderButton) {
            return;
        }

        paymentDetailsConfirmButton.dataset.defaultLabel = confirmLabel;
        paymentDetailsConfirmButton.textContent = confirmLabel;
        paymentDetailsReminderButton.dataset.defaultLabel = reminderLabel;
        paymentDetailsReminderButton.textContent = reminderLabel;
        paymentDetailsReceiptEmptyElement.textContent = button?.dataset.receiptEmptyLabel?.trim() || "No receipt yet.";
        paymentDetailsReceiptEmptyElement.hidden = hasReceiptPreview;
        paymentDetailsReceiptPreviewElement.hidden = !hasReceiptPreview;
        paymentDetailsOpenReceiptButton.hidden = !hasReceipt;
        paymentDetailsReminderButton.hidden = !canSendReminder;
        paymentDetailsConfirmButton.hidden = !canConfirm;

        if (!hasReceiptPreview) {
            paymentDetailsReceiptImageElement.removeAttribute("src");
            setBookingDetailValue(
                "paymentDetailsReceiptUploadedAt",
                button?.dataset.receiptUploadedAt,
                hasReceipt ? "Available to open" : "Not provided"
            );
            setBookingDetailValue(
                "paymentDetailsReceiptFileName",
                button?.dataset.receiptFileName,
                hasReceipt ? "Receipt file" : "Not provided"
            );
            paymentDetailsReceiptEmptyElement.hidden = false;
            paymentDetailsReceiptPreviewElement.hidden = true;

            const receiptNote = button?.dataset.receiptNote?.trim() || "";
            paymentDetailsReceiptNoteElement.hidden = true;

            if (receiptNote) {
                setBookingDetailValue("paymentDetailsReceiptNote", receiptNote);
                paymentDetailsReceiptNoteElement.hidden = false;
            }

            return;
        }

        const receiptNote = button.dataset.receiptNote?.trim() || "";

        paymentDetailsReceiptImageElement.src = receiptDataUrl;
        setBookingDetailValue("paymentDetailsReceiptUploadedAt", formatAdminDateTimeLabel(button.dataset.receiptUploadedAt));
        setBookingDetailValue("paymentDetailsReceiptFileName", button.dataset.receiptFileName);
        paymentDetailsReceiptNoteElement.hidden = !receiptNote;

        if (receiptNote) {
            setBookingDetailValue("paymentDetailsReceiptNote", receiptNote);
        }
    };

    const bindPaymentViewButtons = () => {
        paymentViewButtons.forEach((button) => {
            if (button.dataset.paymentViewBound === "true" || button.closest("[data-action-menu]")) {
                return;
            }

            button.dataset.paymentViewBound = "true";
            button.addEventListener("click", () => {
                openPaymentDetails(button);
            });
        });
    };

    const syncPaymentCollectionsFromDom = () => {
        if (!paymentTableBody) {
            paymentRows = [];
            paymentViewButtons = [];
            return;
        }

        paymentRows = Array.from(paymentTableBody.querySelectorAll("[data-payment-row]"));
        paymentViewButtons = Array.from(paymentTableBody.querySelectorAll("[data-payment-view]"));
    };

    const findPaymentViewButtonByTitle = (paymentTitle) => paymentViewButtons.find(
        (button) => button.dataset.paymentTitle === paymentTitle
    ) || null;

    const renderClientHistory = (historyValue = "") => {
        const historyContainer = document.getElementById("clientDetailsHistory");
        const historyEmptyState = document.getElementById("clientDetailsHistoryEmpty");

        if (!historyContainer || !historyEmptyState) {
            return;
        }

        historyContainer.innerHTML = "";

        const entries = historyValue
            .split("||")
            .map((entry) => entry.trim())
            .filter(Boolean)
            .map((entry) => {
                const [date = "", eventName = "", packageName = "", venueName = "", status = "Completed"] = entry.split("^");
                return { date, eventName, packageName, venueName, status };
            });

        if (!entries.length) {
            historyEmptyState.hidden = false;
            return;
        }

        historyEmptyState.hidden = true;

        entries.forEach((entry) => {
            const item = document.createElement("article");
            item.className = "client-history-item";
            item.innerHTML = `
                <div class="client-history-item__topline">
                    <div>
                        <h4>${entry.eventName || "Completed Event"}</h4>
                        <p>${entry.date || "Date not provided"}</p>
                    </div>
                    <span class="status-pill status-pill--completed">${entry.status || "Completed"}</span>
                </div>
                <p class="client-history-item__meta">${entry.packageName || "Package not provided"}</p>
                <p class="client-history-item__meta">${entry.venueName || "Venue not provided"}</p>
            `;
            historyContainer.appendChild(item);
        });
    };

    const renderPaymentLog = (logValue = "") => {
        const logContainer = document.getElementById("paymentDetailsLog");
        const logEmptyState = document.getElementById("paymentDetailsLogEmpty");

        if (!logContainer || !logEmptyState) {
            return;
        }

        logContainer.innerHTML = "";

        const entries = logValue
            .split("||")
            .map((entry) => entry.trim())
            .filter(Boolean)
            .map((entry) => {
                const [
                    timeLabel = "",
                    title = "",
                    meta = "",
                    statusClass = "pending",
                    statusLabel = "Pending",
                    note = ""
                ] = entry.split("^");

                return {
                    timeLabel,
                    title,
                    meta,
                    statusClass,
                    statusLabel,
                    note
                };
            });

        if (!entries.length) {
            logEmptyState.hidden = false;
            return;
        }

        logEmptyState.hidden = true;

        entries.forEach((entry) => {
            const item = document.createElement("article");
            const timeline = document.createElement("div");
            const dot = document.createElement("span");
            const content = document.createElement("div");
            const head = document.createElement("div");
            const copy = document.createElement("div");
            const title = document.createElement("h4");
            const time = document.createElement("p");
            const status = document.createElement("span");
            const meta = document.createElement("p");
            const note = document.createElement("p");

            item.className = "payment-log-item";
            timeline.className = "payment-log-item__timeline";
            dot.className = "payment-log-item__dot";
            content.className = "payment-log-item__content";
            head.className = "payment-log-item__head";
            copy.className = "payment-log-item__copy";
            title.className = "payment-log-item__title";
            time.className = "payment-log-item__time";
            meta.className = "payment-log-item__meta";
            note.className = "payment-log-item__note";

            const metaText = entry.meta?.trim() || "";
            const noteText = entry.note?.trim() || "";

            title.textContent = entry.title || "Payment update";
            time.textContent = entry.timeLabel || "Time not provided";
            status.className = "status-pill";
            status.classList.add(`status-pill--${entry.statusClass || "pending"}`);
            status.textContent = entry.statusLabel || "Pending";

            timeline.appendChild(dot);
            copy.appendChild(title);
            copy.appendChild(time);
            head.appendChild(copy);
            head.appendChild(status);
            content.appendChild(head);

            if (metaText) {
                meta.textContent = metaText;
                content.appendChild(meta);
            }

            if (noteText) {
                note.textContent = noteText;
                content.appendChild(note);
            }

            item.appendChild(timeline);
            item.appendChild(content);
            logContainer.appendChild(item);
        });
    };

    const parsePaymentLogEntries = (logValue = "") => logValue
        .split("||")
        .map((entry) => entry.trim())
        .filter(Boolean)
        .map((entry) => {
            const [
                timeLabel = "",
                title = "",
                meta = "",
                statusClass = "pending",
                statusLabel = "Pending",
                note = ""
            ] = entry.split("^");

            return {
                timeLabel,
                title,
                meta,
                statusClass,
                statusLabel,
                note
            };
        });

    const getPaymentLogTimestamp = (timeLabel = "") => {
        const normalizedLabel = timeLabel.replace(/\s*\|\s*/g, " ").trim();
        const parsedValue = Date.parse(normalizedLabel);

        return Number.isNaN(parsedValue) ? 0 : parsedValue;
    };

    const extractPaymentAmountLabel = (entry, fallbackAmount = "Not provided") => {
        const amountMatch = `${entry.meta || ""} ${entry.note || ""}`.match(/(?:PHP|₱)\s*[\d,]+(?:\.\d+)?/i);

        if (!amountMatch) {
            return fallbackAmount?.trim() || "Not provided";
        }

        const matchedValue = amountMatch[0]
            .replace(/^₱/i, "PHP ")
            .replace(/\s+/g, " ")
            .trim();

        return matchedValue.startsWith("PHP") ? matchedValue : `PHP ${matchedValue.replace(/^PHP\s*/i, "").trim()}`;
    };

    const extractPaymentAmountLabelSafe = (entry, fallbackAmount = "Not provided") => {
        const amountMatch = `${entry.meta || ""} ${entry.note || ""}`.match(/(?:PHP|\u20B1)\s*[\d,]+(?:\.\d+)?/i);

        if (!amountMatch) {
            return fallbackAmount?.trim() || "Not provided";
        }

        const matchedValue = amountMatch[0]
            .replace(/^\u20B1/i, "PHP ")
            .replace(/\s+/g, " ")
            .trim();

        return matchedValue.startsWith("PHP") ? matchedValue : `PHP ${matchedValue.replace(/^PHP\s*/i, "").trim()}`;
    };

    const createPaymentMetaCell = (primaryText, detailLines = []) => {
        const cell = document.createElement("td");
        const wrapper = document.createElement("div");
        const primary = document.createElement("strong");

        wrapper.className = "payment-meta-cell";
        primary.textContent = primaryText?.trim() || "Not provided";
        wrapper.appendChild(primary);

        detailLines
            .map((line) => line?.trim())
            .filter(Boolean)
            .forEach((line) => {
                const detail = document.createElement("span");
                detail.textContent = line;
                wrapper.appendChild(detail);
            });

        cell.appendChild(wrapper);

        return cell;
    };

    const createPaymentLogTable = () => {
        if (!paymentTableBody) {
            return;
        }

        if (isDbOnlyPaymentTable) {
            syncPaymentCollectionsFromDom();
            return;
        }

        if (!paymentSourceRows.length) {
            paymentTableBody.innerHTML = "";
            paymentRows = [];
            paymentViewButtons = [];
            return;
        }

        paymentTableBody.innerHTML = "";

        paymentSourceRows.forEach((sourceRow) => {
            const sourceButton = sourceRow.querySelector("[data-payment-view]");

            if (!sourceButton) {
                return;
            }
            const row = document.createElement("tr");
            const [loggedDate = "Date not provided", loggedTime = "Time not provided"] = (sourceButton.dataset.paymentLoggedAt || "")
                .split("|")
                .map((value) => value.trim());
            const amountCell = document.createElement("td");
            const amountValue = document.createElement("strong");
            const statusCell = document.createElement("td");
            const statusPill = document.createElement("span");
            const actionCell = document.createElement("td");
            const viewButton = document.createElement("button");
            const rowStatus = sourceRow.dataset.paymentStatus || sourceButton.dataset.statusClass || "pending";
            const rowSearch = sourceRow.dataset.paymentSearch || [
                sourceButton.dataset.clientName,
                sourceButton.dataset.eventName,
                sourceButton.dataset.bookingReference,
                sourceButton.dataset.paymentTitle,
                sourceButton.dataset.method,
                sourceButton.dataset.amount,
                sourceButton.dataset.paymentStage,
                sourceButton.dataset.paymentNote,
                sourceButton.dataset.statusLabel,
                sourceButton.dataset.receiptFileName,
                sourceButton.dataset.receiptNote
            ]
                .join(" ")
                .toLowerCase();

            row.setAttribute("data-payment-row", "");
            row.dataset.paymentStatus = rowStatus;
            row.dataset.paymentSearch = rowSearch;

            row.appendChild(createPaymentMetaCell(sourceButton.dataset.clientName, [
                sourceButton.dataset.eventName,
                sourceButton.dataset.method
            ]));
            row.appendChild(createPaymentMetaCell(sourceButton.dataset.paymentTitle, [
                sourceButton.dataset.bookingReference
            ]));
            row.appendChild(createPaymentMetaCell(sourceButton.dataset.paymentStage || "Awaiting Payment", [
                sourceButton.dataset.paymentNote || sourceButton.dataset.statusLabel || "Payment details are ready."
            ]));
            row.appendChild(createPaymentMetaCell(loggedDate, [
                loggedTime
            ]));

            amountValue.className = "payment-amount";
            amountValue.textContent = sourceButton.dataset.amount?.trim() || "Not provided";
            amountCell.appendChild(amountValue);
            row.appendChild(amountCell);

            statusPill.className = "status-pill";
            statusPill.classList.add(`status-pill--${rowStatus}`);
            statusPill.textContent = sourceButton.dataset.statusLabel || "Pending";
            statusCell.appendChild(statusPill);
            row.appendChild(statusCell);

            viewButton.className = "action-btn action-btn--ghost";
            viewButton.type = "button";
            viewButton.textContent = "View";
            viewButton.setAttribute("data-payment-view", "");
            Object.entries(sourceButton.dataset).forEach(([key, value]) => {
                viewButton.dataset[key] = value;
            });
            actionCell.appendChild(viewButton);
            row.appendChild(actionCell);

            paymentTableBody.appendChild(row);
        });

        paymentRows = Array.from(document.querySelectorAll("[data-payment-row]"));
        paymentViewButtons = Array.from(document.querySelectorAll("[data-payment-view]"));
    };

    const applyBookingFilter = (filterValue = "all") => {
        if (!bookingRows.length) {
            return;
        }

        let visibleCount = 0;

        closeAllActionMenus();

        bookingRows.forEach((row) => {
            const status = row.dataset.bookingStatus || "pending";
            const isMatch = filterValue === "all" || status === filterValue;

            row.hidden = !isMatch;

            if (isMatch) {
                visibleCount += 1;
            }
        });

        bookingFilterButtons.forEach((button) => {
            const isActive = button.dataset.bookingFilter === filterValue;

            button.classList.toggle("is-active", isActive);
            button.setAttribute("aria-pressed", String(isActive));
        });

        if (bookingStatusSelect && bookingStatusSelect.value !== filterValue) {
            bookingStatusSelect.value = filterValue;
        }

        if (bookingTableWrap) {
            bookingTableWrap.hidden = visibleCount === 0;
        }

        if (bookingEmptyState) {
            bookingEmptyState.textContent = filterValue === "all"
                ? "No bookings found."
                : `No ${filterValue} bookings found.`;
            bookingEmptyState.hidden = visibleCount !== 0;
        }
    };

    upsertPortalPaymentSourceRow();
    createPaymentLogTable();

    const applyPaymentFilter = (filterValue = null) => {
        if (!paymentRows.length) {
            return;
        }

        const activeFilter = filterValue || Array.from(paymentFilterButtons)
            .find((button) => button.classList.contains("is-active"))
            ?.dataset.paymentFilter || "all";
        const searchTerm = paymentSearchInput?.value.trim().toLowerCase() || "";
        let visibleCount = 0;

        closeAllActionMenus();

        paymentRows.forEach((row) => {
            const status = row.dataset.paymentStatus || "pending";
            const searchIndex = row.dataset.paymentSearch || row.textContent.toLowerCase();
            const matchesFilter = activeFilter === "all" || status === activeFilter;
            const matchesSearch = !searchTerm || searchIndex.includes(searchTerm);
            const isMatch = matchesFilter && matchesSearch;

            row.hidden = !isMatch;

            if (isMatch) {
                visibleCount += 1;
            }
        });

        paymentFilterButtons.forEach((button) => {
            const isActive = button.dataset.paymentFilter === activeFilter;

            button.classList.toggle("is-active", isActive);
            button.setAttribute("aria-pressed", String(isActive));
        });

        if (paymentStatusSelect && paymentStatusSelect.value !== activeFilter) {
            paymentStatusSelect.value = activeFilter;
        }

        if (paymentTableWrap) {
            paymentTableWrap.hidden = visibleCount === 0;
        }

        if (paymentEmptyState) {
            paymentEmptyState.textContent = searchTerm
                ? "No matching payment logs found."
                : activeFilter === "all"
                    ? "No payment logs found."
                    : `No ${activeFilter} payment logs found.`;
            paymentEmptyState.hidden = visibleCount !== 0;
        }
    };

    const applyClientFilter = (filterValue = null) => {
        if (!clientRows.length) {
            return;
        }

        const activeFilter = filterValue || Array.from(clientFilterButtons)
            .find((button) => button.classList.contains("is-active"))
            ?.dataset.clientFilter || "all";
        const searchTerm = clientSearchInput?.value.trim().toLowerCase() || "";
        let visibleCount = 0;

        closeAllActionMenus();

        clientRows.forEach((row) => {
            const status = row.dataset.clientStatus || "inactive";
            const searchIndex = row.dataset.clientSearch || row.textContent.toLowerCase();
            const matchesFilter = activeFilter === "all" || status === activeFilter;
            const matchesSearch = !searchTerm || searchIndex.includes(searchTerm);
            const isMatch = matchesFilter && matchesSearch;

            row.hidden = !isMatch;

            if (isMatch) {
                visibleCount += 1;
            }
        });

        clientFilterButtons.forEach((button) => {
            const isActive = button.dataset.clientFilter === activeFilter;

            button.classList.toggle("is-active", isActive);
            button.setAttribute("aria-pressed", String(isActive));
        });

        if (clientEmptyState) {
            clientEmptyState.textContent = searchTerm
                ? "No matching clients found."
                : activeFilter === "all"
                    ? "No clients found."
                    : `No ${activeFilter} clients found.`;
            clientEmptyState.hidden = visibleCount !== 0;
        }
    };

    const openBookingDetails = (button) => {
        const detailSource = resolveBookingDetailsSource(button);

        if (!detailSource || !bookingDetailsModal) {
            return;
        }

        const {
            clientName,
            package: packageName,
            reference,
            submittedAt,
            statusLabel,
            statusClass,
            mobile,
            eventType,
            eventDate,
            eventTime,
            guestCount,
            venueOption,
            venue,
            notes
        } = detailSource.dataset;

        setBookingDetailValue("bookingDetailsModalLabel", clientName, "Booking Details");
        setBookingDetailValue("bookingDetailsPackage", packageName);
        setBookingDetailValue("bookingDetailsReference", reference);
        setBookingDetailValue("bookingDetailsSubmittedAt", submittedAt);
        setBookingDetailValue("bookingDetailsStatusText", statusLabel);
        setBookingDetailValue("bookingDetailsPackageSummary", packageName);
        setBookingDetailValue("bookingDetailsClientName", clientName);
        setBookingDetailValue("bookingDetailsMobile", mobile);
        setBookingDetailValue("bookingDetailsEventType", eventType);
        setBookingDetailValue("bookingDetailsEventDate", eventDate);
        setBookingDetailValue("bookingDetailsEventTime", eventTime);
        setBookingDetailValue("bookingDetailsGuestCount", guestCount);
        setBookingDetailValue("bookingDetailsVenueOption", venueOption);
        setBookingDetailValue("bookingDetailsVenue", venue);
        setBookingDetailValue("bookingDetailsNotes", notes, "No notes provided.");

        setStatusPill(bookingDetailsStatusElement, statusClass, statusLabel, "pending", "Pending");

        bookingDetailsModal.show();
    };

    const openPaymentDetails = (button) => {
        if (!button || !paymentDetailsModal) {
            return;
        }

        activePaymentViewButton = button;

        const {
            paymentTitle,
            clientName,
            bookingReference,
            eventName,
            eventSchedule,
            amount,
            dueDate,
            method,
            statusLabel,
            statusClass,
            paymentTotalPaid,
            paymentPendingBalance,
            paymentLastPayment,
            paymentStage,
            paymentLog
        } = button.dataset;

        setBookingDetailValue("paymentDetailsModalLabel", paymentTitle, "Invoice Details");
        setBookingDetailValue("paymentDetailsSummary", clientName, "Client");
        setBookingDetailValue("paymentDetailsClientName", clientName);
        setBookingDetailValue("paymentDetailsBookingReference", bookingReference ? `Ref ${bookingReference}` : "Reference pending");
        setBookingDetailValue("paymentDetailsEventName", eventName);
        setBookingDetailValue("paymentDetailsEventSchedule", eventSchedule);
        setBookingDetailValue("paymentDetailsAmount", amount);
        setBookingDetailValue("paymentDetailsDueDate", dueDate ? `Due ${dueDate}` : "Due date pending");
        setBookingDetailValue("paymentDetailsMethod", method);
        setBookingDetailValue("paymentDetailsTotalPaid", paymentTotalPaid);
        setBookingDetailValue("paymentDetailsPendingBalance", paymentPendingBalance);
        setBookingDetailValue("paymentDetailsLastPayment", paymentLastPayment);
        setBookingDetailValue("paymentDetailsStage", paymentStage);
        renderPaymentReceiptPreview(button);
        renderPaymentLog(paymentLog);
        setStatusPill(paymentDetailsStatusElement, statusClass, statusLabel, "pending", "Open");

        paymentDetailsModal.show();
    };

    const openClientDetails = (button) => {
        if (!button || !clientDetailsModal) {
            return;
        }

        const {
            clientName,
            mobile,
            alternateContact,
            clientSince,
            preferredContact,
            totalBookings,
            lastBooking,
            lastReference,
            lastEventDate,
            venue,
            lastActivity,
            statusLabel,
            statusClass,
            notes,
            history,
            bookingHref
        } = button.dataset;

        setBookingDetailValue("clientDetailsModalLabel", clientName, "Client Details");
        setBookingDetailValue("clientDetailsLastBooking", lastBooking, "No active booking");
        setBookingDetailValue("clientDetailsMobile", mobile);
        setBookingDetailValue("clientDetailsAlternateContact", alternateContact);
        setBookingDetailValue("clientDetailsPreferredContact", preferredContact);
        setBookingDetailValue("clientDetailsBookingName", lastBooking, "No active booking");
        setBookingDetailValue("clientDetailsReference", lastReference, "No booking reference");
        setBookingDetailValue("clientDetailsEventDate", lastEventDate, "No event date yet");
        setBookingDetailValue("clientDetailsVenue", venue, "No venue submitted");
        setBookingDetailValue(
            "clientDetailsSummary",
            `${clientSince || "Client since not available"} | ${totalBookings || "0 bookings"} | ${lastActivity || "Last activity not available"}`,
            "Client summary not available"
        );
        renderClientHistory(history);
        setElementHref("clientDetailsBookingLink", bookingHref, "admin-bookings.php");
        setStatusPill(clientDetailsStatusElement, statusClass, statusLabel, "inactive", "Inactive");

        clientDetailsModal.show();
    };

    const confirmActivePortalPayment = () => {
        if (!activePaymentViewButton || activePaymentViewButton.dataset.paymentSourceType !== "portal") {
            return null;
        }

        const currentState = getClientPortalState();
        const bookingRequest = currentState?.bookingRequest;
        const billingDetails = currentState?.billingDetails;
        const receiptSubmission = billingDetails?.receiptSubmission;

        if (!bookingRequest || !billingDetails || !receiptSubmission?.dataUrl) {
            return null;
        }

        const nowIso = new Date().toISOString();
        const nowLabel = formatAdminDateTimeLabel(nowIso);
        const currentTotalPaidValue = Number.isFinite(billingDetails.totalPaidValue)
            ? Number(billingDetails.totalPaidValue)
            : parseCurrencyAmount(billingDetails.totalPaid || activePaymentViewButton.dataset.paymentTotalPaid);
        const estimatedTotalAmountValue = Number.isFinite(billingDetails.estimatedTotalAmountValue)
            ? Number(billingDetails.estimatedTotalAmountValue)
            : parseCurrencyAmount(billingDetails.estimatedTotalAmount);
        const existingPendingBalanceValue = Number.isFinite(billingDetails.pendingBalanceValue)
            ? Number(billingDetails.pendingBalanceValue)
            : parseCurrencyAmount(billingDetails.pendingBalance || activePaymentViewButton.dataset.paymentPendingBalance);
        const baseAmountValue = parseCurrencyAmount(billingDetails.amountDue || activePaymentViewButton.dataset.amount);
        const currentAmountValue = currentTotalPaidValue > 0 && existingPendingBalanceValue > 0
            ? existingPendingBalanceValue
            : baseAmountValue;
        const nextTotalPaidValue = currentTotalPaidValue + currentAmountValue;
        const nextPendingBalanceValue = estimatedTotalAmountValue > 0
            ? Math.max(estimatedTotalAmountValue - nextTotalPaidValue, 0)
            : Math.max(existingPendingBalanceValue - currentAmountValue, 0);
        const nextStage = nextPendingBalanceValue <= 0 ? "Fully Paid" : "Down Payment Posted";
        const nextAmountDue = nextPendingBalanceValue > 0
            ? formatCurrencyAmount(nextPendingBalanceValue)
            : formatCurrencyAmount(currentAmountValue);
        const nextNote = nextPendingBalanceValue <= 0
            ? "Payment verified and the booking is fully paid."
            : "Down payment verified. The booking still has a remaining balance for the next receipt.";
        const nextPaymentLog = appendPaymentLogEntry(
            billingDetails.paymentLog || activePaymentViewButton.dataset.paymentLog,
            {
                timeLabel: nowLabel,
                title: "Payment Verified",
                meta: nextPendingBalanceValue <= 0
                    ? `Full payment confirmed | ${formatCurrencyAmount(currentAmountValue)}`
                    : `QRPh down payment confirmed | ${formatCurrencyAmount(currentAmountValue)}`,
                statusClass: "approved",
                statusLabel: "Paid",
                note: nextNote
            }
        );
        const nextState = {
            ...currentState,
            billingDetails: {
                ...billingDetails,
                amountDue: nextAmountDue,
                statusText: "Paid",
                statusPillClass: "approved",
                paymentStage: nextStage,
                totalPaid: formatCurrencyAmount(nextTotalPaidValue),
                totalPaidValue: nextTotalPaidValue,
                pendingBalance: formatCurrencyAmount(nextPendingBalanceValue),
                pendingBalanceValue: nextPendingBalanceValue,
                lastPayment: nowLabel,
                paymentNote: nextNote,
                paymentLog: nextPaymentLog,
                estimatedTotalAmount: billingDetails.estimatedTotalAmount || formatCurrencyAmount(estimatedTotalAmountValue),
                estimatedTotalAmountValue,
                receiptSubmission: {
                    ...receiptSubmission,
                    status: "verified",
                    reviewedAt: nowIso
                }
            }
        };

        if (!saveClientPortalState(nextState)) {
            return null;
        }

        upsertPortalPaymentSourceRow();
        createPaymentLogTable();
        bindPaymentViewButtons();
        applyPaymentFilter();

        return findPaymentViewButtonByTitle(activePaymentViewButton.dataset.paymentTitle);
    };

    const syncAdminPaymentStatus = async () => {
        const invoiceNumber = activePaymentViewButton?.dataset.paymentInvoiceNumber?.trim() || "";

        if (!invoiceNumber || !paymentDetailsConfirmButton) {
            return;
        }

        setAsyncButtonState(paymentDetailsConfirmButton, true, "Refreshing...");

        try {
            const response = await window.fetch("api/payments/admin-sync-status.php", {
                method: "POST",
                headers: {
                    "Accept": "application/json",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    invoice_number: invoiceNumber
                })
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok || payload?.success === false) {
                throw new Error(payload?.message || "Payment status could not be refreshed.");
            }

            window.location.reload();
        } catch (error) {
            setAsyncButtonState(paymentDetailsConfirmButton, false);
            showPaymentActionFeedback(
                "Refresh Failed",
                error?.message || "Payment status could not be refreshed.",
                "error"
            );
        }
    };

    const sendPaymentReminder = async () => {
        const bookingId = Number.parseInt(activePaymentViewButton?.dataset.bookingId || "0", 10);
        const templateKey = activePaymentViewButton?.dataset.paymentReminderTemplate?.trim() || "";

        if (!bookingId || !templateKey || !paymentDetailsReminderButton) {
            return;
        }

        setAsyncButtonState(paymentDetailsReminderButton, true, "Sending...");

        try {
            const response = await window.fetch("api/messages/send-booking-sms.php", {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json"
                },
                credentials: "same-origin",
                body: JSON.stringify({
                    booking_id: bookingId,
                    template_key: templateKey
                })
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok || payload?.ok === false) {
                throw new Error(payload?.message || "Reminder could not be sent right now.");
            }

            showPaymentActionFeedback(
                "Reminder Sent",
                payload?.message || "Reminder sent successfully.",
                "success"
            );
        } catch (error) {
            showPaymentActionFeedback(
                "Reminder Failed",
                error?.message || "Reminder could not be sent right now.",
                "error"
            );
        } finally {
            setAsyncButtonState(paymentDetailsReminderButton, false);
        }
    };

    paymentDetailsOpenReceiptButton?.addEventListener("click", () => {
        const receiptHref = activePaymentViewButton?.dataset.receiptHref;
        const receiptDataUrl = activePaymentViewButton?.dataset.receiptDataUrl;

        if (receiptHref) {
            window.open(receiptHref, "_blank", "noopener");
            return;
        }

        if (receiptDataUrl) {
            window.open(receiptDataUrl, "_blank", "noopener");
        }
    });

    paymentDetailsConfirmButton?.addEventListener("click", () => {
        if (activePaymentViewButton?.dataset.paymentConfirmMode === "admin-sync") {
            void syncAdminPaymentStatus();
            return;
        }

        const refreshedButton = confirmActivePortalPayment();

        if (refreshedButton) {
            openPaymentDetails(refreshedButton);
        }
    });

    paymentDetailsReminderButton?.addEventListener("click", () => {
        void sendPaymentReminder();
    });

    paymentDetailsModalElement?.addEventListener("hidden.bs.modal", () => {
        activePaymentViewButton = null;
        setAsyncButtonState(paymentDetailsConfirmButton, false);
        setAsyncButtonState(paymentDetailsReminderButton, false);
    });

    navLinks.forEach((link) => {
        const href = link.getAttribute("href");
        const isDisabled = link.dataset.disabled === "true";

        if (href && href !== "#" && !href.startsWith("#")) {
            const normalizedHref = href.split("#")[0];
            link.classList.toggle("active", normalizedHref === currentPath);
        }

        link.addEventListener("click", (event) => {
            if (isDisabled || href === "#") {
                event.preventDefault();
            }

            if (window.innerWidth < 1200 && sidebarInstance) {
                window.setTimeout(() => {
                    sidebarInstance.hide();
                }, 120);
            }
        });
    });

    actionMenus.forEach((menu) => {
        const toggle = menu.querySelector("[data-action-menu-toggle]");
        const panel = menu.querySelector("[data-action-menu-panel]");
        const actionButtons = menu.querySelectorAll(".action-btn");

        if (!toggle || !panel) {
            return;
        }

        toggle.addEventListener("click", (event) => {
            event.stopPropagation();

            const shouldOpen = !menu.classList.contains("is-open");

            closeAllActionMenus(menu);
            menu.classList.toggle("is-open", shouldOpen);
            toggle.setAttribute("aria-expanded", String(shouldOpen));
            panel.hidden = !shouldOpen;

            if (shouldOpen) {
                positionActionPanel(toggle, panel);
            } else {
                resetActionPanelPosition(panel);
            }
        });

        panel.addEventListener("click", (event) => {
            event.stopPropagation();
        });

        actionButtons.forEach((button) => {
            button.addEventListener("click", () => {
                if (button.hasAttribute("data-booking-view")) {
                    openBookingDetails(button);
                }

                if (button.hasAttribute("data-client-view")) {
                    openClientDetails(button);
                }

                if (button.hasAttribute("data-payment-view")) {
                    openPaymentDetails(button);
                }

                closeActionMenu(menu);
            });
        });
    });

    bookingFilterButtons.forEach((button) => {
        button.addEventListener("click", () => {
            applyBookingFilter(button.dataset.bookingFilter || "all");
        });
    });

    bookingStatusSelect?.addEventListener("change", () => {
        applyBookingFilter(bookingStatusSelect.value || "all");
    });

    bookingViewButtons.forEach((button) => {
        if (button.closest("[data-action-menu]")) {
            return;
        }

        button.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();
            openBookingDetails(button);
        });
    });

    if (bookingFilterButtons.length && bookingRows.length) {
        const defaultFilter = Array.from(bookingFilterButtons)
            .find((button) => button.classList.contains("is-active"))
            ?.dataset.bookingFilter || "all";

        applyBookingFilter(defaultFilter);
    }

    paymentFilterButtons.forEach((button) => {
        button.addEventListener("click", () => {
            applyPaymentFilter(button.dataset.paymentFilter || "all");
        });
    });

    paymentSearchInput?.addEventListener("input", () => {
        applyPaymentFilter();
    });

    paymentStatusSelect?.addEventListener("change", () => {
        applyPaymentFilter(paymentStatusSelect.value || "all");
    });

    if (paymentFilterButtons.length && paymentRows.length) {
        applyPaymentFilter("all");
    }

    clientFilterButtons.forEach((button) => {
        button.addEventListener("click", () => {
            applyClientFilter(button.dataset.clientFilter || "all");
        });
    });

    clientSearchInput?.addEventListener("input", () => {
        applyClientFilter();
    });

    if (clientFilterButtons.length && clientRows.length) {
        applyClientFilter("all");
    }

    clientViewButtons.forEach((button) => {
        if (button.closest("[data-action-menu]")) {
            return;
        }

        button.addEventListener("click", () => {
            openClientDetails(button);
        });
    });

    clientRows.forEach((row) => {
        const triggerButton = row.querySelector("[data-client-view]");

        if (!triggerButton) {
            return;
        }

        row.addEventListener("click", (event) => {
            if (!mobileClientTableMedia.matches) {
                return;
            }

            if (event.target.closest("button, a, input, textarea, select, label")) {
                return;
            }

            openClientDetails(triggerButton);
        });
    });

    bindPaymentViewButtons();

    window.addEventListener("storage", (event) => {
        if (event.key !== CLIENT_PORTAL_STORAGE_KEY || !paymentTableBody) {
            return;
        }

        upsertPortalPaymentSourceRow();
        createPaymentLogTable();
        bindPaymentViewButtons();
        applyPaymentFilter();

        if (activePaymentViewButton?.dataset.paymentSourceType === "portal"
            && paymentDetailsModalElement?.classList.contains("show")) {
            const refreshedButton = findPaymentViewButtonByTitle(activePaymentViewButton.dataset.paymentTitle);

            if (refreshedButton) {
                openPaymentDetails(refreshedButton);
            }
        }
    });

    document.addEventListener("click", () => {
        closeAllActionMenus();
    });

    window.addEventListener("resize", () => {
        closeAllActionMenus();
    });

    window.addEventListener("scroll", () => {
        closeAllActionMenus();
    }, true);

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeAllActionMenus();
        }
    });
});
