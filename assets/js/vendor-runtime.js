(function () {
    const iconGlyphs = {
        "bi-arrow-clockwise": "\u21BB",
        "bi-arrow-left": "\u2190",
        "bi-balloon-heart": "\u2665",
        "bi-box-arrow-right": "\u21AA",
        "bi-briefcase": "\uD83D\uDCBC",
        "bi-calendar2-check": "\uD83D\uDDD3",
        "bi-calendar2-plus": "\uD83D\uDDD3",
        "bi-calendar2-week": "\uD83D\uDDD3",
        "bi-calendar3": "\uD83D\uDDD3",
        "bi-calendar-event": "\uD83D\uDDD3",
        "bi-calendar-week": "\uD83D\uDDD3",
        "bi-chat-square-text": "\uD83D\uDCAC",
        "bi-check2-circle": "\u2713",
        "bi-chevron-left": "\u2039",
        "bi-chevron-right": "\u203A",
        "bi-clipboard-check": "\uD83D\uDCCB",
        "bi-clock": "\uD83D\uDD52",
        "bi-envelope": "\u2709",
        "bi-envelope-paper": "\u2709",
        "bi-eye": "\u25C9",
        "bi-eye-slash": "\u2298",
        "bi-gear": "\u2699",
        "bi-geo-alt": "\u2316",
        "bi-grid-1x2-fill": "\u25A6",
        "bi-hourglass-split": "\u231B",
        "bi-image": "\uD83D\uDDBC",
        "bi-images": "\uD83D\uDDBC",
        "bi-info-circle": "\u24D8",
        "bi-journal-check": "\uD83D\uDCD8",
        "bi-journal-richtext": "\uD83D\uDCD8",
        "bi-list": "\u2630",
        "bi-patch-check": "\u2713",
        "bi-pause-circle": "\u23F8",
        "bi-people": "\uD83D\uDC65",
        "bi-person-badge": "\uD83E\uDEAA",
        "bi-person-circle": "\uD83D\uDC64",
        "bi-person-gear": "\u2699",
        "bi-phone": "\u260E",
        "bi-qr-code": "\u2317",
        "bi-receipt": "\uD83E\uDDFE",
        "bi-receipt-cutoff": "\uD83E\uDDFE",
        "bi-save": "\uD83D\uDCBE",
        "bi-search": "\u2315",
        "bi-shield-check": "\uD83D\uDEE1",
        "bi-shield-lock": "\uD83D\uDD12",
        "bi-stars": "\u2726",
        "bi-telephone": "\u260E",
        "bi-three-dots-vertical": "\u22EE",
        "bi-trash": "\uD83D\uDDD1",
        "bi-wallet2": "\uD83D\uDC5B",
        "bi-x-circle": "\u2716",
        "bi-x-lg": "\u2715"
    };

    function iconFontIsReady() {
        if (!document.fonts || typeof document.fonts.check !== "function") {
            return false;
        }

        return document.fonts.check('1em "bootstrap-icons"');
    }

    function installIconFallbacks() {
        if (iconFontIsReady()) {
            return;
        }

        document.querySelectorAll(".bi").forEach((element) => {
            if (element.dataset.biFallbackReady === "1") {
                return;
            }

            const iconClass = Array.from(element.classList).find((className) => className.indexOf("bi-") === 0);

            if (!iconClass) {
                return;
            }

            element.textContent = "";
            element.dataset.biFallback = "text";
            element.dataset.biFallbackReady = "1";

            const glyph = document.createElement("span");
            glyph.className = "bi-fallback-glyph";
            glyph.setAttribute("aria-hidden", "true");
            glyph.textContent = iconGlyphs[iconClass] || "\u2022";
            element.appendChild(glyph);
        });
    }

    function resolveTarget(trigger) {
        const rawSelector = (trigger.getAttribute("data-bs-target") || trigger.getAttribute("href") || "").trim();

        if (rawSelector === "" || rawSelector === "#") {
            return null;
        }

        if (rawSelector.charAt(0) !== "#") {
            return null;
        }

        try {
            return document.querySelector(rawSelector);
        } catch (error) {
            return null;
        }
    }

    function normalizeBackdropValue(value, defaultValue) {
        if (value === undefined || value === null || value === "") {
            return defaultValue;
        }

        const normalizedValue = String(value).toLowerCase();
        return normalizedValue !== "false" && normalizedValue !== "0";
    }

    function createBackdrop(kind, onClick) {
        const backdrop = document.createElement("div");
        backdrop.className = "bootstrap-runtime-backdrop bootstrap-runtime-backdrop--" + kind;

        if (typeof onClick === "function") {
            backdrop.addEventListener("click", onClick);
        }

        document.body.appendChild(backdrop);
        return backdrop;
    }

    function removeBackdrop(backdrop) {
        if (backdrop && backdrop.parentNode) {
            backdrop.parentNode.removeChild(backdrop);
        }
    }

    const needsBootstrapRuntime = !window.bootstrap;

    if (needsBootstrapRuntime) {
        const modalStore = new WeakMap();
        const offcanvasStore = new WeakMap();
        const tabStore = new WeakMap();

        class Modal {
            constructor(element, options) {
                this._element = element;
                const elementBackdrop = element ? element.getAttribute("data-bs-backdrop") : null;
                const providedOptions = options || {};
                this._options = {
                    backdrop: normalizeBackdropValue(
                        Object.prototype.hasOwnProperty.call(providedOptions, "backdrop") ? providedOptions.backdrop : elementBackdrop,
                        true
                    )
                };
                this._backdrop = null;
                modalStore.set(element, this);
            }

            static getInstance(element) {
                return element ? modalStore.get(element) || null : null;
            }

            static getOrCreateInstance(element, options) {
                return Modal.getInstance(element) || new Modal(element, options);
            }

            show() {
                if (!this._element) {
                    return;
                }

                this._element.style.display = "block";
                this._element.removeAttribute("aria-hidden");
                this._element.setAttribute("aria-modal", "true");
                this._element.classList.add("show");
                document.body.classList.add("modal-open");

                if (this._options.backdrop) {
                    this._backdrop = createBackdrop("modal", () => this.hide());
                }
            }

            hide() {
                if (!this._element) {
                    return;
                }

                this._element.classList.remove("show");
                this._element.setAttribute("aria-hidden", "true");
                this._element.removeAttribute("aria-modal");
                this._element.style.display = "none";
                removeBackdrop(this._backdrop);
                this._backdrop = null;

                if (!document.querySelector(".modal.show")) {
                    document.body.classList.remove("modal-open");
                }
            }

            toggle() {
                if (this._element && this._element.classList.contains("show")) {
                    this.hide();
                    return;
                }

                this.show();
            }
        }

        class Offcanvas {
            constructor(element, options) {
                this._element = element;
                const providedOptions = options || {};
                this._options = {
                    backdrop: normalizeBackdropValue(providedOptions.backdrop, true)
                };
                this._backdrop = null;
                offcanvasStore.set(element, this);
            }

            static getInstance(element) {
                return element ? offcanvasStore.get(element) || null : null;
            }

            static getOrCreateInstance(element, options) {
                return Offcanvas.getInstance(element) || new Offcanvas(element, options);
            }

            show() {
                if (!this._element) {
                    return;
                }

                this._element.classList.add("show");
                this._element.removeAttribute("aria-hidden");
                this._element.setAttribute("aria-modal", "true");
                document.body.classList.add("offcanvas-open");

                if (this._options.backdrop) {
                    this._backdrop = createBackdrop("offcanvas", () => this.hide());
                }
            }

            hide() {
                if (!this._element) {
                    return;
                }

                this._element.classList.remove("show");
                this._element.setAttribute("aria-hidden", "true");
                this._element.removeAttribute("aria-modal");
                removeBackdrop(this._backdrop);
                this._backdrop = null;

                if (!document.querySelector(".offcanvas.show")) {
                    document.body.classList.remove("offcanvas-open");
                }
            }

            toggle() {
                if (this._element && this._element.classList.contains("show")) {
                    this.hide();
                    return;
                }

                this.show();
            }
        }

        class Tab {
            constructor(element) {
                this._element = element;
                tabStore.set(element, this);
            }

            static getInstance(element) {
                return element ? tabStore.get(element) || null : null;
            }

            static getOrCreateInstance(element) {
                return Tab.getInstance(element) || new Tab(element);
            }

            show() {
                if (!this._element) {
                    return;
                }

                const navRoot = this._element.closest(".nav");

                if (navRoot) {
                    navRoot.querySelectorAll("[data-bs-toggle=\"pill\"], [data-bs-toggle=\"tab\"], .nav-link").forEach((button) => {
                        button.classList.remove("active");
                        button.setAttribute("aria-selected", button === this._element ? "true" : "false");
                    });
                }

                const targetPane = resolveTarget(this._element);

                if (targetPane && targetPane.parentElement) {
                    targetPane.parentElement.querySelectorAll(".tab-pane").forEach((pane) => {
                        pane.classList.remove("active", "show");
                    });

                    targetPane.classList.add("active", "show");
                }

                this._element.classList.add("active");
                this._element.setAttribute("aria-selected", "true");
            }
        }

        window.bootstrap = {
            Modal: Modal,
            Offcanvas: Offcanvas,
            Tab: Tab
        };
        document.addEventListener("click", (event) => {
            const dismissTrigger = event.target.closest("[data-bs-dismiss]");

            if (dismissTrigger) {
                const dismissKind = (dismissTrigger.getAttribute("data-bs-dismiss") || "").toLowerCase();
                const parent = dismissTrigger.closest(".modal, .offcanvas");

                if (dismissKind === "modal" && parent) {
                    event.preventDefault();
                    window.bootstrap.Modal.getOrCreateInstance(parent).hide();
                    return;
                }

                if (dismissKind === "offcanvas" && parent) {
                    event.preventDefault();
                    window.bootstrap.Offcanvas.getOrCreateInstance(parent).hide();
                    return;
                }
            }

            const toggleTrigger = event.target.closest("[data-bs-toggle]");

            if (!toggleTrigger) {
                const modalRoot = event.target.classList.contains("modal") ? event.target : null;

                if (modalRoot) {
                    window.bootstrap.Modal.getOrCreateInstance(modalRoot).hide();
                }

                return;
            }

            const toggleKind = (toggleTrigger.getAttribute("data-bs-toggle") || "").toLowerCase();

            if (toggleKind === "modal") {
                const modalTarget = resolveTarget(toggleTrigger);

                if (modalTarget) {
                    event.preventDefault();
                    window.bootstrap.Modal.getOrCreateInstance(modalTarget).show();
                }

                return;
            }

            if (toggleKind === "offcanvas") {
                const offcanvasTarget = resolveTarget(toggleTrigger);

                if (offcanvasTarget) {
                    event.preventDefault();
                    window.bootstrap.Offcanvas.getOrCreateInstance(offcanvasTarget).toggle();
                }

                return;
            }

            if (toggleKind === "pill" || toggleKind === "tab") {
                event.preventDefault();
                window.bootstrap.Tab.getOrCreateInstance(toggleTrigger).show();
            }
        });

        document.addEventListener("keydown", (event) => {
            if (event.key !== "Escape") {
                return;
            }

            const activeModal = document.querySelector(".modal.show");

            if (activeModal) {
                window.bootstrap.Modal.getOrCreateInstance(activeModal).hide();
                return;
            }

            const activeOffcanvas = document.querySelector(".offcanvas.show");

            if (activeOffcanvas) {
                window.bootstrap.Offcanvas.getOrCreateInstance(activeOffcanvas).hide();
            }
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", () => {
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(installIconFallbacks).catch(installIconFallbacks);
                window.setTimeout(installIconFallbacks, 700);
                return;
            }

            installIconFallbacks();
        });
    } else {
        installIconFallbacks();
    }
})();
