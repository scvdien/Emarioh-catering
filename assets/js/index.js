document.addEventListener("DOMContentLoaded", () => {
    const navLinks = document.querySelectorAll(".dashboard-nav .nav-link");
    const messageToggle = document.querySelector('[data-nav-toggle="notes"]');
    const messagePanel = document.getElementById("screenMessagesPanel");
    const messageClose = document.getElementById("screenMessagesClose");
    const dashboardContent = document.querySelector(".dashboard-content");
    const sidebarElement = document.getElementById("dashboardSidebar");
    const currentPath = window.location.pathname.split("/").pop() || "index.html";
    const sidebarInstance = sidebarElement && window.bootstrap
        ? window.bootstrap.Offcanvas.getOrCreateInstance(sidebarElement)
        : null;

    const closeMessages = () => {
        if (!messagePanel || !messageToggle || !dashboardContent) {
            return;
        }

        messagePanel.setAttribute("hidden", "");
        dashboardContent.classList.remove("messages-open");
        messageToggle.classList.remove("is-open");
        messageToggle.setAttribute("aria-expanded", "false");
    };

    const openMessages = () => {
        if (!messagePanel || !messageToggle || !dashboardContent) {
            return;
        }

        messagePanel.removeAttribute("hidden");
        dashboardContent.classList.add("messages-open");
        messageToggle.classList.add("is-open");
        messageToggle.setAttribute("aria-expanded", "true");
    };

    navLinks.forEach((link) => {
        if (link.tagName === "A") {
            const href = link.getAttribute("href");

            if (href && href !== "#" && !href.startsWith("#")) {
                const normalizedHref = href.split("#")[0];
                link.classList.toggle("active", normalizedHref === currentPath);
            }
        }

        link.addEventListener("click", (event) => {
            if (link === messageToggle) {
                event.preventDefault();
                const isOpen = messagePanel && !messagePanel.hasAttribute("hidden");

                if (isOpen) {
                    closeMessages();
                } else {
                    openMessages();

                    if (window.innerWidth < 1200 && sidebarInstance) {
                        sidebarInstance.hide();
                    }
                }

                return;
            }

            if (link.tagName === "A") {
                const href = link.getAttribute("href");

                if (href && href !== "#" && !href.startsWith("#")) {
                    if (window.innerWidth < 1200 && sidebarInstance) {
                        sidebarInstance.hide();
                    }

                    return;
                }
            }

            event.preventDefault();

            navLinks.forEach((navLink) => {
                if (navLink !== messageToggle) {
                    navLink.classList.remove("active");
                }
            });

            link.classList.add("active");
            closeMessages();

            if (window.innerWidth < 1200 && sidebarInstance) {
                sidebarInstance.hide();
            }
        });
    });

    if (messageClose) {
        messageClose.addEventListener("click", closeMessages);
    }
});