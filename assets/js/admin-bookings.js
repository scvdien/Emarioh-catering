document.addEventListener("DOMContentLoaded", () => {
    const bookingQueueCard = document.querySelector(".surface-card--booking-queue");
    const adminActionButtons = bookingQueueCard?.querySelectorAll("[data-booking-admin-action]") || [];
    const messageActionButtons = bookingQueueCard?.querySelectorAll("[data-booking-message-action]") || [];
    const confirmModalElement = document.getElementById("bookingActionConfirmModal");
    const confirmModalLabel = document.getElementById("bookingActionConfirmModalLabel");
    const confirmModalText = document.getElementById("bookingActionConfirmModalText");
    const confirmActionButton = document.getElementById("bookingActionConfirmButton");
    const confirmModal = confirmModalElement && window.bootstrap?.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(confirmModalElement)
        : null;
    let pendingBookingAction = null;

    if (!bookingQueueCard) {
        return;
    }

    const getBookingActionMeta = (nextStatus, bookingReference) => {
        if (nextStatus === "approved") {
            return {
                title: "Approve Booking?",
                message: `Approve ${bookingReference}? This will confirm the booking date on the admin schedule.`,
                confirmLabel: "Approve Booking",
                loadingLabel: "Approving...",
                buttonClass: "action-btn--primary"
            };
        }

        return {
            title: "Reject Booking?",
            message: `Reject ${bookingReference}? This will close the booking request.`,
            confirmLabel: "Reject Booking",
            loadingLabel: "Rejecting...",
            buttonClass: "action-btn--danger"
        };
    };

    const getBookingMessageMeta = (templateKey, bookingReference) => {
        if (templateKey === "final_event_reminder") {
            return {
                message: `Send the final event reminder for ${bookingReference} to the client now?`,
                loadingLabel: "Sending..."
            };
        }

        return {
            message: `Send a booking message for ${bookingReference} now?`,
            loadingLabel: "Sending..."
        };
    };

    const resetConfirmActionButton = () => {
        if (!confirmActionButton) {
            return;
        }

        confirmActionButton.disabled = false;
        confirmActionButton.textContent = "Confirm";
        confirmActionButton.classList.remove("action-btn--danger");
        confirmActionButton.classList.add("action-btn--primary");
    };

    const resetPendingBookingAction = () => {
        pendingBookingAction = null;
        resetConfirmActionButton();

        if (confirmModalLabel) {
            confirmModalLabel.textContent = "Confirm Booking Update";
        }

        if (confirmModalText) {
            confirmModalText.textContent = "Confirm this booking action?";
        }
    };

    const submitBookingAdminAction = async () => {
        if (!pendingBookingAction) {
            return;
        }

        const {
            actionButton,
            bookingId,
            nextStatus,
            confirmLabel,
            loadingLabel
        } = pendingBookingAction;

        if (!(actionButton instanceof HTMLElement)) {
            return;
        }

        if (actionButton.disabled) {
            return;
        }

        if (!bookingId) {
            return;
        }

        actionButton.disabled = true;
        actionButton.textContent = loadingLabel;

        if (confirmActionButton) {
            confirmActionButton.disabled = true;
            confirmActionButton.textContent = loadingLabel;
        }

        try {
            await postBookingAdminAction("api/bookings/update-status.php", {
                booking_id: bookingId,
                status: nextStatus
            });

            window.location.assign("admin-bookings.php");
        } catch (error) {
            actionButton.disabled = false;
            actionButton.textContent = nextStatus === "approved" ? "Approve" : "Reject";

            if (confirmActionButton) {
                confirmActionButton.disabled = false;
                confirmActionButton.textContent = confirmLabel;
            }

            renderAdminBookingAlert(
                error?.message || "The booking status could not be updated right now. Please try again.",
                "danger"
            );
        }
    };

    const openBookingActionConfirm = (actionButton) => {
        if (!(actionButton instanceof HTMLElement) || actionButton.disabled) {
            return;
        }

        const nextStatus = String(actionButton.dataset.bookingAdminAction || "").trim().toLowerCase();
        const bookingId = Number.parseInt(actionButton.dataset.bookingId || "0", 10);
        const bookingReference = actionButton.dataset.bookingReference || "this booking";
        const actionMeta = getBookingActionMeta(nextStatus, bookingReference);

        if (!bookingId) {
            return;
        }

        pendingBookingAction = {
            actionButton,
            bookingId,
            nextStatus,
            confirmLabel: actionMeta.confirmLabel,
            loadingLabel: actionMeta.loadingLabel
        };

        if (!confirmModal || !confirmActionButton || !confirmModalLabel || !confirmModalText) {
            const shouldProceed = window.confirm(actionMeta.message);

            if (!shouldProceed) {
                resetPendingBookingAction();
                return;
            }

            void submitBookingAdminAction();
            return;
        }

        confirmModalLabel.textContent = actionMeta.title;
        confirmModalText.textContent = actionMeta.message;
        confirmActionButton.disabled = false;
        confirmActionButton.textContent = actionMeta.confirmLabel;
        confirmActionButton.classList.remove("action-btn--danger", "action-btn--primary");
        confirmActionButton.classList.add(actionMeta.buttonClass);
        confirmModal.show();
    };

    adminActionButtons.forEach((actionButton) => {
        actionButton.addEventListener("click", (event) => {
            event.preventDefault();
            openBookingActionConfirm(actionButton);
        });
    });

    messageActionButtons.forEach((actionButton) => {
        actionButton.addEventListener("click", async (event) => {
            event.preventDefault();

            if (!(actionButton instanceof HTMLElement) || actionButton.disabled) {
                return;
            }

            const templateKey = String(actionButton.dataset.bookingMessageAction || "").trim().toLowerCase();
            const bookingId = Number.parseInt(actionButton.dataset.bookingId || "0", 10);
            const bookingReference = actionButton.dataset.bookingReference || "this booking";
            const actionMeta = getBookingMessageMeta(templateKey, bookingReference);
            const defaultLabel = actionButton.textContent;

            if (!bookingId || !templateKey) {
                return;
            }

            if (!window.confirm(actionMeta.message)) {
                return;
            }

            actionButton.disabled = true;
            actionButton.textContent = actionMeta.loadingLabel;

            try {
                const payload = await postBookingAdminAction("api/messages/send-booking-sms.php", {
                    booking_id: bookingId,
                    template_key: templateKey
                });

                renderAdminBookingAlert(
                    payload?.message || "Client reminder sent successfully.",
                    "success"
                );
            } catch (error) {
                renderAdminBookingAlert(
                    error?.message || "The booking reminder could not be sent right now. Please try again.",
                    "danger"
                );
            } finally {
                actionButton.disabled = false;
                actionButton.textContent = defaultLabel || "Final Reminder";
            }
        });
    });

    confirmActionButton?.addEventListener("click", () => {
        void submitBookingAdminAction();
    });

    confirmModalElement?.addEventListener("hidden.bs.modal", () => {
        resetPendingBookingAction();
    });
});

async function postBookingAdminAction(url, payload) {
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

function renderAdminBookingAlert(message, tone = "danger") {
    const bookingQueueCard = document.querySelector(".surface-card--booking-queue");

    if (!bookingQueueCard) {
        return;
    }

    let alertElement = document.getElementById("bookingManagementAlert");

    if (!alertElement) {
        alertElement = document.createElement("div");
        alertElement.id = "bookingManagementAlert";
        bookingQueueCard.insertBefore(alertElement, bookingQueueCard.firstElementChild || null);
    }

    alertElement.className = `alert alert-${tone} mb-4`;
    alertElement.textContent = message || "";
}
