document.addEventListener("DOMContentLoaded", () => {
    const sidebarElement = document.getElementById("dashboardSidebar");
    const navLinks = document.querySelectorAll(".dashboard-nav .nav-link");
    const currentPath = window.location.pathname.split("/").pop() || "client-dashboard.html";
    const sidebarInstance = sidebarElement && window.bootstrap
        ? window.bootstrap.Offcanvas.getOrCreateInstance(sidebarElement)
        : null;

    navLinks.forEach((link) => {
        const href = link.getAttribute("href");

        if (href && href !== "#" && !href.startsWith("#")) {
            const normalizedHref = href.split("#")[0];
            link.classList.toggle("active", normalizedHref === currentPath);
        }

        link.addEventListener("click", () => {
            if (window.innerWidth < 1200 && sidebarInstance) {
                window.setTimeout(() => {
                    sidebarInstance.hide();
                }, 150);
            }
        });
    });
});
