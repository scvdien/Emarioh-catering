(function initializePackageCatalog(global) {
    const STORAGE_KEY = "emariohPackageCatalog.v1";
    const PACKAGE_CHANGE_EVENT = "emarioh:packages-changed";
    const PACKAGE_SELECTION_SEPARATOR = "::";
    let hasHydratedFromServer = false;
    const GROUPS = {
        "per-head": {
            label: "Per-Head Packages",
            shortLabel: "Per-head",
            description: "Flexible buffet-ready offers for reunions, corporate lunches, and medium-sized gatherings."
        },
        celebration: {
            label: "Celebration Packages",
            shortLabel: "Celebration",
            description: "Event-ready packages for weddings, birthdays, debuts, anniversaries, and outdoor celebrations."
        }
    };
    const STATUS_META = {
        active: {
            label: "Active",
            pillClass: "approved"
        },
        review: {
            label: "Review",
            pillClass: "review"
        },
        inactive: {
            label: "Inactive",
            pillClass: "inactive"
        }
    };
    const DEFAULT_PACKAGES = [
        {
            id: "silver-buffet-package",
            group: "per-head",
            name: "All Occasions 350 / Head",
            category: "Per-Head",
            guestLabel: "Minimum of 50 persons",
            rateLabel: "PHP 350/HEAD",
            status: "active",
            description: "Emarioh Rate for All Occasions",
            tags: ["Min. 50 persons", "All occasions"],
            inclusions: ["Rice", "Chicken", "Pork", "Noodles/Pasta (Choose 1)", "Vegetables/Salad (Choose 1)", "Beverage"]
        },
        {
            id: "gold-buffet-package",
            group: "per-head",
            name: "All Occasions 400 / Head",
            category: "Per-Head",
            guestLabel: "Minimum of 50 persons",
            rateLabel: "PHP 400/HEAD",
            status: "active",
            description: "Emarioh Rate for All Occasions",
            tags: ["Min. 50 persons", "Fish included"],
            inclusions: ["Rice", "Chicken", "Pork", "Fish", "Vegetables/Salad (Choose 1)", "Beverage"]
        },
        {
            id: "premium-buffet-package",
            group: "per-head",
            name: "All Occasions 500 / Head",
            category: "Per-Head",
            guestLabel: "Minimum of 50 persons",
            rateLabel: "PHP 500/HEAD",
            status: "active",
            description: "Emarioh Rate for All Occasions",
            tags: ["Min. 50 persons", "Dessert included"],
            inclusions: ["Rice", "Soup", "Chicken", "Pork", "Fish", "Vegetables/Salad (Choose 1)", "Beverage", "Dessert"]
        },
        {
            id: "signature-buffet-package",
            group: "per-head",
            name: "All Occasions 600 / Head",
            category: "Per-Head",
            guestLabel: "Minimum of 50 persons",
            rateLabel: "PHP 600/HEAD",
            status: "active",
            description: "Emarioh Rate for All Occasions",
            tags: ["Min. 50 persons", "Premium buffet"],
            inclusions: ["Rice", "Soup", "Chicken", "Pork", "Fish", "Noodles/Pasta (Choose 1)", "Vegetables/Salad (Choose 1)", "2 Kinds of Beverage", "Dessert"]
        },
        {
            id: "wedding-birthday-package",
            group: "celebration",
            name: "Wedding/ Birthday Package",
            category: "Wedding / Birthday",
            guestLabel: "50pax | 100pax | 150pax",
            rateLabel: "P50,000 | P85,000 | P120,000",
            status: "active",
            description: "Celebration package for weddings, birthdays, and formal events.",
            tags: ["50pax", "100pax", "150pax"],
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
        }
    ];
    const LEGACY_DEFAULT_PACKAGE_MAP = {
        "silver-buffet-package": {
            name: "Silver Buffet Package",
            rateLabel: "PHP 350 / head"
        },
        "gold-buffet-package": {
            name: "Gold Buffet Package",
            rateLabel: "PHP 400 / head"
        },
        "premium-buffet-package": {
            name: "Premium Buffet Package",
            rateLabel: "PHP 500 / head"
        },
        "signature-buffet-package": {
            name: "Signature Buffet Package",
            rateLabel: "PHP 600 / head"
        },
        "corporate-lunch-buffet": {
            name: "Corporate Lunch Buffet",
            rateLabel: "PHP 32,000"
        },
        "wedding-classic-package": {
            name: "Wedding Classic Package",
            rateLabel: "PHP 85,000"
        },
        "debut-premium-package": {
            name: "Debut Premium Package",
            rateLabel: "PHP 96,500"
        },
        "birthday-celebration-set": {
            name: "Birthday Celebration Set",
            rateLabel: "PHP 41,000"
        },
        "garden-reception-package": {
            name: "Garden Reception Package",
            rateLabel: "PHP 68,000"
        }
    };
    const DEFAULT_PACKAGE_BY_ID = Object.fromEntries(
        DEFAULT_PACKAGES.map((packageItem) => [packageItem.id, packageItem])
    );

    function cloneValue(value) {
        return JSON.parse(JSON.stringify(value));
    }

    function slugify(value) {
        return String(value || "")
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, "-")
            .replace(/^-+|-+$/g, "");
    }

    function normalizeList(value) {
        if (Array.isArray(value)) {
            return value
                .map((item) => String(item || "").trim())
                .filter(Boolean);
        }

        return String(value || "")
            .split(/\r?\n|,/)
            .map((item) => item.trim())
            .filter(Boolean);
    }

    function normalizePricingTiers(value, fallbackGuestLabel = "", fallbackRateLabel = "") {
        if (Array.isArray(value)) {
            return value
                .map((item) => ({
                    label: String(item?.label || "").trim(),
                    price: String(item?.price || "").trim()
                }))
                .filter((item) => item.label && item.price)
                .slice(0, 4);
        }

        const guestLabels = String(fallbackGuestLabel || "")
            .split("|")
            .map((item) => item.trim())
            .filter(Boolean);
        const rateLabels = String(fallbackRateLabel || "")
            .split("|")
            .map((item) => item.trim())
            .filter(Boolean);

        if (!guestLabels.length || guestLabels.length !== rateLabels.length) {
            return [];
        }

        return guestLabels.map((label, index) => ({
            label,
            price: rateLabels[index]
        })).filter((item) => item.label && item.price).slice(0, 4);
    }

    function normalizeDownPaymentTiers(value, pricingTiers = [], fallbackDownPaymentAmount = "") {
        if (!Array.isArray(pricingTiers) || !pricingTiers.length) {
            return [];
        }

        const rawTierMap = new Map();

        if (Array.isArray(value)) {
            value.forEach((item) => {
                const tierLabel = String(item?.label || "").trim();

                if (!tierLabel) {
                    return;
                }

                rawTierMap.set(
                    tierLabel.toLowerCase(),
                    String(item?.amount || item?.downPaymentAmount || "").trim()
                );
            });
        }

        const fallbackAmount = String(fallbackDownPaymentAmount || "").trim();

        return pricingTiers.map((tier) => {
            const tierLabel = String(tier?.label || "").trim();
            const mappedAmount = rawTierMap.has(tierLabel.toLowerCase())
                ? rawTierMap.get(tierLabel.toLowerCase())
                : fallbackAmount;

            return {
                label: tierLabel,
                amount: String(mappedAmount || "").trim()
            };
        }).filter((tier) => tier.label);
    }

    function normalizeStatus(status) {
        return STATUS_META[status] ? status : "review";
    }

    function normalizeGroup(group) {
        return GROUPS[group] ? group : "per-head";
    }

    function normalizePackage(rawPackage, fallbackIndex = 0) {
        const packageName = String(rawPackage?.name || "").trim() || `Package ${fallbackIndex + 1}`;
        const packageId = String(rawPackage?.id || "").trim() || `${slugify(packageName) || `package-${fallbackIndex + 1}`}`;
        const group = normalizeGroup(rawPackage?.group);
        const pricingTiers = normalizePricingTiers(rawPackage?.pricingTiers, rawPackage?.guestLabel, rawPackage?.rateLabel);
        const downPaymentAmount = String(rawPackage?.downPaymentAmount || "").trim();
        const downPaymentTiers = normalizeDownPaymentTiers(rawPackage?.downPaymentTiers, pricingTiers, downPaymentAmount);
        const allowDownPayment = rawPackage?.allowDownPayment === undefined
            ? Boolean(downPaymentAmount) || downPaymentTiers.some((tier) => tier.amount !== "")
            : Boolean(rawPackage.allowDownPayment);

        return {
            id: packageId,
            group,
            name: packageName,
            category: String(rawPackage?.category || GROUPS[group].shortLabel).trim() || GROUPS[group].shortLabel,
            guestLabel: String(rawPackage?.guestLabel || "Guest count to follow").trim() || "Guest count to follow",
            rateLabel: String(rawPackage?.rateLabel || "Rate pending").trim() || "Rate pending",
            allowDownPayment,
            downPaymentAmount,
            downPaymentTiers,
            status: normalizeStatus(rawPackage?.status),
            description: String(rawPackage?.description || "Package details will be updated soon.").trim() || "Package details will be updated soon.",
            tags: normalizeList(rawPackage?.tags).slice(0, 6),
            pricingTiers,
            inclusions: normalizeList(rawPackage?.inclusions).slice(0, 20)
        };
    }

    function getDefaultCatalog() {
        return DEFAULT_PACKAGES.map((packageItem, index) => normalizePackage(packageItem, index));
    }

    function isLegacyDefaultPackage(packageItem) {
        const legacyPackage = LEGACY_DEFAULT_PACKAGE_MAP[String(packageItem?.id || "").trim()];

        if (!legacyPackage) {
            return false;
        }

        return String(packageItem?.name || "").trim() === legacyPackage.name
            && String(packageItem?.rateLabel || "").trim() === legacyPackage.rateLabel;
    }

    function migrateLegacyCatalog(catalog) {
        let didChange = false;
        const migratedCatalog = [];

        catalog.forEach((packageItem) => {
            const packageId = String(packageItem?.id || "").trim();

            if (!isLegacyDefaultPackage(packageItem)) {
                migratedCatalog.push(packageItem);
                return;
            }

            if (packageId === "corporate-lunch-buffet" || packageId === "debut-premium-package" || packageId === "birthday-celebration-set" || packageId === "garden-reception-package") {
                didChange = true;
                return;
            }

            if (packageId === "wedding-classic-package") {
                migratedCatalog.push({
                    ...DEFAULT_PACKAGE_BY_ID["wedding-birthday-package"],
                    status: normalizeStatus(packageItem?.status)
                });
                didChange = true;
                return;
            }

            const updatedPackage = DEFAULT_PACKAGE_BY_ID[packageId];

            if (!updatedPackage) {
                migratedCatalog.push(packageItem);
                return;
            }

            migratedCatalog.push({
                ...updatedPackage,
                status: normalizeStatus(packageItem?.status)
            });
            didChange = true;
        });

        return {
            catalog: migratedCatalog,
            didChange
        };
    }

    function readCatalogFromStorage() {
        const serverCatalog = Array.isArray(global.EmariohServerPackageCatalog)
            ? global.EmariohServerPackageCatalog
            : [];

        if (serverCatalog.length && !hasHydratedFromServer) {
            const normalizedServerCatalog = serverCatalog.map((packageItem, index) => normalizePackage(packageItem, index));
            hasHydratedFromServer = true;

            try {
                global.localStorage?.setItem(STORAGE_KEY, JSON.stringify(normalizedServerCatalog));
            } catch (error) {
                return normalizedServerCatalog;
            }

            return normalizedServerCatalog;
        }

        try {
            const storedValue = global.localStorage?.getItem(STORAGE_KEY);

            if (!storedValue) {
                return getDefaultCatalog();
            }

            const parsedValue = JSON.parse(storedValue);

            if (!Array.isArray(parsedValue) || !parsedValue.length) {
                return getDefaultCatalog();
            }

            const { catalog, didChange } = migrateLegacyCatalog(parsedValue);
            const normalizedCatalog = catalog.map((packageItem, index) => normalizePackage(packageItem, index));

            if (didChange) {
                global.localStorage?.setItem(STORAGE_KEY, JSON.stringify(normalizedCatalog));
            }

            return normalizedCatalog;
        } catch (error) {
            return getDefaultCatalog();
        }
    }

    function writeCatalogToStorage(catalog) {
        const normalizedCatalog = catalog.map((packageItem, index) => normalizePackage(packageItem, index));
        hasHydratedFromServer = true;
        global.EmariohServerPackageCatalog = cloneValue(normalizedCatalog);

        try {
            global.localStorage?.setItem(STORAGE_KEY, JSON.stringify(normalizedCatalog));
        } catch (error) {
            return normalizedCatalog;
        }

        return normalizedCatalog;
    }

    function dispatchCatalogChange(catalog) {
        global.dispatchEvent(new CustomEvent(PACKAGE_CHANGE_EVENT, {
            detail: {
                catalog: cloneValue(catalog)
            }
        }));
    }

    function getCatalog() {
        return cloneValue(readCatalogFromStorage());
    }

    function saveCatalog(nextCatalog, options = {}) {
        const normalizedCatalog = writeCatalogToStorage(nextCatalog);

        if (options.emit !== false) {
            dispatchCatalogChange(normalizedCatalog);
        }

        return cloneValue(normalizedCatalog);
    }

    function getPackageById(packageId) {
        return getCatalog().find((packageItem) => packageItem.id === packageId) || null;
    }

    function getPackagesByGroup(group, options = {}) {
        const { activeOnly = false } = options;

        return getCatalog()
            .filter((packageItem) => packageItem.group === group)
            .filter((packageItem) => !activeOnly || packageItem.status === "active");
    }

    function getActivePackages(group) {
        return getPackagesByGroup(group, { activeOnly: true });
    }

    function buildPackageSelectionValue(packageId, tierLabel = "") {
        const normalizedPackageId = String(packageId || "").trim();
        const normalizedTierLabel = String(tierLabel || "").trim();
        return normalizedTierLabel
            ? `${normalizedPackageId}${PACKAGE_SELECTION_SEPARATOR}${normalizedTierLabel}`
            : normalizedPackageId;
    }

    function parsePackageSelectionValue(selectionValue) {
        const normalizedValue = String(selectionValue || "").trim();

        if (!normalizedValue) {
            return {
                packageId: "",
                tierLabel: ""
            };
        }

        const [packageId, ...tierParts] = normalizedValue.split(PACKAGE_SELECTION_SEPARATOR);

        return {
            packageId: String(packageId || "").trim(),
            tierLabel: tierParts.join(PACKAGE_SELECTION_SEPARATOR).trim()
        };
    }

    function getPackagePricingTier(packageItem, tierLabel = "") {
        const normalizedTierLabel = String(tierLabel || "").trim().toLowerCase();

        if (!packageItem || !Array.isArray(packageItem.pricingTiers) || !packageItem.pricingTiers.length || !normalizedTierLabel) {
            return null;
        }

        return packageItem.pricingTiers.find((tier) => String(tier?.label || "").trim().toLowerCase() === normalizedTierLabel) || null;
    }

    function getPackageDownPaymentAmount(packageItem, tierLabel = "") {
        const normalizedTierLabel = String(tierLabel || "").trim().toLowerCase();
        const downPaymentTiers = Array.isArray(packageItem?.downPaymentTiers)
            ? packageItem.downPaymentTiers
            : [];

        if (normalizedTierLabel && downPaymentTiers.length) {
            const matchedTier = downPaymentTiers.find((tier) => (
                String(tier?.label || "").trim().toLowerCase() === normalizedTierLabel
            ));

            if (matchedTier && String(matchedTier.amount || "").trim() !== "") {
                return String(matchedTier.amount || "").trim();
            }
        }

        if (!normalizedTierLabel && downPaymentTiers.length === 1) {
            return String(downPaymentTiers[0]?.amount || "").trim();
        }

        return String(packageItem?.downPaymentAmount || "").trim();
    }

    function getPackageOptionSelections(packageItem) {
        if (!packageItem) {
            return [];
        }

        if (!Array.isArray(packageItem.pricingTiers) || !packageItem.pricingTiers.length) {
            return [{
                value: packageItem.id,
                label: getPackageOptionLabel(packageItem),
                packageId: packageItem.id,
                tierLabel: "",
                tierPrice: ""
            }];
        }

        return packageItem.pricingTiers.map((tier) => ({
            value: buildPackageSelectionValue(packageItem.id, tier.label),
            label: getPackageOptionLabel(packageItem, tier),
            packageId: packageItem.id,
            tierLabel: String(tier?.label || "").trim(),
            tierPrice: String(tier?.price || "").trim()
        }));
    }

    function getPackageOptionLabel(packageItem, pricingTier = null) {
        const tierPrice = String(pricingTier?.price || "").trim();

        if (tierPrice) {
            return `${packageItem.name} - ${tierPrice}`;
        }

        return `${packageItem.name} - ${packageItem.rateLabel}`;
    }

    function upsertPackage(packageInput) {
        const catalog = getCatalog();
        const existingIndex = catalog.findIndex((packageItem) => packageItem.id === packageInput.id);
        const existingPackage = existingIndex >= 0 ? catalog[existingIndex] : null;
        const nextPackage = normalizePackage({
            ...(existingPackage || {}),
            ...packageInput
        }, existingIndex >= 0 ? existingIndex : catalog.length);

        if (existingIndex >= 0) {
            catalog.splice(existingIndex, 1, nextPackage);
        } else {
            catalog.push(nextPackage);
        }

        return saveCatalog(catalog);
    }
    function deletePackage(packageId) {
        const nextCatalog = getCatalog().filter((packageItem) => packageItem.id !== packageId);
        return saveCatalog(nextCatalog);
    }

    function resetCatalog() {
        return saveCatalog(getDefaultCatalog());
    }

    global.addEventListener("storage", (event) => {
        if (event.key === STORAGE_KEY) {
            dispatchCatalogChange(readCatalogFromStorage());
        }
    });

    global.EmariohPackageCatalog = {
        STORAGE_KEY,
        PACKAGE_CHANGE_EVENT,
        GROUPS: cloneValue(GROUPS),
        STATUS_META: cloneValue(STATUS_META),
        getDefaultCatalog,
        getCatalog,
        getPackageById,
        getPackagesByGroup,
        getActivePackages,
        getPackagePricingTier,
        getPackageDownPaymentAmount,
        getPackageOptionSelections,
        getPackageOptionLabel,
        buildPackageSelectionValue,
        parsePackageSelectionValue,
        normalizePackage,
        saveCatalog,
        upsertPackage,
        deletePackage,
        resetCatalog,
        slugify
    };

    if (!global.localStorage?.getItem(STORAGE_KEY)) {
        writeCatalogToStorage(getDefaultCatalog());
    }
}(window));
