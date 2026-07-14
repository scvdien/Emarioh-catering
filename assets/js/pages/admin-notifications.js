document.addEventListener("DOMContentLoaded", () => {
    const items = Array.from(document.querySelectorAll("[data-admin-notification-item]"));
    const modalElement = document.getElementById("adminNotificationModal");
    const modal = modalElement && window.bootstrap && window.bootstrap.Modal
        ? new window.bootstrap.Modal(modalElement)
        : null;
    const modalTitle = document.getElementById("adminNotificationModalTitle");
    const modalMessage = document.getElementById("adminNotificationModalMessage");
    const modalReference = document.getElementById("adminNotificationModalReference");
    const modalTime = document.getElementById("adminNotificationModalTime");
    const modalIcon = document.getElementById("adminNotificationModalIcon");
    const modalOpenLink = document.getElementById("adminNotificationModalOpenLink");
    const notificationList = document.querySelector("[data-notification-list]");
    const deleteModalElement = document.getElementById("notificationDeleteModal");
    const deleteModal = deleteModalElement && window.bootstrap?.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(deleteModalElement)
        : null;
    const confirmDeleteButton = deleteModalElement?.querySelector("[data-confirm-notification-delete]");
    let pendingDelete = null;

    const updateUnreadBadges = (unreadTotal) => {
        const count = Math.max(0, Number.parseInt(String(unreadTotal), 10) || 0);
        const badges = document.querySelectorAll(".admin-nav-badge, .admin-mobile-notification-badge");

        badges.forEach((badge) => {
            if (count === 0) {
                badge.remove();
                return;
            }

            badge.textContent = String(Math.min(count, 99));
            badge.setAttribute("aria-label", `${count} unread notifications`);
        });
    };

    const markReadLocally = (item) => {
        if (!item || item.dataset.readStatus !== "unread") {
            return;
        }

        item.dataset.readStatus = "read";
        item.classList.remove("is-unread");
        item.classList.add("is-read");
    };

    const persistRead = async (item) => {
        const notificationId = Number.parseInt(item && item.dataset ? item.dataset.notificationId || "0" : "0", 10);

        if (!notificationId) {
            return;
        }

        try {
            const response = await fetch("api/admin-notifications/mark-read.php", {
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

            const result = await response.json();
            if (!response.ok || result.success === false) {
                throw new Error(result.message || "Notification could not be marked as read.");
            }

            updateUnreadBadges(result.unread_total ?? result.data?.unread_total ?? 0);
        } catch (error) {
            // Keep the UI responsive even if the read state cannot be saved immediately.
        }
    };

    const deleteNotification = async (item, button) => {
        const notificationId = Number.parseInt(item?.dataset.notificationId || "0", 10);
        if (!notificationId) {
            return;
        }

        button.disabled = true;
        try {
            const response = await fetch("api/admin-notifications/delete.php", {
                method: "POST",
                headers: { Accept: "application/json", "Content-Type": "application/json" },
                credentials: "same-origin",
                body: JSON.stringify({ notification_id: notificationId })
            });
            const result = await response.json();
            if (!response.ok || result.success === false) {
                throw new Error(result.message || "Notification could not be deleted.");
            }

            item.remove();
            deleteModal?.hide();
            pendingDelete = null;
            updateUnreadBadges(result.unread_total ?? result.data?.unread_total ?? 0);
            if (notificationList && !notificationList.querySelector("[data-admin-notification-item]")) {
                notificationList.innerHTML = '<article class="admin-notification-empty"><span class="admin-notification-empty__icon" aria-hidden="true"><i class="bi bi-bell"></i></span><div><h2>No notifications yet</h2></div></article>';
            }
        } catch (error) {
            window.alert(error.message || "Notification could not be deleted.");
            button.disabled = false;
        }
    };

    const openNotification = (item) => {
        if (!item) {
            return;
        }

        const title = item.dataset.notificationTitle || "Notification";
        const message = item.dataset.notificationMessage || "Notification details";
        const reference = item.dataset.notificationReference || "No reference";
        const time = item.dataset.notificationTime || "";
        const href = item.dataset.notificationHref || "admin-notifications.php";
        const icon = item.dataset.notificationIcon || "bi-bell";
        const wasUnread = item.dataset.readStatus === "unread";

        if (modalTitle) {
            modalTitle.textContent = title;
        }

        if (modalMessage) {
            modalMessage.textContent = message;
        }

        if (modalReference) {
            modalReference.textContent = reference;
        }

        if (modalTime) {
            modalTime.textContent = time;
        }

        if (modalIcon) {
            modalIcon.className = `bi ${icon}`;
        }

        if (modalOpenLink) {
            modalOpenLink.href = href;
            modalOpenLink.hidden = href === "" || href === "admin-notifications.php";
        }

        markReadLocally(item);

        if (wasUnread) {
            const remainingUnread = items.filter((notificationItem) => notificationItem.dataset.readStatus === "unread").length;
            updateUnreadBadges(remainingUnread);
            void persistRead(item);
        }

        if (modal) {
            modal.show();
        }
    };

    items.forEach((item) => {
        const deleteButton = item.querySelector("[data-delete-notification]");
        deleteButton?.addEventListener("click", (event) => {
            event.stopPropagation();
            pendingDelete = { item, button: deleteButton };
            deleteModal?.show();
        });
        deleteButton?.addEventListener("keydown", (event) => event.stopPropagation());

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

    confirmDeleteButton?.addEventListener("click", () => {
        if (pendingDelete) {
            void deleteNotification(pendingDelete.item, pendingDelete.button);
        }
    });

    deleteModalElement?.addEventListener("hidden.bs.modal", () => {
        pendingDelete = null;
    });
});
