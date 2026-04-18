const CLIENT_PORTAL_STORAGE_KEY = "emariohClientPortalState";
let bookingCancelAction = null;

document.addEventListener("DOMContentLoaded", () => {
    const currentPath = window.location.pathname.split("/").pop() || "client-dashboard.php";
    const portalState = syncClientIdentityState(getClientPortalState());

    if (getServerClientPortalState()) {
        saveClientPortalState(portalState);
    }

    hydrateBookingFormFromState(portalState);

    window.setTimeout(() => {
        applyClientPortalState(currentPath, portalState);
        initializeBookingSubmission();
        initializeBookingRequestActions();
        initializeBookingCancelModal();
        initializeBillingPaymentActions();
    }, 0);

    window.addEventListener("storage", (event) => {
        if (event.key !== CLIENT_PORTAL_STORAGE_KEY) {
            return;
        }

        applyClientPortalState(currentPath, getClientPortalState());
    });
});

function clonePortalStateValue(value) {
    if (value === null || value === undefined) {
        return value;
    }

    try {
        return JSON.parse(JSON.stringify(value));
    } catch (error) {
        return value;
    }
}

function getServerClientPortalState() {
    const rawState = window.EmariohServerClientPortalState;

    if (!rawState || typeof rawState !== "object") {
        return null;
    }

    return clonePortalStateValue(rawState);
}

function mergePortalBillingDetails(localBillingDetails, serverBillingDetails) {
    const clonedLocalBillingDetails = localBillingDetails && typeof localBillingDetails === "object"
        ? clonePortalStateValue(localBillingDetails)
        : null;
    const clonedServerBillingDetails = serverBillingDetails && typeof serverBillingDetails === "object"
        ? clonePortalStateValue(serverBillingDetails)
        : null;

    if (!clonedServerBillingDetails) {
        return clonedLocalBillingDetails;
    }

    if (!clonedLocalBillingDetails) {
        return clonedServerBillingDetails;
    }

    return {
        ...clonedLocalBillingDetails,
        ...clonedServerBillingDetails,
        invoiceNumber: clonedServerBillingDetails.invoiceNumber || clonedLocalBillingDetails.invoiceNumber || "",
        paymentMethod: clonedServerBillingDetails.paymentMethod || clonedLocalBillingDetails.paymentMethod || "PayMongo QRPh",
        amountDue: clonedServerBillingDetails.amountDue || clonedLocalBillingDetails.amountDue || "",
        description: clonedServerBillingDetails.description || clonedLocalBillingDetails.description || "",
        invoiceHref: clonedServerBillingDetails.invoiceHref || clonedLocalBillingDetails.invoiceHref || "#"
    };
}

function mergeClientPortalState(storedState, serverState) {
    if (!serverState) {
        return storedState;
    }

    const nextState = storedState && typeof storedState === "object"
        ? clonePortalStateValue(storedState)
        : {};
    const serverClientName = String(serverState.clientName || "").trim();
    const serverBookingRequest = serverState.bookingRequest && typeof serverState.bookingRequest === "object"
        ? clonePortalStateValue(serverState.bookingRequest)
        : null;
    const serverBillingDetails = serverState.billingDetails && typeof serverState.billingDetails === "object"
        ? clonePortalStateValue(serverState.billingDetails)
        : null;

    if (serverClientName) {
        nextState.clientName = serverClientName;
    }

    if (!serverBookingRequest) {
        delete nextState.bookingRequest;
        delete nextState.billingDetails;
        return nextState;
    }

    const localBookingRequest = nextState.bookingRequest && typeof nextState.bookingRequest === "object"
        ? nextState.bookingRequest
        : null;
    const localBillingDetails = nextState.billingDetails && typeof nextState.billingDetails === "object"
        ? nextState.billingDetails
        : null;
    const hasSameReference = Boolean(
        localBookingRequest
        && String(localBookingRequest.reference || "").trim()
        && String(localBookingRequest.reference || "").trim() === String(serverBookingRequest.reference || "").trim()
    );

    nextState.bookingRequest = {
        ...(hasSameReference ? clonePortalStateValue(localBookingRequest) : {}),
        ...serverBookingRequest
    };

    if (hasSameReference) {
        nextState.billingDetails = mergePortalBillingDetails(localBillingDetails, serverBillingDetails);
    } else if (serverBillingDetails) {
        nextState.billingDetails = serverBillingDetails;
    } else {
        delete nextState.billingDetails;
    }

    if (!["approved", "completed"].includes(String(serverBookingRequest.status || "").trim().toLowerCase())) {
        delete nextState.billingDetails;
    }

    return nextState;
}

function initializeBookingSubmission() {
    const bookingForm = document.querySelector(".booking-form--details");
    const submitButton = document.getElementById("bookingSubmitButton");
    const submitFeedback = document.getElementById("bookingSubmitFeedback");

    if (!bookingForm || !submitButton || bookingForm.dataset.portalBound === "true") {
        return;
    }

    bookingForm.dataset.portalBound = "true";

    bookingForm.addEventListener("submit", (event) => {
        event.preventDefault();

        const bookingRequest = collectBookingRequest(submitFeedback);

        if (!bookingRequest) {
            return;
        }

        const nextState = {
            clientName: bookingRequest.primaryContact,
            bookingRequest
        };

        submitButton.disabled = true;
        submitButton.textContent = "Saving...";

        saveClientPortalState(nextState);
        window.location.href = "client-my-bookings.php";
    });
}

function initializeBookingRequestActions() {
    const requestsSection = document.getElementById("bookingRequestsSection");

    if (!requestsSection || requestsSection.dataset.portalActionsBound === "true") {
        return;
    }

    requestsSection.dataset.portalActionsBound = "true";

    requestsSection.addEventListener("click", (event) => {
        const cancelButton = event.target.closest("[data-booking-action='cancel']");

        if (!cancelButton) {
            return;
        }

        const currentState = getClientPortalState() || {};
        const bookingRequest = currentState.bookingRequest;

        if (!bookingRequest) {
            return;
        }

        if (showBookingCancelModal(bookingRequest)) {
            return;
        }

        const cancelLabel = bookingRequest.status === "approved" ? "booking" : "request";
        const confirmed = window.confirm(`Cancel this ${cancelLabel}? You can submit a new booking again after this.`);

        if (confirmed) {
            cancelActiveBookingRequest();
        }
    });
}

function initializeBookingCancelModal() {
    const modalElement = document.getElementById("bookingCancelModal");
    const confirmButton = document.getElementById("bookingCancelConfirmButton");

    if (!modalElement || !confirmButton || modalElement.dataset.portalBound === "true") {
        return;
    }

    modalElement.dataset.portalBound = "true";

    confirmButton.addEventListener("click", () => {
        bookingCancelAction?.();
        bookingCancelAction = null;
        window.bootstrap?.Modal.getInstance(modalElement)?.hide();
    });

    modalElement.addEventListener("hidden.bs.modal", () => {
        bookingCancelAction = null;
    });
}

function initializeBillingPaymentActions() {
    const paymentSection = document.getElementById("billingPaymentSection");
    const payButton = document.getElementById("billingPayNowButton");
    const refreshButton = document.getElementById("billingRefreshPaymentButton");

    if (!paymentSection || !payButton || !refreshButton || paymentSection.dataset.portalBound === "true") {
        return;
    }

    paymentSection.dataset.portalBound = "true";

    paymentSection.addEventListener("change", (event) => {
        const optionField = event.target.closest('input[name="billingPaymentOption"]');

        if (!optionField) {
            return;
        }

        const currentState = getClientPortalState() || {};
        const bookingRequest = currentState.bookingRequest;

        if (bookingRequest?.status !== "approved") {
            return;
        }

        const billingMeta = buildBillingPageMeta(bookingRequest, currentState.billingDetails || {});
        renderBillingContextState(currentState, billingMeta);
        renderBillingPaymentState(currentState, billingMeta, true);
    });

    const syncBillingState = async (showSuccessMessage = false) => {
        const currentState = getClientPortalState() || {};
        const bookingRequest = currentState.bookingRequest;

        if (!bookingRequest?.id) {
            throw new Error("Booking reference is invalid.");
        }

        const payload = await postPortalJson("api/payments/sync-status.php", {
            booking_id: bookingRequest.id
        });
        const nextState = {
            ...currentState,
            billingDetails: payload.billing_details || currentState.billingDetails || null
        };

        saveClientPortalState(nextState);
        applyClientPortalState("client-billing.php", nextState);

        if (showSuccessMessage) {
            setBillingPaymentFeedback(
                payload.paid
                    ? "Payment confirmed. Your invoice is now marked as paid."
                    : "Billing status refreshed. If your payment was just completed, give PayMongo a moment and refresh again.",
                payload.paid ? "success" : ""
            );
        }

        return payload;
    };

    payButton.addEventListener("click", async () => {
        const currentState = getClientPortalState() || {};
        const bookingRequest = currentState.bookingRequest;
        const billingDetails = currentState.billingDetails || {};
        const billingMeta = buildBillingPageMeta(bookingRequest, billingDetails);
        const selectedPaymentOption = getSelectedBillingPaymentOptionValue(billingMeta);
        const pendingBalanceValue = Number.isFinite(billingDetails.pendingBalanceValue)
            ? Number(billingDetails.pendingBalanceValue)
            : parseCurrencyAmount(billingDetails.pendingBalance);

        if (bookingRequest?.status !== "approved" || !bookingRequest?.id) {
            setBillingPaymentFeedback("Billing is only available after the booking is approved.", "error");
            return;
        }

        if (billingDetails.statusPillClass === "approved" && pendingBalanceValue <= 0) {
            setBillingPaymentFeedback("This invoice is already marked as paid.", "success");
            return;
        }

        payButton.dataset.loading = "true";
        payButton.disabled = true;
        payButton.textContent = "Preparing QRPh...";
        setBillingPaymentFeedback("Preparing your PayMongo QRPh checkout...", "");

        try {
            const payload = await postPortalJson("api/payments/create-checkout.php", {
                booking_id: bookingRequest.id,
                payment_option: selectedPaymentOption
            });

            if (payload.already_paid) {
                delete payButton.dataset.loading;
                await syncBillingState(true);
                return;
            }

            if (!payload.checkout_url) {
                throw new Error("Checkout URL was not returned by the server.");
            }

            setBillingPaymentFeedback("Redirecting to PayMongo QRPh checkout...", "");
            window.location.assign(payload.checkout_url);
        } catch (error) {
            delete payButton.dataset.loading;
            payButton.disabled = false;
            payButton.textContent = selectedPaymentOption === "remaining_balance"
                ? "Pay Remaining Balance"
                : selectedPaymentOption === "down_payment"
                    ? "Pay Down Payment"
                    : selectedPaymentOption === "full_payment"
                        ? "Pay Full Amount"
                        : "Pay Now";
            setBillingPaymentFeedback(
                error?.message || "The PayMongo QRPh checkout could not be opened right now.",
                "error"
            );
        }
    });

    refreshButton.addEventListener("click", async () => {
        refreshButton.dataset.loading = "true";
        refreshButton.disabled = true;
        refreshButton.textContent = "Refreshing...";

        try {
            await syncBillingState(true);
        } catch (error) {
            setBillingPaymentFeedback(
                error?.message || "The latest payment status could not be refreshed right now.",
                "error"
            );
        } finally {
            delete refreshButton.dataset.loading;
            refreshButton.disabled = false;
            refreshButton.textContent = "Refresh Status";
        }
    });

    const params = new URLSearchParams(window.location.search);
    const paymentReturnState = params.get("payment");

    if (paymentReturnState === "paymongo_return") {
        refreshButton.dataset.loading = "true";
        refreshButton.disabled = true;
        refreshButton.textContent = "Refreshing...";
        setBillingPaymentFeedback("Checking the latest PayMongo payment result...", "");

        syncBillingState(true)
            .catch((error) => {
                setBillingPaymentFeedback(
                    error?.message || "We could not confirm the PayMongo payment result yet. Please refresh again.",
                    "error"
                );
            })
            .finally(() => {
                delete refreshButton.dataset.loading;
                refreshButton.disabled = false;
                refreshButton.textContent = "Refresh Status";
                clearBillingPaymentQuery();
            });
    } else if (paymentReturnState === "cancelled") {
        setBillingPaymentFeedback("PayMongo checkout was closed before payment was completed.", "");
        clearBillingPaymentQuery();
    }
}

async function postPortalJson(url, payload) {
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

function clearBillingPaymentQuery() {
    if ((window.location.pathname.split("/").pop() || "client-dashboard.php") !== "client-billing.php") {
        return;
    }

    const nextUrl = new URL(window.location.href);
    nextUrl.searchParams.delete("payment");
    nextUrl.searchParams.delete("invoice");
    window.history.replaceState({}, document.title, nextUrl.pathname + nextUrl.search + nextUrl.hash);
}

function showBookingCancelModal(bookingRequest) {
    const modalElement = document.getElementById("bookingCancelModal");
    const modalTitle = document.getElementById("bookingCancelModalLabel");
    const modalText = document.getElementById("bookingCancelModalText");
    const confirmButton = document.getElementById("bookingCancelConfirmButton");
    const bootstrapModal = window.bootstrap?.Modal;

    if (!modalElement || !modalTitle || !modalText || !confirmButton || !bootstrapModal) {
        return false;
    }

    const isApproved = bookingRequest.status === "approved";

    modalTitle.textContent = isApproved ? "Cancel Booking" : "Cancel Request";
    modalText.textContent = isApproved
        ? "Cancel this booking? It will move to the Cancelled tab and you can submit a new booking again."
        : "Cancel this request? It will move to the Cancelled tab and you can submit a new booking again.";
    confirmButton.textContent = isApproved ? "Yes, Cancel Booking" : "Yes, Cancel Request";

    bookingCancelAction = () => cancelActiveBookingRequest();
    bootstrapModal.getOrCreateInstance(modalElement).show();
    return true;
}

function cancelActiveBookingRequest() {
    const currentState = getClientPortalState() || {};
    const bookingRequest = currentState.bookingRequest;
    const currentPath = window.location.pathname.split("/").pop() || "client-dashboard.php";

    if (!bookingRequest) {
        return;
    }

    const nextState = {
        ...currentState,
        bookingRequest: {
            ...bookingRequest,
            status: "cancelled",
            cancelledAt: new Date().toISOString()
        }
    };
    delete nextState.billingDetails;

    saveClientPortalState(nextState);
    applyClientPortalState(currentPath, nextState);

    renderBookingSubmitFeedback(
        document.getElementById("bookingSubmitFeedback"),
        "Booking cancelled. You can edit the form and submit a new request anytime.",
        "info"
    );

    if (currentPath === "client-bookings.php") {
        document.getElementById("bookingFormShell")?.scrollIntoView({
            behavior: "smooth",
            block: "start"
        });
    }
}

function collectBookingRequest(submitFeedback) {
    const eventTypeField = document.getElementById("eventType");
    const eventDateField = document.getElementById("eventDate");
    const eventDateTrigger = document.getElementById("eventDateTrigger");
    const eventTimeField = document.getElementById("eventTime");
    const guestCountField = document.getElementById("guestCount");
    const venueField = document.getElementById("venue");
    const packageCategoryField = document.getElementById("packageCategory");
    const packageOptionField = document.getElementById("packageOption");
    const primaryContactField = document.getElementById("primaryContact");
    const primaryMobileField = document.getElementById("primaryMobile");
    const alternateContactField = document.getElementById("alternateContact");
    const bookingNotesField = document.getElementById("bookingNotes");

    const requiredFields = [
        eventTypeField,
        eventTimeField,
        guestCountField,
        primaryContactField,
        primaryMobileField
    ];

    for (const field of requiredFields) {
        if (!field || !field.checkValidity()) {
            field?.reportValidity();
            renderBookingSubmitFeedback(
                submitFeedback,
                "Please complete the required booking details before submitting.",
                "error"
            );
            return null;
        }
    }

    if (!eventDateField?.value) {
        renderBookingSubmitFeedback(
            submitFeedback,
            "Please choose an event date before submitting your request.",
            "error"
        );
        eventDateTrigger?.focus();
        return null;
    }

    const venueOption = document.querySelector('input[name="venueOption"]:checked')?.value || "own";
    const usingOwnVenue = venueOption === "own";

    if (usingOwnVenue && (!venueField || !venueField.value.trim())) {
        venueField?.focus();
        renderBookingSubmitFeedback(
            submitFeedback,
            "Please enter the event venue before submitting your request.",
            "error"
        );
        return null;
    }

    const submittedAt = new Date();
    const selectedPackageOption = packageOptionField?.options[packageOptionField.selectedIndex] || null;
    const catalogApi = window.EmariohPackageCatalog;
    const selectionDetails = catalogApi?.parsePackageSelectionValue
        ? catalogApi.parsePackageSelectionValue(selectedPackageOption?.value || packageOptionField?.value || "")
        : {
            packageId: selectedPackageOption?.value || packageOptionField?.value || "",
            tierLabel: ""
        };
    const packageLabel = selectedPackageOption?.textContent?.trim() || "Package pending";

    return {
        reference: generateRequestReference(submittedAt),
        status: "pending_review",
        submittedAt: submittedAt.toISOString(),
        eventType: eventTypeField.value.trim(),
        eventDate: eventDateField.value,
        eventTime: eventTimeField.value,
        guestCount: guestCountField.value,
        venueOption,
        venue: usingOwnVenue ? venueField.value.trim() : "Emarioh venue (requested)",
        packageCategoryValue: packageCategoryField?.value || "",
        packageValue: packageOptionField?.value || "",
        packageLabel,
        packageBaseValue: selectedPackageOption?.dataset.packageId || selectionDetails.packageId || "",
        packageTierLabel: selectedPackageOption?.dataset.packageTierLabel || selectionDetails.tierLabel || "",
        packageTierPrice: selectedPackageOption?.dataset.packageTierPrice || "",
        primaryContact: primaryContactField.value.trim(),
        primaryMobile: primaryMobileField.value.trim(),
        alternateContact: alternateContactField?.value.trim() || "",
        notes: bookingNotesField?.value.trim() || ""
    };
}

function hydrateBookingFormFromState(state) {
    const bookingRequest = state?.bookingRequest;

    if (!bookingRequest) {
        return;
    }

    setFieldValue("eventType", bookingRequest.eventType);
    setFieldValue("eventDate", bookingRequest.eventDate);
    setFieldValue("eventTime", bookingRequest.eventTime);
    setFieldValue("guestCount", bookingRequest.guestCount);
    setFieldValue("packageCategory", bookingRequest.packageCategoryValue);
    setFieldValue("primaryContact", bookingRequest.primaryContact);
    setFieldValue("primaryMobile", bookingRequest.primaryMobile);
    setFieldValue("alternateContact", bookingRequest.alternateContact);
    setFieldValue("bookingNotes", bookingRequest.notes);

    const selectedVenueOption = document.querySelector(`input[name="venueOption"][value="${bookingRequest.venueOption}"]`);

    if (selectedVenueOption) {
        selectedVenueOption.checked = true;
    }

    if (bookingRequest.venueOption === "own") {
        setFieldValue("venue", bookingRequest.venue);
    }
}

function applyClientPortalState(currentPath, state) {
    if (currentPath === "client-bookings.php") {
        applyBookingPageState(state);
        return;
    }

    if (currentPath === "client-my-bookings.php") {
        applyMyBookingsPageState(state);
        return;
    }

    if (currentPath === "client-billing.php") {
        applyBillingPageState(state);
        return;
    }

    if (currentPath === "client-dashboard.php") {
        applyDashboardState(state);
    }
}

function applyBookingPageState(state) {
    const bookingRequest = state?.bookingRequest;
    const submitTitle = document.getElementById("bookingSubmitTitle");
    const submitText = document.getElementById("bookingSubmitText");
    const submitButton = document.getElementById("bookingSubmitButton");
    const submitFeedback = document.getElementById("bookingSubmitFeedback");
    const packageCategoryField = document.getElementById("packageCategory");
    const packageOptionField = document.getElementById("packageOption");
    const bookingFormShell = document.getElementById("bookingFormShell");

    if (packageCategoryField && bookingRequest?.packageCategoryValue) {
        packageCategoryField.value = bookingRequest.packageCategoryValue;
        packageCategoryField.dispatchEvent(new Event("change", { bubbles: true }));
    }

    if (packageOptionField && bookingRequest?.packageValue) {
        const selectionValue = resolveBookingPackageOptionValue(bookingRequest, packageOptionField);

        if (selectionValue) {
            packageOptionField.value = selectionValue;
        }

        packageOptionField.dispatchEvent(new Event("change", { bubbles: true }));
    }

    if (bookingFormShell) {
        bookingFormShell.hidden = false;
    }

    if (!bookingRequest) {
        if (submitTitle) {
            submitTitle.textContent = "Ready To Submit?";
        }

        if (submitText) {
            submitText.textContent = "After submission, your request will appear in My Bookings while the team checks availability and prepares billing.";
        }

        if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = "Submit Request";
        }

        if (submitFeedback) {
            submitFeedback.textContent = "";
            submitFeedback.className = "booking-submit-feedback";
        }

        return;
    }

    const bookingMeta = getBookingStatusMeta(bookingRequest.status);
    const isClosedBooking = bookingRequest.status === "cancelled" || bookingRequest.status === "rejected";

    if (submitTitle) {
        submitTitle.textContent = isClosedBooking ? "Submit A New Request" : "Update Request";
    }

    if (submitText) {
        submitText.textContent = isClosedBooking
            ? "Your last request is already closed. Update the form here and submit a new booking anytime."
            : "Need to make changes? Update the form here. Track status and cancellations from My Bookings.";
    }

    if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = isClosedBooking ? "Submit New Request" : "Update Request";
    }

    if (!submitFeedback?.textContent) {
        renderBookingSubmitFeedback(
            submitFeedback,
            `Latest submission: ${formatDateTimeLabel(bookingRequest.submittedAt)}. Status: ${bookingMeta.label}.`,
            "info"
        );
    }
}

function applyMyBookingsPageState(state) {
    renderBookingTables(state);
}

function renderBookingTables(state) {
    const pendingTableBody = document.getElementById("bookingPendingTableBody");
    const approvedTableBody = document.getElementById("bookingApprovedTableBody");
    const cancelledTableBody = document.getElementById("bookingCancelledTableBody");
    const rejectedTableBody = document.getElementById("bookingRejectedTableBody");
    const bookingRequest = state?.bookingRequest;
    const pendingTab = document.getElementById("booking-pending-tab");
    const approvedTab = document.getElementById("booking-approved-tab");
    const cancelledTab = document.getElementById("booking-cancelled-tab");
    const rejectedTab = document.getElementById("booking-rejected-tab");

    if (!pendingTableBody || !approvedTableBody || !cancelledTableBody || !rejectedTableBody) {
        return;
    }

    pendingTableBody.innerHTML = `
        <tr>
            <td colspan="6">No pending booking request.</td>
        </tr>
    `;

    approvedTableBody.innerHTML = `
        <tr>
            <td colspan="6">No approved booking yet.</td>
        </tr>
    `;

    cancelledTableBody.innerHTML = `
        <tr>
            <td colspan="6">No cancelled booking yet.</td>
        </tr>
    `;

    rejectedTableBody.innerHTML = `
        <tr>
            <td colspan="6">No rejected booking yet.</td>
        </tr>
    `;

    if (!bookingRequest) {
        pendingTab?.click();
        return;
    }

    const bookingMeta = getBookingStatusMeta(bookingRequest.status);
    const rowMarkup = createBookingTableRow(bookingRequest, bookingMeta);

    if (bookingRequest.status === "approved") {
        approvedTableBody.innerHTML = rowMarkup;
        approvedTab?.click();
        return;
    }

    if (bookingRequest.status === "cancelled") {
        cancelledTableBody.innerHTML = rowMarkup;
        cancelledTab?.click();
        return;
    }

    if (bookingRequest.status === "rejected") {
        rejectedTableBody.innerHTML = rowMarkup;
        rejectedTab?.click();
        return;
    }

    pendingTableBody.innerHTML = rowMarkup;
    pendingTab?.click();
}

function applyDashboardState(state) {
    const bookingRequest = state?.bookingRequest;
    const billingDetails = state?.billingDetails;
    const bookingMeta = getBookingStatusMeta(bookingRequest?.status);
    const clientFirstName = getClientFirstName(resolveClientDisplayName(state));
    const isClosedBooking = bookingRequest?.status === "cancelled" || bookingRequest?.status === "rejected";
    const isApproved = bookingRequest?.status === "approved";
    const pendingBalanceValue = Number.isFinite(billingDetails?.pendingBalanceValue)
        ? Number(billingDetails.pendingBalanceValue)
        : parseCurrencyAmount(billingDetails?.pendingBalance);
    const isFullyPaid = isApproved && billingDetails?.statusPillClass === "approved" && pendingBalanceValue <= 0;
    const billingMeta = bookingRequest ? buildBillingPageMeta(bookingRequest, billingDetails) : null;
    const selectedPackageLabel = bookingRequest?.packageLabel && bookingRequest.packageLabel !== "Package pending"
        ? bookingRequest.packageLabel
        : "";
    const bookingStatusLabel = !bookingRequest
        ? "No active booking yet"
        : isFullyPaid
            ? "Confirmed and paid"
            : isApproved
                ? "Approved booking"
                : isClosedBooking
                    ? "Request closed"
                    : bookingMeta.label;
    const bookingStatusNote = !bookingRequest
        ? "Start by creating your event request."
        : bookingRequest.submittedAt
            ? `Last update: ${formatDateTimeLabel(bookingRequest.submittedAt)}`
            : "Latest request status.";
    const paymentStatusValue = !bookingRequest
        ? "No invoice available"
        : isFullyPaid
            ? "Paid"
            : billingMeta?.statusText || bookingMeta.paymentStatus;
    const paymentStatusNote = !bookingRequest
        ? "Invoices will appear after booking confirmation."
        : isApproved
            ? isFullyPaid
                ? "Payment has already been verified."
                : `Invoice ${billingMeta?.invoiceNumber || "details"} is ready in Billing.`
            : isClosedBooking
                ? "No active invoice for this closed request."
                : "Billing opens after approval.";
    const eventScheduleValue = bookingRequest
        ? billingMeta?.eventSchedule || "Not scheduled"
        : "No schedule set";
    const eventScheduleNote = bookingRequest
        ? billingMeta?.venue || bookingRequest.venue || "Venue to be confirmed"
        : "Choose your preferred date and time.";
    const headerSubtitle = !bookingRequest
        ? "Plan, manage, and track your catering events in one place."
        : isApproved
            ? isFullyPaid
                ? "Your booking is confirmed. Review your event details and payment record in one place."
                : "Your booking is approved. Review the invoice and complete the remaining payment on time."
            : isClosedBooking
                ? "Your previous request is closed, but you can begin a new booking anytime."
                : "Track your request progress, schedule, and billing updates from this dashboard.";
    const heroStatusLabel = !bookingRequest
        ? "Ready to start"
        : isFullyPaid
            ? "Confirmed"
            : isApproved
                ? "Approved"
                : isClosedBooking
                    ? "Closed"
                    : bookingMeta.label;
    const overviewTitle = !bookingRequest
        ? "Ready to plan your event?"
        : isApproved
            ? isFullyPaid
                ? selectedPackageLabel || bookingRequest.eventType || "Your event is confirmed"
                : "Your event is approved"
            : isClosedBooking
                ? "Ready to start a new booking?"
                : selectedPackageLabel || bookingRequest.eventType || "Your booking request is being reviewed";
    const overviewIntro = !bookingRequest
        ? "Submit your event details and let our team handle the rest, from preparation to execution."
        : isApproved
            ? isFullyPaid
                ? "Your approved event is fully paid and ready for final coordination."
                : "Your booking is approved. Review the invoice and settle the remaining amount to keep everything on schedule."
            : isClosedBooking
                ? "Your previous request is already closed. You can start a new booking anytime from the portal."
                : "Your request is under review while the team checks availability, schedule, and event details.";
    const primaryActionLabel = !bookingRequest
        ? "Book New Event"
        : isApproved
            ? isFullyPaid
                ? "View My Booking"
                : "Open Billing"
            : isClosedBooking
                ? "Book New Event"
                : "View My Booking";
    const primaryActionHref = !bookingRequest
        ? "client-bookings.php"
        : isApproved
            ? isFullyPaid
                ? "client-my-bookings.php"
                : "client-billing.php"
            : isClosedBooking
                ? "client-bookings.php"
                : "client-my-bookings.php";
    const overviewFootnote = !bookingRequest
        ? "You can track every update from My Bookings after submission."
        : isApproved
            ? isFullyPaid
                ? "Payment is confirmed. Keep your schedule and venue details updated if needed."
                : "Open Billing to review the invoice, due date, and payment instructions."
            : isClosedBooking
                ? "Use Book Event when you are ready to submit a new request."
                : "The team will update your status once availability has been reviewed.";

    setText("dashboardHeaderTitle", `Welcome back, ${clientFirstName}`);
    setText("dashboardHeaderSubtitle", headerSubtitle);
    setText("dashboardBookingStatusValue", bookingStatusLabel);
    setText("dashboardBookingStatusValueSummary", bookingStatusLabel);
    setText("dashboardBookingStatusNote", bookingStatusNote);
    setText("dashboardBookingStatusNoteSummary", bookingStatusNote);
    setText("dashboardPaymentStatusValue", paymentStatusValue);
    setText("dashboardPaymentStatusValueSummary", paymentStatusValue);
    setText("dashboardPaymentStatusNote", paymentStatusNote);
    setText("dashboardPaymentStatusNoteSummary", paymentStatusNote);
    setText("dashboardEventScheduleValue", eventScheduleValue);
    setText("dashboardEventScheduleValueSummary", eventScheduleValue);
    setText("dashboardEventScheduleNote", eventScheduleNote);
    setText("dashboardEventScheduleNoteSummary", eventScheduleNote);
    setText("dashboardOverviewTitle", overviewTitle);
    setText("dashboardOverviewIntro", overviewIntro);
    setStatusPill("dashboardOverviewStatusPill", heroStatusLabel, !bookingRequest || isClosedBooking ? (bookingRequest ? bookingMeta.pillClass : "pending") : isApproved ? "confirmed" : bookingMeta.pillClass);
    setText("dashboardOverviewEvent", bookingRequest ? (selectedPackageLabel || bookingRequest.eventType || "Not selected") : "Not selected");
    setText("dashboardOverviewSchedule", bookingRequest ? (billingMeta?.eventSchedule || "Not scheduled") : "Not scheduled");
    setText("dashboardOverviewVenue", bookingRequest ? (billingMeta?.venue || bookingRequest.venue || "To be decided") : "To be decided");
    setText("dashboardOverviewGuests", bookingRequest ? (billingMeta?.guestCountLabel || "Not specified") : "Not specified");
    setText("dashboardOverviewReference", bookingRequest?.reference || "Not available yet");
    setText("dashboardOverviewStage", bookingRequest ? bookingMeta.stageValue : "Pre-booking");
    setAction("dashboardPrimaryAction", primaryActionLabel, primaryActionHref);
    setText("dashboardOverviewFootnote", overviewFootnote);
    setText("dashboardOverviewFootnoteSecondary", overviewFootnote);

}

function applyBillingPageState(state) {
    const bookingRequest = state?.bookingRequest;
    const billingDetails = state?.billingDetails;
    const isApproved = bookingRequest?.status === "approved";
    const hasRequest = Boolean(bookingRequest);
    const isClosedBooking = bookingRequest?.status === "cancelled" || bookingRequest?.status === "rejected";
    const bookingMeta = getBookingStatusMeta(bookingRequest?.status);

    setHidden("billingPendingState", isApproved);
    setHidden("billingApprovedContent", !isApproved);

    if (!isApproved) {
        const shouldShowPendingBookingLink = hasRequest;
        const shouldShowPendingPrimaryAction = hasRequest;

        setText("billingTopbarLabel", "Billing Status");
        setText("billingTopbarValue", hasRequest ? bookingMeta.label : "No Booking Yet");
        setHidden("billingPaymentSection", true);
        setHidden("billingReceiptSection", true);
        setText(
            "billingPendingTitle",
            !hasRequest
                ? "Submit a booking first"
                : bookingRequest.status === "cancelled"
                    ? "This booking was cancelled"
                    : bookingRequest.status === "rejected"
                        ? "This booking was rejected"
                        : "Payment opens after approval"
        );
        setText(
            "billingPendingText",
            !hasRequest
                ? "You need to submit a booking request first. Once approved, this page will show your invoice, payment method, and payment instructions."
                : bookingRequest.status === "cancelled"
                    ? "This booking was cancelled, so there is no active invoice linked to it. You can submit a new request anytime."
                    : bookingRequest.status === "rejected"
                        ? "This booking request was rejected, so billing was not opened. You can revise your details and submit a new request anytime."
                        : "Your booking request is still being reviewed. Once approved, this page will show your invoice, payment method, and payment instructions."
        );
        setHidden("billingPendingLink", !shouldShowPendingBookingLink);
        setHidden("billingPendingActions", !shouldShowPendingPrimaryAction);

        if (shouldShowPendingBookingLink) {
            setAction("billingPendingLink", "View booking", "client-my-bookings.php");
        }

        if (shouldShowPendingPrimaryAction) {
            setAction("billingPendingPrimaryAction", "Go To My Bookings", "client-my-bookings.php");
        }

        return;
    }

    const billingMeta = buildBillingPageMeta(bookingRequest, billingDetails);
    const pendingBalanceValue = Number.isFinite(billingDetails?.pendingBalanceValue)
        ? Number(billingDetails.pendingBalanceValue)
        : parseCurrencyAmount(billingDetails?.pendingBalance || billingMeta.pendingBalance);
    const isFullyPaid = billingDetails?.statusPillClass === "approved" && pendingBalanceValue <= 0;
    renderBillingPaymentChoiceState(billingMeta);
    renderBillingContextState(state, billingMeta);
    renderBillingPaymentState(state, billingMeta, true);
    renderBillingReceiptState(state, billingMeta, true);
}
function getBookingStatusMeta(status) {
    if (status === "approved") {
        return {
            label: "Approved",
            pillClass: "confirmed",
            paymentStatus: "Open",
            stageValue: "Awaiting Payment"
        };
    }

    if (status === "cancelled") {
        return {
            label: "Cancelled",
            pillClass: "cancelled",
            paymentStatus: "Cancelled",
            stageValue: "Booking Closed"
        };
    }

    if (status === "rejected") {
        return {
            label: "Rejected",
            pillClass: "rejected",
            paymentStatus: "Rejected",
            stageValue: "Booking Closed"
        };
    }

    return {
        label: "Pending Review",
        pillClass: "pending",
        paymentStatus: "No Invoice Yet",
        stageValue: "Availability Check"
    };
}

function createBookingTableRow(bookingRequest, bookingMeta) {
    const cancelLabel = bookingRequest.status === "approved" ? "Cancel Booking" : "Cancel Request";
    const actionMarkup = bookingRequest.status === "approved" || bookingRequest.status === "pending_review"
        ? `<button class="booking-request-action" type="button" data-booking-action="cancel">${escapeHtml(cancelLabel)}</button>`
        : `<span class="booking-request-action booking-request-action--muted">Closed</span>`;

    return `
        <tr>
            <td>${escapeHtml(`${formatDateLabel(bookingRequest.eventDate, "compact")} - ${formatTimeLabel(bookingRequest.eventTime)}`)}</td>
            <td>${escapeHtml(bookingRequest.eventType)}</td>
            <td>${escapeHtml(`${bookingRequest.guestCount} pax`)}</td>
            <td>${escapeHtml(bookingRequest.venue)}</td>
            <td><span class="status-pill status-pill--${bookingMeta.pillClass}">${escapeHtml(bookingMeta.label)}</span></td>
            <td>${actionMarkup}</td>
        </tr>
    `;
}

function getClientPortalState() {
    let storedState = null;

    try {
        const storedValue = window.localStorage.getItem(CLIENT_PORTAL_STORAGE_KEY);
        storedState = storedValue ? JSON.parse(storedValue) : null;
    } catch (error) {
        storedState = null;
    }

    return mergeClientPortalState(storedState, getServerClientPortalState());
}

function syncClientIdentityState(state) {
    const params = new URLSearchParams(window.location.search);
    const queryName = params.get("full_name")?.trim() || params.get("name")?.trim() || "";
    const storedName = String(state?.clientName || "").trim();
    const bookingName = String(state?.bookingRequest?.primaryContact || "").trim();
    const resolvedName = queryName || storedName || bookingName;

    if (!resolvedName) {
        return state;
    }

    const nextState = {
        ...(state || {}),
        clientName: resolvedName
    };

    if (nextState.bookingRequest && !String(nextState.bookingRequest.primaryContact || "").trim()) {
        nextState.bookingRequest = {
            ...nextState.bookingRequest,
            primaryContact: resolvedName
        };
    }

    if (queryName && queryName !== storedName) {
        saveClientPortalState(nextState);
    }

    return nextState;
}

function saveClientPortalState(state) {
    try {
        window.localStorage.setItem(CLIENT_PORTAL_STORAGE_KEY, JSON.stringify(state));
        return true;
    } catch (error) {
        // Ignore storage failures and keep the page usable.
        return false;
    }
}

function setFieldValue(id, value) {
    const field = document.getElementById(id);

    if (field && typeof value === "string") {
        field.value = value;
    }
}

function setText(id, value) {
    const element = document.getElementById(id);

    if (element) {
        element.textContent = value;
    }
}

function setAction(id, label, href) {
    const element = document.getElementById(id);

    if (!element) {
        return;
    }

    element.textContent = label;
    element.setAttribute("href", href);
}

function setHidden(id, shouldHide) {
    const element = document.getElementById(id);

    if (element) {
        element.hidden = shouldHide;
    }
}

function setStatusPill(id, label, pillClass) {
    const element = document.getElementById(id);

    if (!element) {
        return;
    }

    element.textContent = label;
    element.className = "status-pill";
    element.classList.add(`status-pill--${pillClass}`);
}

function renderBookingSubmitFeedback(target, message, tone) {
    if (!target) {
        return;
    }

    target.textContent = message;
    target.className = "booking-submit-feedback";

    if (tone) {
        target.classList.add(`booking-submit-feedback--${tone}`);
    }
}

function setBillingPaymentFeedback(message, tone) {
    const feedbackElement = document.getElementById("billingPaymentFeedback");

    if (!feedbackElement) {
        return;
    }

    feedbackElement.textContent = message;
    feedbackElement.className = "billing-payment-feedback";

    if (tone) {
        feedbackElement.classList.add(`is-${tone}`);
    }
}

function getBillingPaymentOptionByValue(billingMeta, optionValue) {
    const paymentOptions = Array.isArray(billingMeta?.paymentOptions) ? billingMeta.paymentOptions : [];
    const normalizedOptionValue = String(optionValue || "").trim();

    if (!normalizedOptionValue) {
        return paymentOptions[0] || null;
    }

    return paymentOptions.find((option) => option.value === normalizedOptionValue) || paymentOptions[0] || null;
}

function getSelectedBillingPaymentOptionValue(billingMeta) {
    const checkedField = document.querySelector('input[name="billingPaymentOption"]:checked');

    if (checkedField) {
        return String(checkedField.value || "").trim();
    }

    return String(
        billingMeta?.selectedPaymentOption
        || billingMeta?.currentPaymentOption?.value
        || billingMeta?.paymentOptions?.[0]?.value
        || "full_payment"
    ).trim();
}

function renderBillingPaymentChoiceState(billingMeta) {
    const choiceGroup = document.getElementById("billingPaymentChoiceGroup");
    const choiceNote = document.getElementById("billingPaymentChoiceNote");
    const choiceOptions = document.getElementById("billingPaymentChoiceOptions");
    const paymentOptions = Array.isArray(billingMeta?.paymentOptions) ? billingMeta.paymentOptions : [];
    const selectedValue = String(
        billingMeta?.selectedPaymentOption
        || paymentOptions[0]?.value
        || ""
    ).trim();

    if (!choiceGroup || !choiceNote || !choiceOptions) {
        return;
    }

    choiceOptions.innerHTML = "";
    choiceNote.textContent = billingMeta?.paymentChoiceNote || "Choose what you want to pay today.";
    choiceGroup.hidden = paymentOptions.length < 2;

    if (paymentOptions.length < 2) {
        return;
    }

    paymentOptions.forEach((option, index) => {
        choiceOptions.insertAdjacentHTML("beforeend", `
            <label class="billing-payment-option">
                <input
                    class="billing-payment-option__input"
                    type="radio"
                    name="billingPaymentOption"
                    value="${escapeHtml(option.value)}"
                    ${option.value === selectedValue || (!selectedValue && index === 0) ? "checked" : ""}
                >
                <span class="billing-payment-option__copy">
                    <span class="billing-payment-option__head">
                        <strong class="billing-payment-option__label">${escapeHtml(option.label)}</strong>
                        <span class="billing-payment-option__amount">${escapeHtml(option.amount)}</span>
                    </span>
                    <small class="billing-payment-option__description">${escapeHtml(option.description || "Pay the amount shown for this option.")}</small>
                </span>
            </label>
        `);
    });
}

function renderBillingContextState(state, billingMeta) {
    const billingDetails = state?.billingDetails || {};
    const selectedOption = getBillingPaymentOptionByValue(billingMeta, getSelectedBillingPaymentOptionValue(billingMeta));
    const pendingBalanceValue = Number.isFinite(billingDetails.pendingBalanceValue)
        ? Number(billingDetails.pendingBalanceValue)
        : parseCurrencyAmount(billingDetails.pendingBalance || billingMeta.pendingBalance);
    const selectedAmountValue = Number.isFinite(selectedOption?.amountValue)
        ? Number(selectedOption.amountValue)
        : parseCurrencyAmount(selectedOption?.amount || billingMeta.amountDue);
    const remainingAfterPaymentValue = Math.max(pendingBalanceValue - selectedAmountValue, 0);
    const optionValue = String(selectedOption?.value || "").trim();
    const optionLabel = selectedOption?.label || billingMeta?.selectedPaymentOptionLabel || "Payment";
    const sectionTitle = optionValue === "remaining_balance"
        ? "Complete your remaining balance"
        : optionValue === "down_payment"
            ? "Pay your down payment"
            : optionValue === "full_payment"
                ? "Pay your full booking amount"
                : "Pay your booking";
    const afterPaymentText = remainingAfterPaymentValue <= 0.00001
        ? "No balance left after this payment"
        : `${formatBillingAmount(remainingAfterPaymentValue)} left after this payment`;

    setText("billingPaymentFocusAmountValue", selectedOption?.amount || billingMeta.amountDue);
    setText("billingPaymentFocusOptionValue", optionLabel);
    setText("billingPaymentAfterValue", afterPaymentText);
    setText("billingPaymentDueInlineValue", billingMeta.dueValue);

    setStatusPill("billingInvoiceStatusPill", billingMeta.statusText, billingMeta.statusPillClass || "pending");
    setText("billingInvoiceNumberValue", billingMeta.invoiceNumber);
    setText("billingInvoiceBookingValue", billingMeta.bookingReference);
    setText("billingInvoiceEventValue", billingMeta.eventName);
    setText("billingInvoiceScheduleValue", billingMeta.eventSchedule);
    setText("billingInvoiceVenueValue", billingMeta.venue);
    setText("billingInvoiceOptionValue", optionLabel);
    setText("billingInvoicePaidValue", billingMeta.totalPaid);
    setText("billingInvoiceBalanceValue", billingMeta.pendingBalance);
}

function renderBillingPaymentState(state, billingMeta, isApproved) {
    const paymentSection = document.getElementById("billingPaymentSection");
    const introText = document.getElementById("billingPaymentIntroText");
    const feedbackElement = document.getElementById("billingPaymentFeedback");
    const payButton = document.getElementById("billingPayNowButton");
    const refreshButton = document.getElementById("billingRefreshPaymentButton");
    const billingDetails = state?.billingDetails || {};
    const pendingBalanceValue = Number.isFinite(billingDetails.pendingBalanceValue)
        ? Number(billingDetails.pendingBalanceValue)
        : parseCurrencyAmount(billingDetails.pendingBalance || billingMeta.pendingBalance);
    const isFullyPaid = billingDetails.statusPillClass === "approved" && pendingBalanceValue <= 0;
    const isPaymongoEnabled = Boolean(billingDetails.isPaymongoEnabled);
    const selectedOption = getBillingPaymentOptionByValue(billingMeta, getSelectedBillingPaymentOptionValue(billingMeta));
    const selectedOptionValue = String(selectedOption?.value || "").trim();
    const payButtonLabel = selectedOptionValue === "remaining_balance"
        ? "Pay Remaining Balance"
        : selectedOptionValue === "down_payment"
            ? "Pay Down Payment"
            : selectedOptionValue === "full_payment"
                ? "Pay Full Amount"
                : "Pay Now";
    const defaultFeedback = isFullyPaid
        ? "Payment confirmed. This invoice is already marked as paid."
        : isPaymongoEnabled
            ? "Open the QRPh checkout, finish the payment, then refresh this page."
            : "PayMongo QRPh checkout is not configured yet. Please contact the admin first.";

    if (!paymentSection || !payButton || !refreshButton) {
        return;
    }

    setHidden("billingPaymentSection", !isApproved || isFullyPaid);

    if (!isApproved) {
        return;
    }

    if (introText) {
        introText.textContent = isFullyPaid
            ? "Your payment was confirmed by PayMongo."
            : isPaymongoEnabled
                ? (selectedOption?.description || "Open the secure PayMongo QRPh checkout and complete your payment there.")
                : "PayMongo QRPh checkout is not configured yet. Once the admin finishes the setup, you can pay from here directly.";
    }
    payButton.disabled = !isPaymongoEnabled || isFullyPaid || !selectedOption;
    payButton.textContent = isFullyPaid
        ? "Payment Received"
        : (payButton.dataset.loading === "true" ? "Preparing QRPh..." : payButtonLabel);
    refreshButton.disabled = refreshButton.dataset.loading === "true";
    refreshButton.textContent = refreshButton.dataset.loading === "true" ? "Refreshing..." : "Refresh Status";

    if (!feedbackElement?.classList.contains("is-success") && !feedbackElement?.classList.contains("is-error")) {
        setBillingPaymentFeedback(defaultFeedback, isFullyPaid ? "success" : "");
    }
}

function renderBillingReceiptState(state, billingMeta, isApproved) {
    const receiptSection = document.getElementById("billingReceiptSection");
    const receiptIntroText = document.getElementById("billingReceiptIntroText");
    const receiptFeedback = document.getElementById("billingReceiptFeedback");
    const receiptViewLink = document.getElementById("billingReceiptViewLink");
    const receiptDownloadLink = document.getElementById("billingReceiptDownloadLink");
    const receiptStatusValue = document.getElementById("billingReceiptStatusValue");
    const receiptStatusPill = document.getElementById("billingReceiptStatusPill");
    const receiptReferenceValue = document.getElementById("billingReceiptReferenceValue");
    const receiptInvoiceValue = document.getElementById("billingReceiptInvoiceValue");
    const receiptAmountValue = document.getElementById("billingReceiptAmountValue");
    const receiptPaidAtValue = document.getElementById("billingReceiptPaidAtValue");
    const receiptNote = document.getElementById("billingReceiptNote");
    const billingDetails = state?.billingDetails || {};
    const pendingBalanceValue = Number.isFinite(billingDetails.pendingBalanceValue)
        ? Number(billingDetails.pendingBalanceValue)
        : parseCurrencyAmount(billingDetails.pendingBalance || billingMeta.pendingBalance);
    const isFullyPaid = billingDetails.statusPillClass === "approved" && pendingBalanceValue <= 0;
    const hasReceipt = Boolean(billingMeta.receiptHref);

    if (!receiptSection) {
        return;
    }

    setHidden("billingReceiptSection", !isApproved || !isFullyPaid || !hasReceipt);

    if (!isApproved || !isFullyPaid || !hasReceipt) {
        return;
    }

    if (receiptIntroText) {
        receiptIntroText.textContent = "PayMongo already confirmed your payment. Your system receipt is ready to open, download, print, or screenshot.";
    }

    if (receiptFeedback) {
        receiptFeedback.textContent = "Payment posted successfully. The PayMongo checkout step is complete and your receipt is now available.";
    }

    if (receiptViewLink) {
        receiptViewLink.setAttribute("href", billingMeta.receiptHref);
    }

    if (receiptDownloadLink) {
        receiptDownloadLink.setAttribute("href", billingMeta.receiptDownloadHref || billingMeta.receiptHref);
    }

    if (receiptStatusValue) {
        receiptStatusValue.textContent = "Paid Successful";
    }

    if (receiptStatusPill) {
        receiptStatusPill.textContent = "Paid";
        receiptStatusPill.className = "status-pill status-pill--confirmed";
    }

    if (receiptReferenceValue) {
        receiptReferenceValue.textContent = billingMeta.receiptReference || "Receipt ready";
    }

    if (receiptInvoiceValue) {
        receiptInvoiceValue.textContent = billingMeta.invoiceNumber;
    }

    if (receiptAmountValue) {
        receiptAmountValue.textContent = billingMeta.totalPaid || billingMeta.amountDue;
    }

    if (receiptPaidAtValue) {
        receiptPaidAtValue.textContent = billingMeta.paidAt ? formatDateTimeLabel(billingMeta.paidAt) : "Recently";
    }

    if (receiptNote) {
        receiptNote.textContent = billingMeta.paymentReference
            ? `Reference ${billingMeta.paymentReference} was posted successfully. Keep this receipt for your records.`
            : "Keep this receipt for your records. You can always return to Billing to open it again.";
    }
}

function formatDateLabel(dateValue, style) {
    if (!dateValue) {
        return "TBA";
    }

    const [year, month, day] = dateValue.split("-").map(Number);

    if (!year || !month || !day) {
        return "TBA";
    }

    const options = style === "compact"
        ? { month: "short", day: "numeric" }
        : { month: "long", day: "numeric", year: "numeric" };

    return new Intl.DateTimeFormat("en-PH", options).format(new Date(year, month - 1, day));
}

function formatDateTimeLabel(dateValue) {
    if (!dateValue) {
        return "recently";
    }

    return new Intl.DateTimeFormat("en-PH", {
        month: "short",
        day: "numeric",
        year: "numeric",
        hour: "numeric",
        minute: "2-digit"
    }).format(new Date(dateValue));
}

function formatTimeLabel(timeValue) {
    if (!timeValue || !timeValue.includes(":")) {
        return "Time pending";
    }

    const [hours, minutes] = timeValue.split(":").map(Number);
    const date = new Date();

    date.setHours(hours || 0, minutes || 0, 0, 0);

    return new Intl.DateTimeFormat("en-PH", {
        hour: "numeric",
        minute: "2-digit"
    }).format(date);
}

function formatAdminLogDateTimeLabel(dateValue) {
    if (!dateValue) {
        return "Date not provided";
    }

    const date = new Date(dateValue);
    const dateLabel = new Intl.DateTimeFormat("en-PH", {
        month: "long",
        day: "numeric",
        year: "numeric"
    }).format(date);
    const timeLabel = new Intl.DateTimeFormat("en-PH", {
        hour: "numeric",
        minute: "2-digit"
    }).format(date);

    return `${dateLabel} | ${timeLabel}`;
}

function parseCurrencyAmount(value) {
    const normalizedValue = String(value || "").replace(/[^0-9.]/g, "");
    const parsedValue = Number.parseFloat(normalizedValue);

    return Number.isFinite(parsedValue) ? parsedValue : 0;
}

function parseGuestCount(value) {
    const parsedValue = Number.parseInt(String(value || "").replace(/[^0-9]/g, ""), 10);
    return Number.isFinite(parsedValue) ? parsedValue : 0;
}

function estimateBookingTotalAmount(bookingRequest) {
    const selectedPackage = getCatalogPackageForBooking(bookingRequest);
    const selectedTier = getPackagePricingTierForBooking(bookingRequest, selectedPackage);
    const guestCount = parseGuestCount(bookingRequest?.guestCount);

    if (!selectedPackage) {
        return 0;
    }

    if (selectedTier?.price) {
        return parseCurrencyAmount(selectedTier.price);
    }

    if (Array.isArray(selectedPackage.pricingTiers) && selectedPackage.pricingTiers.length) {
        const matchedTier = selectedPackage.pricingTiers
            .map((tier) => ({
                ...tier,
                guestCount: parseGuestCount(tier.label)
            }))
            .sort((firstTier, secondTier) => Math.abs(firstTier.guestCount - guestCount) - Math.abs(secondTier.guestCount - guestCount))[0];

        return parseCurrencyAmount(matchedTier?.price);
    }

    const rateLabel = String(selectedPackage.rateLabel || "");
    const perHeadMatch = rateLabel.match(/([\d,]+)\s*\/\s*head/i);

    if (perHeadMatch && guestCount) {
        return parseCurrencyAmount(perHeadMatch[1]) * guestCount;
    }

    return parseCurrencyAmount(rateLabel);
}

function getCatalogPackageForBooking(bookingRequest) {
    const catalogApi = window.EmariohPackageCatalog;
    const selectionDetails = catalogApi?.parsePackageSelectionValue
        ? catalogApi.parsePackageSelectionValue(bookingRequest?.packageValue)
        : {
            packageId: bookingRequest?.packageValue || "",
            tierLabel: ""
        };
    const packageId = String(bookingRequest?.packageBaseValue || selectionDetails.packageId || bookingRequest?.packageValue || "").trim();

    if (!catalogApi || !packageId) {
        return null;
    }

    return catalogApi.getPackageById(packageId);
}

function getPackagePricingTierForBooking(bookingRequest, packageItem = null) {
    const catalogApi = window.EmariohPackageCatalog;
    const selectedPackage = packageItem || getCatalogPackageForBooking(bookingRequest);
    const tierLabel = String(
        bookingRequest?.packageTierLabel
        || catalogApi?.parsePackageSelectionValue?.(bookingRequest?.packageValue)?.tierLabel
        || ""
    ).trim();

    if (!catalogApi || !selectedPackage || !tierLabel) {
        return null;
    }

    return catalogApi.getPackagePricingTier(selectedPackage, tierLabel);
}

function getPackageDownPaymentAmount(packageItem, tierLabel = "") {
    const catalogApi = window.EmariohPackageCatalog;

    if (catalogApi?.getPackageDownPaymentAmount) {
        return String(catalogApi.getPackageDownPaymentAmount(packageItem, tierLabel) || "").trim();
    }

    return String(packageItem?.downPaymentAmount || "").trim();
}

function getPackageAllowsDownPayment(packageItem) {
    return Boolean(packageItem?.allowDownPayment);
}

function resolveBookingPackageOptionValue(bookingRequest, packageOptionField) {
    const optionElements = Array.from(packageOptionField?.options || []);
    const catalogApi = window.EmariohPackageCatalog;
    const selectionDetails = catalogApi?.parsePackageSelectionValue
        ? catalogApi.parsePackageSelectionValue(bookingRequest?.packageValue)
        : {
            packageId: bookingRequest?.packageValue || "",
            tierLabel: ""
        };
    const packageId = String(bookingRequest?.packageBaseValue || selectionDetails.packageId || "").trim();
    const tierLabel = String(bookingRequest?.packageTierLabel || selectionDetails.tierLabel || "").trim();

    if (!optionElements.length) {
        return "";
    }

    const exactMatch = optionElements.find((optionElement) => optionElement.value === bookingRequest?.packageValue);

    if (exactMatch) {
        return exactMatch.value;
    }

    const tierMatch = optionElements.find((optionElement) => (
        optionElement.dataset.packageId === packageId
        && (!tierLabel || optionElement.dataset.packageTierLabel === tierLabel)
    ));

    if (tierMatch) {
        return tierMatch.value;
    }

    const packageMatch = optionElements.find((optionElement) => optionElement.dataset.packageId === packageId);
    return packageMatch?.value || optionElements[0]?.value || "";
}

function getSharedPaymentSettings() {
    return window.EmariohPaymentSettings?.getSettings?.() || null;
}

function formatSentenceFragment(value, fallbackValue) {
    const normalizedValue = String(value || "").trim() || fallbackValue;
    return normalizedValue.charAt(0).toLowerCase() + normalizedValue.slice(1);
}

function getClientFirstName(value) {
    const normalizedValue = String(value || "").trim();

    if (!normalizedValue) {
        return "Client";
    }

    const firstToken = normalizedValue.split(/\s+/)[0] || "Client";
    return firstToken.charAt(0).toUpperCase() + firstToken.slice(1);
}

function resolveClientDisplayName(state) {
    const storedName = String(state?.clientName || "").trim();

    if (storedName) {
        return storedName;
    }

    const bookingName = state?.bookingRequest?.primaryContact;

    if (String(bookingName || "").trim()) {
        return bookingName;
    }

    const sidebarName = document.querySelector(".sidebar-account__name")?.textContent?.trim();

    if (sidebarName) {
        return sidebarName;
    }

    const profileName = document.querySelector(".client-profile-panel__row strong")?.textContent?.trim();

    if (profileName) {
        return profileName;
    }

    return "Client";
}

function generateRequestReference(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    const hours = String(date.getHours()).padStart(2, "0");
    const minutes = String(date.getMinutes()).padStart(2, "0");

    return `REQ-${year}${month}${day}-${hours}${minutes}`;
}

function buildBillingPageMeta(bookingRequest, billingDetails) {
    const packageLabel = bookingRequest?.packageLabel;
    const selectedPackage = getCatalogPackageForBooking(bookingRequest);
    const selectedTier = getPackagePricingTierForBooking(bookingRequest, selectedPackage);
    const paymentSettings = getSharedPaymentSettings();
    const allowFullPayment = typeof billingDetails?.allowFullPayment === "boolean"
        ? billingDetails.allowFullPayment
        : Boolean(paymentSettings?.allowFullPayment);
    const packageAllowsDownPayment = typeof billingDetails?.packageAllowsDownPayment === "boolean"
        ? billingDetails.packageAllowsDownPayment
        : (typeof bookingRequest?.packageAllowsDownPayment === "boolean"
            ? bookingRequest.packageAllowsDownPayment
            : getPackageAllowsDownPayment(selectedPackage));
    const packageDownPaymentAmount = String(
        billingDetails?.downPaymentAmount
        || bookingRequest?.packageDownPaymentAmount
        || (packageAllowsDownPayment
            ? getPackageDownPaymentAmount(selectedPackage, selectedTier?.label || bookingRequest?.packageTierLabel || "")
            : "")
    ).trim();
    const downPaymentAmountValue = Number.isFinite(billingDetails?.downPaymentAmountValue)
        ? Number(billingDetails.downPaymentAmountValue)
        : parseCurrencyAmount(packageDownPaymentAmount);
    const estimatedTotalAmount = estimateBookingTotalAmount(bookingRequest);
    const totalAmountValue = Number.isFinite(billingDetails?.totalAmountValue)
        ? Number(billingDetails.totalAmountValue)
        : (estimatedTotalAmount > 0
            ? estimatedTotalAmount
            : parseCurrencyAmount(bookingRequest?.packageTierPrice || ""));
    const pendingBalanceValue = Number.isFinite(billingDetails?.pendingBalanceValue)
        ? Number(billingDetails.pendingBalanceValue)
        : parseCurrencyAmount(
            billingDetails?.pendingBalance
            || billingDetails?.amountToPayNow
            || billingDetails?.amountDue
            || totalAmountValue
        );
    const totalPaidValue = Number.isFinite(billingDetails?.totalPaidValue)
        ? Number(billingDetails.totalPaidValue)
        : parseCurrencyAmount(billingDetails?.totalPaid || "");
    const amountToPayNowValue = Number.isFinite(billingDetails?.amountToPayNowValue)
        ? Number(billingDetails.amountToPayNowValue)
        : (Number.isFinite(billingDetails?.amountDueValue)
            ? Number(billingDetails.amountDueValue)
            : 0);
    const totalAmount = formatBillingAmount(
        billingDetails?.totalAmount
        || (totalAmountValue > 0 ? totalAmountValue : bookingRequest?.packageTierPrice || "")
    );
    const pendingBalance = formatBillingAmount(
        billingDetails?.pendingBalance
        || (pendingBalanceValue > 0 || totalPaidValue > 0 ? pendingBalanceValue : totalAmountValue)
    );
    const totalPaid = formatBillingAmount(
        billingDetails?.totalPaid
        || totalPaidValue
    );
    const hasPartialPayment = totalPaidValue > 0 && pendingBalanceValue > 0.00001;
    const canChooseDownPayment = packageAllowsDownPayment
        && downPaymentAmountValue > 0
        && totalAmountValue > 0
        && downPaymentAmountValue < totalAmountValue
        && !hasPartialPayment;
    const paymentOptions = [];

    if (pendingBalanceValue > 0.00001) {
        if (hasPartialPayment) {
            paymentOptions.push({
                value: "remaining_balance",
                label: "Remaining Balance",
                amount: pendingBalance,
                amountValue: pendingBalanceValue,
                description: "Pay the remaining balance to complete your booking payment."
            });
        } else {
            if (canChooseDownPayment) {
                paymentOptions.push({
                    value: "down_payment",
                    label: "Down Payment",
                    amount: formatBillingAmount(packageDownPaymentAmount || downPaymentAmountValue),
                    amountValue: downPaymentAmountValue,
                    description: "Pay only the reservation amount today."
                });
            }

            if (allowFullPayment || !canChooseDownPayment) {
                const fullPaymentAmountValue = pendingBalanceValue > 0 ? pendingBalanceValue : totalAmountValue;

                paymentOptions.push({
                    value: "full_payment",
                    label: "Full Payment",
                    amount: formatBillingAmount(fullPaymentAmountValue),
                    amountValue: fullPaymentAmountValue,
                    description: "Pay the full amount today to finish your booking payment."
                });
            }
        }
    }

    if (!paymentOptions.length) {
        paymentOptions.push({
            value: "full_payment",
            label: pendingBalanceValue > 0.00001 ? "Pay Now" : "Paid",
            amount: formatBillingAmount(amountToPayNowValue || pendingBalanceValue || totalAmountValue || 0),
            amountValue: amountToPayNowValue || pendingBalanceValue || totalAmountValue || 0,
            description: pendingBalanceValue > 0.00001
                ? "Pay the available balance for this booking."
                : "Your booking payment is complete."
        });
    }

    const requestedPaymentOption = String(
        billingDetails?.selectedPaymentOption
        || paymentOptions[0]?.value
        || "full_payment"
    ).trim();
    const currentPaymentOption = paymentOptions.find((option) => option.value === requestedPaymentOption) || paymentOptions[0];
    const amountDue = currentPaymentOption?.amount
        || formatBillingAmount(
            billingDetails?.amountToPayNow
            || billingDetails?.amountDue
            || pendingBalanceValue
            || totalAmountValue
        );
    const paymentChoiceNote = hasPartialPayment
        ? "Your first payment is already posted. Pay the remaining balance to complete the booking."
        : paymentOptions.length > 1
            ? "Choose down payment or full payment before you continue."
            : currentPaymentOption?.description || "Pay the amount shown, then refresh this page after payment.";
    const eventLabel = packageLabel && packageLabel !== "Package pending"
        ? packageLabel
        : bookingRequest?.eventType || "Booking";
    const invoiceNumber = billingDetails?.invoiceNumber || createBillingInvoiceReference(bookingRequest?.reference);
    const paymentMethod = billingDetails?.paymentMethod || paymentSettings?.activeMethod || "PayMongo QRPh";
    const dueValue = billingDetails?.dueDate
        ? formatDateLabel(billingDetails.dueDate, "compact")
        : (paymentSettings?.balanceDueRule || "See invoice");
    const eventDateValue = bookingRequest?.eventDate
        ? formatDateLabel(bookingRequest.eventDate)
        : "Date pending";
    const eventTimeValue = bookingRequest?.eventTime
        ? formatTimeLabel(bookingRequest.eventTime)
        : "Time pending";
    const paymentMethodKey = paymentMethod.trim().toLowerCase();
    const isQrphPayment = paymentMethodKey.includes("qrph");
    const paymentMethodBodyLabel = isQrphPayment
        ? formatSentenceFragment(paymentSettings?.acceptedWalletsLabel, "any QRPh-supported e-wallet or banking app")
        : `${paymentMethod} method`;
    const receiptAttachmentLabel = isQrphPayment
        ? "the PayMongo QRPh checkout"
        : "your payment method";
    const paymentInstructionText = billingDetails?.instructionText
        || paymentSettings?.instructionText
        || "Use the active PayMongo QRPh checkout in your invoice and refresh this page after payment.";
    const dueInstructionLabel = dueValue === "See invoice"
        ? "before the due date shown in your invoice"
        : (/^on\b/i.test(dueValue) ? dueValue : `on or before ${dueValue}`);
    const receiptText = isQrphPayment
        ? `Open ${receiptAttachmentLabel}, complete the payment, then refresh your billing status here.`
        : `Complete your ${paymentMethod.toLowerCase()} payment and refresh your billing status here.`;
    const description = billingDetails?.description || `${eventLabel} Payment`;

    return {
        bookingReference: bookingRequest?.reference || "REQ-TBA",
        eventName: eventLabel,
        eventSchedule: `${eventDateValue} | ${eventTimeValue}`,
        venue: bookingRequest?.venue || "Venue pending",
        guestCountLabel: bookingRequest?.guestCount ? `${bookingRequest.guestCount} guests` : "Guest count pending",
        invoiceNumber,
        paymentMethod,
        amountDue,
        amountToPay: amountDue,
        amountToPayValue: currentPaymentOption?.amountValue || amountToPayNowValue || pendingBalanceValue,
        totalAmount,
        totalAmountValue,
        pendingBalance,
        pendingBalanceValue,
        totalPaid,
        totalPaidValue,
        downPaymentAmount: packageDownPaymentAmount,
        downPaymentAmountValue,
        dueValue,
        description,
        paymentMethodBodyLabel,
        statusText: billingDetails?.statusText || "Open",
        statusPillClass: billingDetails?.statusPillClass || "pending",
        invoiceHref: billingDetails?.invoiceHref || "#",
        receiptHref: billingDetails?.receiptHref || "",
        receiptDownloadHref: billingDetails?.receiptDownloadHref || billingDetails?.receiptHref || "",
        receiptReference: billingDetails?.receiptReference || "",
        paidAt: billingDetails?.gatewayPaidAt || billingDetails?.lastPayment || "",
        paymentReference: billingDetails?.gatewayPaymentId || billingDetails?.gatewayCheckoutReference || "",
        paymentChoiceNote,
        paymentOptions,
        selectedPaymentOption: currentPaymentOption?.value || "full_payment",
        selectedPaymentOptionLabel: currentPaymentOption?.label || "Full Payment",
        currentPaymentOption,
        methodInstructionText: `${paymentInstructionText} Settle the amount due ${dueInstructionLabel}.`.trim(),
        receiptText,
        supportMobile: paymentSettings?.supportMobile || "Billing support line",
        allowFullPayment,
        receiptRequirement: paymentSettings?.receiptRequirement || "receipt_required",
        packageAllowsDownPayment,
        isPartialPayment: hasPartialPayment
    };
}
function createBillingInvoiceReference(requestReference) {
    if (!requestReference) {
        return "INV-TBA";
    }

    return requestReference.replace(/^(REQ|BK)-/i, "INV-");
}

function formatBillingAmount(amountValue) {
    if (typeof amountValue === "number" && Number.isFinite(amountValue)) {
        return `PHP ${new Intl.NumberFormat("en-PH", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amountValue)}`;
    }

    if (typeof amountValue === "string" && amountValue.trim()) {
        return amountValue.trim();
    }

    return "See latest invoice";
}

function escapeHtml(value) {
    return String(value)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#39;");
}

