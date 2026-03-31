const menuToggle = document.querySelector(".public-menu-toggle");
const mobileNav = document.querySelector("#publicMobileNav");
const mobileLinks = mobileNav ? mobileNav.querySelectorAll("a") : [];
const sectionNavLinks = document.querySelectorAll('.public-nav a[href^="#"], .public-mobile-nav a[href^="#"]');
const pageHeader = document.querySelector(".public-header");
const filterButtons = document.querySelectorAll(".gallery-filter");
const galleryCards = document.querySelectorAll(".gallery-card");
const yearTarget = document.querySelector("[data-current-year]");
const revealItems = document.querySelectorAll("[data-reveal]");
const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
const pageSections = Array.from(new Set(Array.from(sectionNavLinks, (link) => link.getAttribute("href"))))
    .map((href) => document.querySelector(href))
    .filter((section) => section && section.id);

if (yearTarget) {
    yearTarget.textContent = new Date().getFullYear();
}

function setMobileMenuState(isOpen) {
    if (!menuToggle || !mobileNav) {
        return;
    }

    menuToggle.setAttribute("aria-expanded", String(isOpen));
    mobileNav.hidden = !isOpen;
    document.body.classList.toggle("is-menu-open", isOpen);
}

function setActiveSectionLink(sectionId) {
    if (!sectionId) {
        return;
    }

    sectionNavLinks.forEach((link) => {
        const isActive = link.getAttribute("href") === `#${sectionId}`;
        link.classList.toggle("is-active", isActive);

        if (isActive) {
            link.setAttribute("aria-current", "location");
        } else {
            link.removeAttribute("aria-current");
        }
    });
}

function getCurrentSectionId() {
    if (!pageSections.length) {
        return "";
    }

    const headerHeight = pageHeader ? pageHeader.offsetHeight : 0;
    const checkpoint = window.scrollY + headerHeight + Math.max(window.innerHeight * 0.18, 120);
    let activeSectionId = pageSections[0].id;

    pageSections.forEach((section) => {
        if (section.offsetTop <= checkpoint) {
            activeSectionId = section.id;
        }
    });

    return activeSectionId;
}

let isSectionSyncQueued = false;

function syncActiveSectionLink() {
    setActiveSectionLink(getCurrentSectionId());
}

function queueActiveSectionSync() {
    if (isSectionSyncQueued) {
        return;
    }

    isSectionSyncQueued = true;
    window.requestAnimationFrame(() => {
        syncActiveSectionLink();
        isSectionSyncQueued = false;
    });
}

if (sectionNavLinks.length && pageSections.length) {
    const initialHash = window.location.hash.replace("#", "");
    const hasMatchingHash = pageSections.some((section) => section.id === initialHash);

    if (hasMatchingHash) {
        setActiveSectionLink(initialHash);
    } else {
        syncActiveSectionLink();
    }

    sectionNavLinks.forEach((link) => {
        link.addEventListener("click", () => {
            const targetId = link.getAttribute("href").replace("#", "");
            setActiveSectionLink(targetId);
        });
    });

    window.addEventListener("scroll", queueActiveSectionSync, { passive: true });
    window.addEventListener("resize", queueActiveSectionSync);
    window.addEventListener("hashchange", () => {
        const targetId = window.location.hash.replace("#", "");
        const hasMatchingSection = pageSections.some((section) => section.id === targetId);

        if (hasMatchingSection) {
            setActiveSectionLink(targetId);
        } else {
            queueActiveSectionSync();
        }
    });
}

if (menuToggle && mobileNav) {
    menuToggle.addEventListener("click", () => {
        const expanded = menuToggle.getAttribute("aria-expanded") === "true";
        setMobileMenuState(!expanded);
    });

    mobileLinks.forEach((link) => {
        link.addEventListener("click", () => setMobileMenuState(false));
    });

    window.addEventListener("resize", () => {
        if (window.innerWidth > 991) {
            setMobileMenuState(false);
        }
    });
}

filterButtons.forEach((button) => {
    button.addEventListener("click", () => {
        const filter = button.dataset.filter;

        filterButtons.forEach((item) => item.classList.remove("is-active"));
        button.classList.add("is-active");

        galleryCards.forEach((card) => {
            const categories = (card.dataset.category || "").split(" ");
            const shouldShow = filter === "all" || categories.includes(filter);
            card.classList.toggle("is-hidden", !shouldShow);
        });
    });
});

if (prefersReducedMotion.matches) {
    revealItems.forEach((item) => item.classList.add("is-visible"));
} else if ("IntersectionObserver" in window) {
    const observer = new IntersectionObserver((entries, instance) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            entry.target.classList.add("is-visible");
            instance.unobserve(entry.target);
        });
    }, {
        threshold: 0.18,
        rootMargin: "0px 0px -40px 0px"
    });

    revealItems.forEach((item) => observer.observe(item));
} else {
    revealItems.forEach((item) => item.classList.add("is-visible"));
}