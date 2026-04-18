document.addEventListener("DOMContentLoaded", () => {
    const logoutSelector = "[data-logout-link][href], .sidebar-logout[href], .settings-profile-logout[href]";
    const logoutLinks = Array.from(document.querySelectorAll(logoutSelector));

    if (!logoutLinks.length) {
        return;
    }

    const modalId = "logoutConfirmationModal";
    const styleId = "logoutConfirmationModalStyles";

    const injectStyles = () => {
        if (document.getElementById(styleId)) {
            return;
        }

        const style = document.createElement("style");
        style.id = styleId;
        style.textContent = `
            .logout-confirmation-modal .modal-dialog {
                max-width: 29rem;
            }

            .logout-confirmation-modal__backdrop {
                background: rgba(30, 22, 15, 0.42);
            }

            .logout-confirmation-modal__content {
                border: 1px solid rgba(201, 162, 74, 0.18);
                border-radius: 1.35rem;
                background: linear-gradient(180deg, #FFFFFF 0%, #FCF8F0 100%);
                box-shadow:
                    0 22px 44px rgba(46, 46, 46, 0.14),
                    inset 0 1px 0 rgba(255, 255, 255, 0.92);
                overflow: hidden;
            }

            .logout-confirmation-modal__header,
            .logout-confirmation-modal__footer {
                border: 0;
            }

            .logout-confirmation-modal__header {
                align-items: flex-start;
                padding: 1.25rem 1.25rem 0.35rem;
            }

            .logout-confirmation-modal__eyebrow {
                margin: 0;
                color: #B4851F;
                font-size: 0.72rem;
                font-weight: 800;
                letter-spacing: 0.12em;
                text-transform: uppercase;
            }

            .logout-confirmation-modal__title {
                margin: 0.35rem 0 0;
                font-family: "Cormorant Garamond", serif;
                font-size: 2rem;
                font-weight: 700;
                line-height: 0.95;
                color: #2E2E2E;
            }

            .logout-confirmation-modal__body {
                padding: 0.35rem 1.25rem 1rem;
            }

            .logout-confirmation-modal__text {
                margin: 0;
                color: #6D6760;
                font-size: 0.96rem;
                line-height: 1.7;
            }

            .logout-confirmation-modal__footer {
                display: flex;
                justify-content: flex-end;
                gap: 0.65rem;
                padding: 0 1.25rem 1.25rem;
            }

            .logout-confirmation-modal__action {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 8rem;
                padding: 0.72rem 1.15rem;
                border-radius: 999px;
                border: 1px solid rgba(201, 162, 74, 0.2);
                font-size: 0.88rem;
                font-weight: 700;
                line-height: 1;
                text-decoration: none;
                transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, background-color 0.2s ease, color 0.2s ease;
            }

            .logout-confirmation-modal__action:hover {
                transform: translateY(-1px);
            }

            .logout-confirmation-modal__action:disabled {
                opacity: 0.72;
                cursor: wait;
                transform: none;
            }

            .logout-confirmation-modal__action--cancel {
                background: #FFFFFF;
                color: #5F5851;
            }

            .logout-confirmation-modal__action--cancel:hover {
                border-color: rgba(201, 162, 74, 0.32);
                color: #2E2E2E;
            }

            .logout-confirmation-modal__action--confirm {
                border-color: transparent;
                background: linear-gradient(135deg, #D5AE4B, #C89B2F);
                color: #FFFFFF;
                box-shadow: 0 14px 24px rgba(201, 162, 74, 0.22);
            }

            .logout-confirmation-modal__action--confirm:hover {
                color: #FFFFFF;
                box-shadow: 0 18px 28px rgba(201, 162, 74, 0.28);
            }

            @media (max-width: 575.98px) {
                .logout-confirmation-modal__header,
                .logout-confirmation-modal__body,
                .logout-confirmation-modal__footer {
                    padding-left: 1rem;
                    padding-right: 1rem;
                }

                .logout-confirmation-modal__footer {
                    flex-direction: column-reverse;
                }

                .logout-confirmation-modal__action {
                    width: 100%;
                }
            }
        `;

        document.head.appendChild(style);
    };

    const injectModal = () => {
        let modalElement = document.getElementById(modalId);

        if (modalElement) {
            return modalElement;
        }

        document.body.insertAdjacentHTML("beforeend", `
            <div class="modal fade logout-confirmation-modal" id="${modalId}" tabindex="-1" aria-labelledby="logoutConfirmationModalLabel" aria-hidden="true" role="dialog" aria-modal="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content logout-confirmation-modal__content">
                        <div class="modal-header logout-confirmation-modal__header">
                            <div>
                                <p class="logout-confirmation-modal__eyebrow">Confirm Logout</p>
                                <h2 class="logout-confirmation-modal__title" id="logoutConfirmationModalLabel">Log out now?</h2>
                            </div>
                            <button type="button" class="btn-close" data-logout-dismiss aria-label="Close"></button>
                        </div>
                        <div class="modal-body logout-confirmation-modal__body">
                            <p class="logout-confirmation-modal__text">You will be returned to the login page and need to sign in again to access your dashboard.</p>
                        </div>
                        <div class="modal-footer logout-confirmation-modal__footer">
                            <button type="button" class="logout-confirmation-modal__action logout-confirmation-modal__action--cancel" data-logout-dismiss>Stay here</button>
                            <button type="button" class="logout-confirmation-modal__action logout-confirmation-modal__action--confirm" id="logoutConfirmationModalConfirm">Log out</button>
                        </div>
                    </div>
                </div>
            </div>
        `);

        return document.getElementById(modalId);
    };

    injectStyles();

    const modalElement = injectModal();
    const confirmButton = document.getElementById("logoutConfirmationModalConfirm");
    const dismissButtons = Array.from(modalElement.querySelectorAll("[data-logout-dismiss]"));

    let pendingHref = "logout.php";
    let backdropElement = null;
    let isOpen = false;
    let isSubmitting = false;

    modalElement.style.display = "none";

    const setConfirmButtonBusy = (busy) => {
        if (!confirmButton) {
            return;
        }

        if (!confirmButton.dataset.defaultLabel) {
            confirmButton.dataset.defaultLabel = confirmButton.textContent;
        }

        confirmButton.disabled = busy;
        confirmButton.textContent = busy ? "Logging out..." : confirmButton.dataset.defaultLabel;
    };

    const createBackdrop = () => {
        if (backdropElement) {
            return;
        }

        backdropElement = document.createElement("div");
        backdropElement.className = "modal-backdrop fade show logout-confirmation-modal__backdrop";
        document.body.appendChild(backdropElement);
    };

    const removeBackdrop = () => {
        if (!backdropElement) {
            return;
        }

        backdropElement.remove();
        backdropElement = null;
    };

    const showModal = () => {
        if (isOpen) {
            return;
        }

        isOpen = true;
        modalElement.style.display = "block";
        modalElement.removeAttribute("aria-hidden");
        modalElement.classList.add("show");
        createBackdrop();
        document.body.classList.add("modal-open");
        document.body.style.overflow = "hidden";

        window.setTimeout(() => {
            confirmButton?.focus();
        }, 0);
    };

    const hideModal = () => {
        if (!isOpen || isSubmitting) {
            return;
        }

        isOpen = false;
        modalElement.classList.remove("show");
        modalElement.setAttribute("aria-hidden", "true");
        modalElement.style.display = "none";
        removeBackdrop();
        document.body.classList.remove("modal-open");
        document.body.style.removeProperty("overflow");
    };

    const performLogout = async () => {
        const fallbackHref = pendingHref || "logout.php";
        isSubmitting = true;
        setConfirmButtonBusy(true);

        try {
            if (!window.EmariohAuth?.logout) {
                window.location.href = fallbackHref;
                return;
            }

            const response = await window.EmariohAuth.logout();

            try {
                window.localStorage.removeItem("emariohClientPortalState");
            } catch (error) {
                // Ignore storage cleanup issues and continue redirecting.
            }

            window.location.href = response.redirect_url || fallbackHref;
        } catch (error) {
            window.location.href = fallbackHref;
        } finally {
            isSubmitting = false;
            setConfirmButtonBusy(false);
        }
    };

    logoutLinks.forEach((link) => {
        link.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();
            pendingHref = link.getAttribute("href")?.trim() || "logout.php";
            showModal();
        }, true);
    });

    document.addEventListener("click", (event) => {
        if (isOpen && event.target === modalElement) {
            event.preventDefault();
            hideModal();
        }
    });

    dismissButtons.forEach((button) => {
        button.addEventListener("click", (event) => {
            event.preventDefault();
            hideModal();
        });
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && isOpen) {
            event.preventDefault();
            hideModal();
        }
    });

    confirmButton?.addEventListener("click", (event) => {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        performLogout();
    });
});
