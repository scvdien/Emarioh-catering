(function initializePaymentSettingsStore(global) {
    const STORAGE_KEY = "emariohPaymentSettings.v1";
    const SETTINGS_CHANGE_EVENT = "emarioh:payment-settings-changed";
    const BALANCE_DUE_RULES = [
        "3 days before event",
        "5 days before event",
        "On the event day"
    ];
    const DEFAULT_SETTINGS = {
        paymentGateway: "PayMongo",
        activeMethod: "PayMongo QRPh",
        acceptedWalletsLabel: "Any QRPh-supported e-wallet or banking app",
        allowFullPayment: true,
        balanceDueRule: BALANCE_DUE_RULES[0],
        receiptRequirement: "receipt_required",
        confirmationRule: "verified_down_payment",
        supportMobile: "0917 800 4401",
        instructionText: "Clients can pay through the active PayMongo QRPh checkout using any QRPh-supported e-wallet or banking app. After payment, they can return to Billing and refresh the latest PayMongo status."
    };

    function cloneValue(value) {
        return JSON.parse(JSON.stringify(value));
    }

    function normalizeBoolean(value, fallbackValue) {
        if (typeof value === "boolean") {
            return value;
        }

        return fallbackValue;
    }

    function normalizeEnum(value, allowedValues, fallbackValue) {
        return allowedValues.includes(value) ? value : fallbackValue;
    }

    function normalizeText(value, fallbackValue) {
        const normalizedValue = String(value || "").trim();
        return normalizedValue || fallbackValue;
    }

    function normalizeSettings(rawSettings) {
        return {
            paymentGateway: "PayMongo",
            activeMethod: "PayMongo QRPh",
            acceptedWalletsLabel: normalizeText(rawSettings?.acceptedWalletsLabel, DEFAULT_SETTINGS.acceptedWalletsLabel),
            allowFullPayment: normalizeBoolean(rawSettings?.allowFullPayment, DEFAULT_SETTINGS.allowFullPayment),
            balanceDueRule: normalizeEnum(rawSettings?.balanceDueRule, BALANCE_DUE_RULES, DEFAULT_SETTINGS.balanceDueRule),
            receiptRequirement: normalizeEnum(
                rawSettings?.receiptRequirement,
                ["receipt_required", "any_proof"],
                DEFAULT_SETTINGS.receiptRequirement
            ),
            confirmationRule: normalizeEnum(
                rawSettings?.confirmationRule,
                ["verified_down_payment", "manual_review"],
                DEFAULT_SETTINGS.confirmationRule
            ),
            supportMobile: normalizeText(rawSettings?.supportMobile, DEFAULT_SETTINGS.supportMobile),
            instructionText: normalizeText(rawSettings?.instructionText, DEFAULT_SETTINGS.instructionText)
        };
    }

    function readSettingsFromStorage() {
        try {
            const storedValue = global.localStorage?.getItem(STORAGE_KEY);

            if (!storedValue) {
                return normalizeSettings(DEFAULT_SETTINGS);
            }

            return normalizeSettings(JSON.parse(storedValue));
        } catch (error) {
            return normalizeSettings(DEFAULT_SETTINGS);
        }
    }

    function writeSettingsToStorage(settings) {
        const normalizedSettings = normalizeSettings(settings);

        try {
            global.localStorage?.setItem(STORAGE_KEY, JSON.stringify(normalizedSettings));
        } catch (error) {
            return normalizedSettings;
        }

        return normalizedSettings;
    }

    function dispatchSettingsChange(settings) {
        global.dispatchEvent(new CustomEvent(SETTINGS_CHANGE_EVENT, {
            detail: {
                settings: cloneValue(settings)
            }
        }));
    }

    function getSettings() {
        return cloneValue(readSettingsFromStorage());
    }

    function saveSettings(nextSettings, options = {}) {
        const normalizedSettings = writeSettingsToStorage(nextSettings);

        if (options.emit !== false) {
            dispatchSettingsChange(normalizedSettings);
        }

        return cloneValue(normalizedSettings);
    }

    global.addEventListener("storage", (event) => {
        if (event.key === STORAGE_KEY) {
            dispatchSettingsChange(readSettingsFromStorage());
        }
    });

    global.EmariohPaymentSettings = {
        STORAGE_KEY,
        SETTINGS_CHANGE_EVENT,
        BALANCE_DUE_RULES: cloneValue(BALANCE_DUE_RULES),
        DEFAULT_SETTINGS: cloneValue(DEFAULT_SETTINGS),
        getSettings,
        normalizeSettings,
        saveSettings
    };

    if (!global.localStorage?.getItem(STORAGE_KEY)) {
        writeSettingsToStorage(DEFAULT_SETTINGS);
    }
}(window));
