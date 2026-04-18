const menuToggle = document.querySelector(".public-menu-toggle");
const mobileNav = document.querySelector("#publicMobileNav");
const mobileLinks = mobileNav ? mobileNav.querySelectorAll("a") : [];
const sectionNavLinks = document.querySelectorAll('.public-nav a[href^="#"], .public-mobile-nav a[href^="#"]');
const pageHeader = document.querySelector(".public-header");
const filterButtons = document.querySelectorAll(".gallery-filter");
const galleryCards = document.querySelectorAll(".gallery-card");
const galleryGrid = document.querySelector(".gallery-grid");
const galleryImages = galleryGrid ? galleryGrid.querySelectorAll("img") : [];
const yearTarget = document.querySelector("[data-current-year]");
const revealItems = document.querySelectorAll("[data-reveal]");
const serviceModalTriggers = document.querySelectorAll("[data-service-modal-open]");
const serviceModal = document.querySelector("#serviceDetailModal");
const serviceModalDialog = serviceModal ? serviceModal.querySelector(".service-modal__dialog") : null;
const serviceModalContent = serviceModal ? serviceModal.querySelector("[data-service-modal-content]") : null;
const serviceModalCloseControls = serviceModal ? serviceModal.querySelectorAll("[data-service-modal-close]") : [];
const contactForm = document.querySelector("#publicInquiryForm");
const contactSubmitButton = contactForm ? contactForm.querySelector(".contact-form__submit") : null;
const contactFormFeedback = document.querySelector("[data-contact-form-feedback]");
const contactSuccessModal = document.querySelector("#contactSuccessModal");
const contactSuccessModalCloseControls = contactSuccessModal ? contactSuccessModal.querySelectorAll("[data-contact-success-close]") : [];
const contactSuccessModalPrimaryButton = contactSuccessModal ? contactSuccessModal.querySelector(".contact-success-modal__button") : null;
const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
const pageSections = Array.from(new Set(Array.from(sectionNavLinks, (link) => link.getAttribute("href"))))
    .map((href) => document.querySelector(href))
    .filter((section) => section && section.id);
const focusableSelector = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])'
].join(", ");
let lastServiceModalTrigger = null;

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
let serviceModalCloseTimer = null;
let serviceModalOpenFrame = null;
let isGalleryViewportSyncQueued = false;
let shouldResetGalleryScroll = false;
let lastContactSuccessTrigger = null;

function syncGalleryGridViewport(resetScroll = false) {
    if (!galleryGrid || !galleryCards.length) {
        return;
    }

    const visibleGalleryCards = Array.from(galleryCards).filter((card) => !card.classList.contains("is-hidden"));
    const isMobileGalleryViewport = window.matchMedia("(max-width: 767.98px)").matches;
    const visibleGalleryLimit = isMobileGalleryViewport ? 4 : 6;

    galleryGrid.classList.remove("is-scrollable");
    galleryGrid.classList.remove("is-mobile-scrollable");
    galleryGrid.style.maxHeight = "";
    galleryGrid.style.height = "";

    if (resetScroll) {
        galleryGrid.scrollTop = 0;
        galleryGrid.scrollLeft = 0;
    }

    if (visibleGalleryCards.length <= visibleGalleryLimit) {
        return;
    }

    if (isMobileGalleryViewport) {
        galleryGrid.classList.add("is-mobile-scrollable");

        const firstCard = visibleGalleryCards[0];
        const secondVisibleCard = visibleGalleryCards[Math.min(1, visibleGalleryCards.length - 1)];
        const viewportHeight = (secondVisibleCard.offsetTop - firstCard.offsetTop) + secondVisibleCard.offsetHeight;

        galleryGrid.style.height = `${Math.ceil(viewportHeight)}px`;
        return;
    }

    const firstCard = visibleGalleryCards[0];
    const lastVisibleCard = visibleGalleryCards[5];
    const viewportHeight = (lastVisibleCard.offsetTop - firstCard.offsetTop) + lastVisibleCard.offsetHeight;

    galleryGrid.style.maxHeight = `${Math.ceil(viewportHeight)}px`;
    galleryGrid.classList.add("is-scrollable");
}

function queueGalleryGridViewportSync(resetScroll = false) {
    if (!galleryGrid || !galleryCards.length) {
        return;
    }

    shouldResetGalleryScroll = shouldResetGalleryScroll || resetScroll;

    if (isGalleryViewportSyncQueued) {
        return;
    }

    isGalleryViewportSyncQueued = true;
    window.requestAnimationFrame(() => {
        syncGalleryGridViewport(shouldResetGalleryScroll);
        shouldResetGalleryScroll = false;
        isGalleryViewportSyncQueued = false;
    });
}

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

function getServiceModalFocusableElements() {
    if (!serviceModal) {
        return [];
    }

    return Array.from(serviceModal.querySelectorAll(focusableSelector)).filter((element) => !element.closest("[hidden]"));
}

function finishServiceModalClose(restoreFocus) {
    if (!serviceModal) {
        return;
    }

    if (serviceModalOpenFrame !== null) {
        window.cancelAnimationFrame(serviceModalOpenFrame);
        serviceModalOpenFrame = null;
    }

    serviceModal.hidden = true;
    serviceModal.classList.remove("is-active");
    document.body.classList.remove("service-modal-open");
    document.removeEventListener("keydown", handleServiceModalKeydown);
    serviceModalContent?.replaceChildren();

    if (restoreFocus && lastServiceModalTrigger instanceof HTMLElement) {
        lastServiceModalTrigger.focus();
    }

    lastServiceModalTrigger = null;
    serviceModalCloseTimer = null;
}

function closeServiceModal(restoreFocus = true) {
    if (!serviceModal || serviceModal.hidden) {
        return;
    }

    serviceModal.classList.remove("is-active");

    if (serviceModalCloseTimer !== null) {
        window.clearTimeout(serviceModalCloseTimer);
    }

    if (serviceModalOpenFrame !== null) {
        window.cancelAnimationFrame(serviceModalOpenFrame);
        serviceModalOpenFrame = null;
    }

    if (prefersReducedMotion.matches) {
        finishServiceModalClose(restoreFocus);
        return;
    }

    serviceModalCloseTimer = window.setTimeout(() => {
        finishServiceModalClose(restoreFocus);
    }, 340);
}

function handleServiceModalKeydown(event) {
    if (!serviceModal || serviceModal.hidden) {
        return;
    }

    if (event.key === "Escape") {
        event.preventDefault();
        closeServiceModal();
        return;
    }

    if (event.key !== "Tab") {
        return;
    }

    const focusableElements = getServiceModalFocusableElements();

    if (!focusableElements.length) {
        event.preventDefault();
        return;
    }

    const firstFocusableElement = focusableElements[0];
    const lastFocusableElement = focusableElements[focusableElements.length - 1];

    if (event.shiftKey && document.activeElement === firstFocusableElement) {
        event.preventDefault();
        lastFocusableElement.focus();
    } else if (!event.shiftKey && document.activeElement === lastFocusableElement) {
        event.preventDefault();
        firstFocusableElement.focus();
    }
}

function openServiceModal(targetId, trigger) {
    if (!serviceModal || !serviceModalContent || !targetId) {
        return;
    }

    if (serviceModalCloseTimer !== null) {
        window.clearTimeout(serviceModalCloseTimer);
        serviceModalCloseTimer = null;
    }

    if (serviceModalOpenFrame !== null) {
        window.cancelAnimationFrame(serviceModalOpenFrame);
        serviceModalOpenFrame = null;
    }

    const template = document.getElementById(`${targetId}-template`);

    if (!(template instanceof HTMLTemplateElement)) {
        return;
    }

    serviceModalContent.replaceChildren(template.content.cloneNode(true));

    const modalTitle = serviceModalContent.querySelector(".service-detail-card__body h3");

    if (modalTitle) {
        modalTitle.id = "serviceDetailModalTitle";
    }

    lastServiceModalTrigger = trigger instanceof HTMLElement
        ? trigger
        : (document.activeElement instanceof HTMLElement ? document.activeElement : null);
    serviceModal.hidden = false;
    document.body.classList.add("service-modal-open");
    if (serviceModalDialog) {
        serviceModalDialog.scrollTop = 0;
    }
    document.addEventListener("keydown", handleServiceModalKeydown);
    serviceModalOpenFrame = window.requestAnimationFrame(() => {
        serviceModal.classList.add("is-active");
        serviceModal.querySelector(".service-modal__close")?.focus();
        serviceModalOpenFrame = null;
    });
}

function setContactFormFeedback(message, tone = "info", detail = "") {
    if (!contactFormFeedback) {
        return;
    }

    contactFormFeedback.replaceChildren();
    contactFormFeedback.classList.remove("is-success", "is-error", "is-info");

    if (message) {
        const titleSpan = document.createElement("span");
        titleSpan.className = "contact-form__feedback-title";
        titleSpan.textContent = message;
        contactFormFeedback.appendChild(titleSpan);

        if (detail) {
            const detailSpan = document.createElement("span");
            detailSpan.className = "contact-form__feedback-detail";
            detailSpan.textContent = detail;
            contactFormFeedback.appendChild(detailSpan);
        }

        contactFormFeedback.classList.add(`is-${tone}`);
    }
}

function openContactSuccessModal(trigger) {
    if (!contactSuccessModal) {
        return;
    }

    lastContactSuccessTrigger = trigger instanceof HTMLElement
        ? trigger
        : (document.activeElement instanceof HTMLElement ? document.activeElement : null);
    contactSuccessModal.hidden = false;
    document.body.classList.add("contact-success-modal-open");
    window.requestAnimationFrame(() => {
        contactSuccessModal.classList.add("is-active");
        contactSuccessModalPrimaryButton?.focus();
    });
}

function closeContactSuccessModal(restoreFocus = true) {
    if (!contactSuccessModal || contactSuccessModal.hidden) {
        return;
    }

    contactSuccessModal.classList.remove("is-active");
    contactSuccessModal.hidden = true;
    document.body.classList.remove("contact-success-modal-open");

    if (restoreFocus && lastContactSuccessTrigger instanceof HTMLElement) {
        lastContactSuccessTrigger.focus();
    }

    lastContactSuccessTrigger = null;
}

function handleContactSuccessModalKeydown(event) {
    if (!contactSuccessModal || contactSuccessModal.hidden) {
        return;
    }

    if (event.key === "Escape") {
        event.preventDefault();
        closeContactSuccessModal();
    }
}

async function submitContactInquiry(payload) {
    const response = await fetch(contactForm?.action || "api/inquiries/create.php", {
        method: "POST",
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json"
        },
        credentials: "same-origin",
        body: JSON.stringify(payload)
    });
    const rawText = await response.text();
    let responsePayload = {};

    try {
        responsePayload = rawText ? JSON.parse(rawText) : {};
    } catch (error) {
        responsePayload = {};
    }

    if (!response.ok || responsePayload.ok === false) {
        throw new Error(responsePayload.message || "Your inquiry could not be sent right now.");
    }

    return responsePayload;
}

if (contactForm && contactSubmitButton) {
    contactForm.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (!contactForm.reportValidity()) {
            return;
        }

        const formData = new FormData(contactForm);
        const payload = {
            name: String(formData.get("name") || "").trim(),
            email: String(formData.get("email") || "").trim(),
            message: String(formData.get("message") || "").trim()
        };
        const defaultButtonLabel = contactSubmitButton.textContent;

        contactSubmitButton.disabled = true;
        contactSubmitButton.textContent = "Sending...";
        setContactFormFeedback("Sending your message...", "info", "Please wait while we send your inquiry to the admin inbox.");

        try {
            await submitContactInquiry(payload);
            contactForm.reset();
            setContactFormFeedback("", "info");
            openContactSuccessModal(contactSubmitButton);
        } catch (error) {
            setContactFormFeedback(
                "We could not send your message.",
                "error",
                error instanceof Error ? error.message : "Please try again in a moment."
            );
        } finally {
            contactSubmitButton.disabled = false;
            contactSubmitButton.textContent = defaultButtonLabel;
        }
    });
}

contactSuccessModalCloseControls.forEach((control) => {
    control.addEventListener("click", () => {
        closeContactSuccessModal();
    });
});

document.addEventListener("keydown", handleContactSuccessModalKeydown);

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

queueGalleryGridViewportSync();

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

        queueGalleryGridViewportSync(true);
    });
});

window.addEventListener("resize", () => {
    queueGalleryGridViewportSync();
});

window.addEventListener("load", () => {
    queueGalleryGridViewportSync();
});

galleryImages.forEach((image) => {
    if (image.complete) {
        return;
    }

    image.addEventListener("load", () => {
        queueGalleryGridViewportSync();
    }, { once: true });
});

if ("ResizeObserver" in window && galleryGrid) {
    const galleryResizeObserver = new ResizeObserver(() => {
        queueGalleryGridViewportSync();
    });

    galleryCards.forEach((card) => {
        galleryResizeObserver.observe(card);
    });
}

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

if (serviceModal && serviceModalContent && serviceModalTriggers.length) {
    serviceModalTriggers.forEach((trigger) => {
        trigger.addEventListener("click", () => {
            openServiceModal(trigger.dataset.serviceModalTarget || "", trigger);
        });
    });

    serviceModalCloseControls.forEach((control) => {
        control.addEventListener("click", () => {
            closeServiceModal();
        });
    });

    serviceModalContent.addEventListener("click", (event) => {
        const clickTarget = event.target instanceof Element ? event.target : null;
        const sectionLink = clickTarget ? clickTarget.closest('a[href^="#"]') : null;

        if (!sectionLink) {
            return;
        }

        closeServiceModal(false);
    });
}
