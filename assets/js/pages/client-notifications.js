document.addEventListener("DOMContentLoaded", () => {
    const items = Array.from(document.querySelectorAll("[data-notification-item]"));
    const modalElement = document.getElementById("clientNotificationModal");
    const modal = modalElement && window.bootstrap?.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(modalElement)
        : null;

    const modalFields = {
        title: modalElement?.querySelector("[data-notification-modal-title]"),
        message: modalElement?.querySelector("[data-notification-modal-message]"),
        reference: modalElement?.querySelector("[data-notification-modal-reference]"),
        time: modalElement?.querySelector("[data-notification-modal-time]"),
        event: modalElement?.querySelector("[data-notification-modal-event]"),
        status: modalElement?.querySelector("[data-notification-modal-status]")
    };

    const setText = (element, value) => {
        if (element) {
            element.textContent = value || "";
        }
    };

    const markItemRead = (item) => {
        if (!item || item.dataset.readStatus !== "unread") {
            return;
        }

        item.dataset.readStatus = "read";
        item.classList.remove("is-unread");
        item.classList.add("is-read");
        item.querySelector(".client-notification-dot")?.remove();
    };

    const persistReadStatus = async (item) => {
        const notificationId = Number.parseInt(item?.dataset.notificationId || "0", 10);

        if (!notificationId) {
            return;
        }

        try {
            await fetch("api/notifications/mark-read.php", {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json"
                },
                credentials: "same-origin",
                body: JSON.stringify({
                    notification_id: notificationId
                })
            });
        } catch (error) {
            // The item is still marked read locally so the client experience stays smooth.
        }
    };

    const openNotification = (item) => {
        if (!item) {
            return;
        }

        const title = item.dataset.notificationTitle || "Notification";
        const message = item.dataset.notificationMessage || "Your booking was updated.";
        const reference = item.dataset.notificationReference || "Reference pending";
        const time = item.dataset.notificationTime || "Just now";
        const eventLabel = item.dataset.notificationEvent || "Booking";
        const statusLabel = item.dataset.notificationStatusLabel || "Booking Update";

        setText(modalFields.title, title);
        setText(modalFields.message, message);
        setText(modalFields.reference, reference);
        setText(modalFields.time, time);
        setText(modalFields.event, eventLabel);
        setText(modalFields.status, statusLabel);

        markItemRead(item);
        persistReadStatus(item);

        if (modal) {
            modal.show();
            return;
        }

        window.alert(`${title}\n\n${message}`);
    };

    items.forEach((item) => {
        item.addEventListener("click", () => {
            openNotification(item);
        });

        item.addEventListener("keydown", (event) => {
            if (event.key !== "Enter" && event.key !== " ") {
                return;
            }

            event.preventDefault();
            openNotification(item);
        });
    });
});
