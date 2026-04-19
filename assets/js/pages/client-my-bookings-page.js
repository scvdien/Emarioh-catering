document.addEventListener("DOMContentLoaded", () => {
    const bookingSection = document.getElementById("bookingRequestsSection");
    const bookingStatusSelect = document.getElementById("bookingStatusSelect");
    const tabButtons = Array.from(document.querySelectorAll("#bookingStatusTabs .nav-link"));
    const modalElement = document.getElementById("bookingCancelModal");
    const modalTitle = document.getElementById("bookingCancelModalLabel");
    const modalText = document.getElementById("bookingCancelModalText");
    const confirmButton = document.getElementById("bookingCancelConfirmButton");
    const cancelModal = modalElement && window.bootstrap?.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(modalElement)
        : null;
    let pendingCancellation = null;

    if (!bookingSection || !confirmButton) {
        return;
    }

    if (bookingStatusSelect && tabButtons.length) {
        bookingStatusSelect.addEventListener("change", () => {
            const selectedId = bookingStatusSelect.value;
            const targetButton = document.getElementById(selectedId);

            if (targetButton) {
                const tabInstance = window.bootstrap?.Tab
                    ? window.bootstrap.Tab.getOrCreateInstance(targetButton)
                    : null;

                if (tabInstance) {
                    tabInstance.show();
                    return;
                }

                targetButton.click();
            }
        });

        tabButtons.forEach((button) => {
            button.addEventListener("shown.bs.tab", () => {
                if (bookingStatusSelect.value !== button.id) {
                    bookingStatusSelect.value = button.id;
                }
            });
        });
    }

    bookingSection.addEventListener("click", (event) => {
        const cancelButton = event.target.closest("[data-booking-cancel]");

        if (!cancelButton) {
            return;
        }

        pendingCancellation = {
            bookingId: Number.parseInt(cancelButton.dataset.bookingId || "0", 10),
            bookingReference: cancelButton.dataset.bookingReference || "",
            buttonLabel: cancelButton.textContent.trim() || "Cancel Request"
        };

        const isApprovedBooking = pendingCancellation.buttonLabel.toLowerCase().includes("booking");

        if (modalTitle) {
            modalTitle.textContent = isApprovedBooking ? "Cancel Booking" : "Cancel Request";
        }

        if (modalText) {
            modalText.textContent = isApprovedBooking
                ? "Cancel this booking? It will move to the Cancelled tab and you can submit a new booking again."
                : "Cancel this request? It will move to the Cancelled tab and you can submit a new booking again.";
        }

        confirmButton.textContent = isApprovedBooking ? "Yes, Cancel Booking" : "Yes, Cancel Request";

        if (cancelModal) {
            cancelModal.show();
            return;
        }

        if (window.confirm(modalText?.textContent || "Cancel this request?")) {
            submitBookingCancellation(pendingCancellation, confirmButton);
        }
    });

    confirmButton.addEventListener("click", async () => {
        if (!pendingCancellation) {
            return;
        }

        await submitBookingCancellation(pendingCancellation, confirmButton);
    });

    modalElement?.addEventListener("hidden.bs.modal", () => {
        pendingCancellation = null;
        confirmButton.disabled = false;
        confirmButton.textContent = "Yes, Cancel Request";
    });
});

async function submitBookingCancellation(cancellation, confirmButton) {
    if (!cancellation?.bookingId) {
        renderBookingActionAlert("Booking reference is invalid.", "danger");
        return;
    }

    confirmButton.disabled = true;
    confirmButton.textContent = "Cancelling...";

    try {
        await postBookingAction("api/bookings/update-status.php", {
            booking_id: cancellation.bookingId,
            status: "cancelled"
        });

        syncCancelledPortalBooking(cancellation.bookingReference);
        window.location.assign("client-my-bookings.php?cancelled=1");
    } catch (error) {
        confirmButton.disabled = false;
        confirmButton.textContent = "Try Again";
        renderBookingActionAlert(
            error?.message || "The booking request could not be cancelled right now. Please try again.",
            "danger"
        );
    }
}

async function postBookingAction(url, payload) {
    const resolvedUrl = window.EmariohRuntime?.resolveUrl
        ? window.EmariohRuntime.resolveUrl(url)
        : url;
    const response = await fetch(resolvedUrl, {
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

function renderBookingActionAlert(message, tone = "danger") {
    const bookingSection = document.getElementById("bookingRequestsSection");

    if (!bookingSection) {
        return;
    }

    let alertElement = document.getElementById("bookingActionAlert");

    if (!alertElement) {
        alertElement = document.createElement("div");
        alertElement.id = "bookingActionAlert";
        bookingSection.insertBefore(alertElement, bookingSection.children[1] || null);
    }

    alertElement.className = `alert alert-${tone} mb-4`;
    alertElement.textContent = message || "";
}

function syncCancelledPortalBooking(reference) {
    try {
        const storageKey = "emariohClientPortalState";
        const currentState = JSON.parse(window.localStorage.getItem(storageKey) || "{}");
        const bookingRequest = currentState?.bookingRequest;

        if (!bookingRequest || (reference && bookingRequest.reference && bookingRequest.reference !== reference)) {
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
        window.localStorage.setItem(storageKey, JSON.stringify(nextState));
    } catch (error) {
        return;
    }
}
