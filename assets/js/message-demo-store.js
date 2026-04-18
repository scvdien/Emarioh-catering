(function () {
    const STORAGE_KEY = "emarioh_admin_messages_demo_v1";

    const FALLBACK_MESSAGES = [
        {
            id: "MSG-20260406-003",
            name: "Angela Ramos",
            email: "angela.ramos@email.com",
            category: "Package Inquiry",
            source: "Public Website",
            status: "unread",
            submittedAt: "2026-04-06T10:15:00+08:00",
            readAt: "",
            message: "Good day. We are planning a debut for around 120 guests in late May. Can you recommend a package with dessert station, simple floral styling, and buffet setup?"
        },
        {
            id: "MSG-20260405-002",
            name: "Jerome Velasco",
            email: "jerome.velasco@email.com",
            category: "Booking Inquiry",
            source: "Public Website",
            status: "read",
            submittedAt: "2026-04-05T14:40:00+08:00",
            readAt: "2026-04-05T15:05:00+08:00",
            message: "Hello, I want to ask if your wedding package is still available for April 18. We are expecting 150 guests and we need on-site service in Plaridel."
        },
        {
            id: "MSG-20260404-001",
            name: "Liza Navarro",
            email: "liza.navarro@email.com",
            category: "Event Inquiry",
            source: "Public Website",
            status: "read",
            submittedAt: "2026-04-04T09:20:00+08:00",
            readAt: "2026-04-04T10:00:00+08:00",
            message: "Hi, asking for your available celebration packages for a family anniversary dinner. We are looking at 80 to 100 guests and want a garden-style setup."
        }
    ];

    function safeParse(value) {
        try {
            return JSON.parse(value);
        } catch (error) {
            return null;
        }
    }

    function readStoredMessages() {
        try {
            const rawValue = window.localStorage.getItem(STORAGE_KEY);
            const parsedValue = safeParse(rawValue);
            return Array.isArray(parsedValue) ? parsedValue : null;
        } catch (error) {
            return null;
        }
    }

    function saveMessages(messages) {
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(messages));
        } catch (error) {
            // Ignore storage failures in demo mode.
        }
    }

    function inferCategory(messageText) {
        const text = String(messageText || "").toLowerCase();

        if (/(package|menu|buffet|pax|dessert|food)/.test(text)) {
            return "Package Inquiry";
        }

        if (/(book|booking|reserve|reservation|date|available)/.test(text)) {
            return "Booking Inquiry";
        }

        return "Event Inquiry";
    }

    function normalizeStatus(status) {
        return status === "new" || status === "unread" ? "unread" : "read";
    }

    function normalizeMessage(message, index = 0) {
        const submittedAt = message.submittedAt || new Date().toISOString();

        return {
            id: message.id || `MSG-${Date.now()}-${index + 1}`,
            name: (message.name || "Guest Inquiry").trim(),
            email: (message.email || "not-provided@emarioh.local").trim(),
            category: message.category || inferCategory(message.message || ""),
            source: message.source || "Public Website",
            status: normalizeStatus(message.status || "unread"),
            submittedAt,
            readAt: String(message.readAt || "").trim(),
            message: (message.message || "").trim()
        };
    }

    function sortMessages(messages) {
        return [...messages].sort((left, right) => {
            return new Date(right.submittedAt).getTime() - new Date(left.submittedAt).getTime();
        });
    }

    function getMessages() {
        const storedMessages = readStoredMessages();
        const sourceMessages = storedMessages && storedMessages.length ? storedMessages : FALLBACK_MESSAGES;
        return sortMessages(sourceMessages.map(normalizeMessage));
    }

    function createReference(dateValue, index) {
        const year = dateValue.getFullYear();
        const month = String(dateValue.getMonth() + 1).padStart(2, "0");
        const day = String(dateValue.getDate()).padStart(2, "0");
        return `MSG-${year}${month}${day}-${String(index).padStart(3, "0")}`;
    }

    function addMessage(payload) {
        const currentMessages = getMessages();
        const now = new Date();
        const nextMessage = normalizeMessage({
            id: createReference(now, currentMessages.length + 1),
            name: payload.name,
            email: payload.email,
            message: payload.message,
            category: payload.category || inferCategory(payload.message),
            source: payload.source || "Public Website",
            status: "unread",
            submittedAt: now.toISOString(),
            readAt: ""
        });

        const updatedMessages = sortMessages([nextMessage, ...currentMessages]);
        saveMessages(updatedMessages);
        return nextMessage;
    }

    function updateMessage(id, updates) {
        const updatedMessages = getMessages().map((message) => {
            if (message.id !== id) {
                return message;
            }

            return normalizeMessage({
                ...message,
                ...updates
            });
        });

        saveMessages(updatedMessages);
        return updatedMessages;
    }

    window.EmariohMessageDemoStore = {
        STORAGE_KEY,
        getMessages,
        addMessage,
        updateMessage,
        inferCategory
    };
})();
