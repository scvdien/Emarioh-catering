(function initializePublicPackages() {
    const catalogApi = window.EmariohPackageCatalog;
    const perHeadContainer = document.getElementById("publicPerHeadPackages");
    const perHeadEmptyState = document.getElementById("publicPerHeadEmpty");
    const celebrationContainer = document.getElementById("publicCelebrationPackages");
    const celebrationEmptyState = document.getElementById("publicCelebrationEmpty");

    if (!perHeadContainer || !celebrationContainer || !perHeadEmptyState || !celebrationEmptyState) {
        return;
    }

    const fallbackPerHeadPackages = [
        {
            name: "All Occasions 350 / Head",
            guestLabel: "Minimum of 50 persons",
            rateLabel: "PHP 350/HEAD",
            description: "Emarioh Rate for All Occasions",
            inclusions: ["Rice", "Chicken", "Pork", "Noodles/Pasta (Choose 1)", "Vegetables/Salad (Choose 1)", "Beverage"]
        },
        {
            name: "All Occasions 400 / Head",
            guestLabel: "Minimum of 50 persons",
            rateLabel: "PHP 400/HEAD",
            description: "Emarioh Rate for All Occasions",
            inclusions: ["Rice", "Chicken", "Pork", "Fish", "Vegetables/Salad (Choose 1)", "Beverage"]
        },
        {
            name: "All Occasions 500 / Head",
            guestLabel: "Minimum of 50 persons",
            rateLabel: "PHP 500/HEAD",
            description: "Emarioh Rate for All Occasions",
            inclusions: ["Rice", "Soup", "Chicken", "Pork", "Fish", "Vegetables/Salad (Choose 1)", "Beverage", "Dessert"]
        },
        {
            name: "All Occasions 600 / Head",
            guestLabel: "Minimum of 50 persons",
            rateLabel: "PHP 600/HEAD",
            description: "Emarioh Rate for All Occasions",
            inclusions: ["Rice", "Soup", "Chicken", "Pork", "Fish", "Noodles/Pasta (Choose 1)", "Vegetables/Salad (Choose 1)", "2 Kinds of Beverage", "Dessert"]
        }
    ];

    const fallbackCelebrationPackage = {
        name: "Wedding/ Birthday Package",
        pricingTiers: [
            { label: "50pax", price: "P50,000" },
            { label: "100pax", price: "P85,000" },
            { label: "150pax", price: "P120,000" }
        ],
        inclusions: [
            "3 Main Dish (Pork, Chicken, Fish)",
            "Vegetables, Soup, Dessert",
            "Unlimited Rice, One (1) Round Choice of Beverage, Unlimited Water & Ice",
            "Two Tier Wedding Cake",
            "Full Buffet Set Up",
            "Complete Dining Set Up",
            "Tiffany Chairs",
            "Stylish Couch for Couple",
            "Themed Event Styling",
            "Table Centerpiece for Guests' Table",
            "Elegant Set-Up for VIP",
            "Seated Service for VIP Guests",
            "Table for Cake",
            "Menu Tasting for two (2) once confirmed",
            "Full Lights & Sound System",
            "Four (4) Hours use of Venue",
            "Uniformed Service Crew"
        ]
    };

    function escapeHtml(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/\"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    function getPerHeadPackages() {
        const dynamicPackages = catalogApi?.getActivePackages?.("per-head") || [];
        return dynamicPackages.length ? dynamicPackages : fallbackPerHeadPackages;
    }

    function getCelebrationPackage() {
        const dynamicPackages = catalogApi?.getActivePackages?.("celebration") || [];
        const featuredDynamicPackage = dynamicPackages.find((packageItem) => Array.isArray(packageItem.pricingTiers) && packageItem.pricingTiers.length);
        return featuredDynamicPackage || dynamicPackages[0] || fallbackCelebrationPackage;
    }


    function renderPerHeadPackages() {
        const packages = getPerHeadPackages();

        perHeadContainer.innerHTML = packages.map((packageItem) => {
            const inclusions = (packageItem.inclusions || []).map((item) => `<li>${escapeHtml(item)}</li>`).join("");
            const heading = packageItem.description || "Emarioh Rate for All Occasions";
            const subheading = packageItem.guestLabel || "Minimum of 50 persons";

            return `
                <article class="packages-tier-card is-visible" data-reveal aria-label="${escapeHtml(packageItem.name || packageItem.rateLabel)}">
                    <p class="packages-tier-card__heading">${escapeHtml(heading)}</p>
                    <p class="packages-tier-card__sub">${escapeHtml(subheading)}</p>
                    <p class="packages-tier-card__price">${escapeHtml(packageItem.rateLabel)}</p>
                    <ul class="packages-tier-card__list">${inclusions}</ul>
                </article>
            `;
        }).join("");

        perHeadEmptyState.hidden = true;
        perHeadContainer.hidden = false;
    }

    function renderCelebrationPackages() {
        const featuredPackage = getCelebrationPackage();
        const pricingTiers = Array.isArray(featuredPackage.pricingTiers) && featuredPackage.pricingTiers.length
            ? featuredPackage.pricingTiers
            : [{ label: featuredPackage.guestLabel || "Package", price: featuredPackage.rateLabel || "Rate pending" }];
        const ratesMarkup = pricingTiers.map((tier) => `<span class="packages-event-card__pill">${escapeHtml(tier.label)}</span>`).join("");
        const pricesMarkup = pricingTiers.map((tier) => `<p class="packages-event-card__price">${escapeHtml(tier.price)}</p>`).join("");
        const inclusionsMarkup = (featuredPackage.inclusions || []).map((item) => `<li>${escapeHtml(item)}</li>`).join("");

        celebrationContainer.innerHTML = `
            <article class="packages-event-card is-visible" data-reveal aria-label="${escapeHtml(featuredPackage.name || "Celebration Package")}">
                <div class="packages-event-card__rates" aria-label="Wedding and birthday package sizes">${ratesMarkup}</div>
                <div class="packages-event-card__prices" aria-label="Wedding and birthday package prices">${pricesMarkup}</div>
                <div class="packages-event-card__content">
                    <ul class="packages-event-card__list">${inclusionsMarkup}</ul>
                    <div class="packages-event-card__actions">
                        <p>Ideal for weddings, birthdays, anniversaries, and milestone celebrations.</p>
                        <a class="public-button public-button--gold" href="registration.php">Request This Package</a>
                    </div>
                </div>
            </article>
        `;

        celebrationEmptyState.hidden = true;
        celebrationContainer.hidden = false;
    }

    function renderAllPackages() {
        renderPerHeadPackages();
        renderCelebrationPackages();
    }

    renderAllPackages();

    if (catalogApi?.PACKAGE_CHANGE_EVENT) {
        window.addEventListener(catalogApi.PACKAGE_CHANGE_EVENT, renderAllPackages);
    }
})();



