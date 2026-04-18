document.addEventListener("DOMContentLoaded", () => {
    const sidebarElement = document.getElementById("dashboardSidebar");
    const navLinks = document.querySelectorAll(".dashboard-nav .nav-link");
    const currentPath = window.location.pathname.split("/").pop() || "client-dashboard.php";
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

    initializeBookingDatePicker();
    initializeVenueOption();
    initializePackageSelector();
});

function initializeBookingDatePicker() {
    const picker = document.getElementById("eventDatePicker");
    const hiddenInput = document.getElementById("eventDate");
    const trigger = document.getElementById("eventDateTrigger");
    const valueLabel = document.getElementById("eventDateValue");
    const panel = document.getElementById("eventDateCalendar");
    const monthLabel = document.getElementById("eventDateMonthLabel");
    const grid = document.getElementById("eventDateGrid");

    if (!picker || !hiddenInput || !trigger || !valueLabel || !panel || !monthLabel || !grid) {
        return;
    }

    const today = startOfDay(new Date());
    const minMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    const blockedDates = new Set(
        (picker.dataset.bookedDates || "")
            .split(",")
            .map((value) => value.trim())
            .filter(Boolean)
    );

    let visibleMonth = new Date(minMonth);
    let selectedDate = hiddenInput.value || "";

    if (selectedDate) {
        const [year, month] = selectedDate.split("-").map(Number);
        visibleMonth = new Date(year, month - 1, 1);
    }

    updateTriggerLabel();
    renderCalendar();

    trigger.addEventListener("click", () => {
        if (panel.hidden) {
            openCalendar();
            return;
        }

        closeCalendar();
    });

    panel.addEventListener("click", (event) => {
        const navButton = event.target.closest("[data-calendar-nav]");
        if (navButton) {
            const direction = Number(navButton.dataset.calendarNav || "0");
            const nextMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() + direction, 1);

            if (nextMonth < minMonth) {
                return;
            }

            visibleMonth = nextMonth;
            renderCalendar();
            return;
        }

        const dayButton = event.target.closest("[data-calendar-date]");
        if (!dayButton || dayButton.disabled || dayButton.classList.contains("booking-date-picker__day--booked")) {
            return;
        }

        selectedDate = dayButton.dataset.calendarDate || "";
        hiddenInput.value = selectedDate;
        picker.classList.add("is-filled");
        updateTriggerLabel();
        renderCalendar();
        closeCalendar();
    });

    document.addEventListener("click", (event) => {
        if (!picker.contains(event.target)) {
            closeCalendar();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeCalendar();
        }
    });

    function openCalendar() {
        panel.hidden = false;
        picker.classList.add("is-open");
        trigger.setAttribute("aria-expanded", "true");
    }

    function closeCalendar() {
        panel.hidden = true;
        picker.classList.remove("is-open");
        trigger.setAttribute("aria-expanded", "false");
    }

    function updateTriggerLabel() {
        if (!selectedDate) {
            valueLabel.textContent = "Select date";
            picker.classList.remove("is-filled");
            return;
        }

        valueLabel.textContent = formatDisplayDate(selectedDate, "full");
        picker.classList.add("is-filled");
    }

    function renderCalendar() {
        monthLabel.textContent = new Intl.DateTimeFormat("en-PH", {
            month: "long",
            year: "numeric"
        }).format(visibleMonth);

        grid.innerHTML = "";

        const firstDay = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth(), 1);
        const daysInMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() + 1, 0).getDate();
        const startingWeekday = firstDay.getDay();
        const prevNav = panel.querySelector('[data-calendar-nav="-1"]');

        if (prevNav) {
            prevNav.disabled = visibleMonth.getFullYear() === minMonth.getFullYear()
                && visibleMonth.getMonth() === minMonth.getMonth();
        }

        for (let index = 0; index < startingWeekday; index += 1) {
            const blank = document.createElement("span");
            blank.className = "booking-date-picker__blank";
            blank.setAttribute("aria-hidden", "true");
            grid.appendChild(blank);
        }

        for (let day = 1; day <= daysInMonth; day += 1) {
            const currentDate = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth(), day);
            const dateValue = formatLocalDate(currentDate);
            const dayButton = document.createElement("button");
            const isPast = currentDate < today;
            const isBooked = blockedDates.has(dateValue);
            const isSelected = selectedDate === dateValue;
            const isToday = dateValue === formatLocalDate(today);

            dayButton.type = "button";
            dayButton.className = "booking-date-picker__day";
            dayButton.dataset.calendarDate = dateValue;
            dayButton.textContent = String(day);

            if (isPast) {
                dayButton.classList.add("booking-date-picker__day--disabled");
                dayButton.disabled = true;
            }

            if (isBooked) {
                dayButton.classList.add("booking-date-picker__day--booked");
                dayButton.dataset.tooltip = "Booked";
                dayButton.setAttribute("aria-label", `${formatDisplayDate(dateValue)} booked`);
                dayButton.setAttribute("aria-disabled", "true");
                dayButton.tabIndex = -1;
            }

            if (isSelected) {
                dayButton.classList.add("booking-date-picker__day--selected");
            }

            if (isToday) {
                dayButton.classList.add("booking-date-picker__day--today");
            }

            grid.appendChild(dayButton);
        }

        const totalCells = grid.childElementCount;
        const trailingBlanks = (7 - (totalCells % 7)) % 7;

        for (let index = 0; index < trailingBlanks; index += 1) {
            const blank = document.createElement("span");
            blank.className = "booking-date-picker__blank";
            blank.setAttribute("aria-hidden", "true");
            grid.appendChild(blank);
        }
    }
}

function initializeVenueOption() {
    const venueOptions = document.querySelectorAll('input[name="venueOption"]');
    const ownVenueField = document.getElementById("venue");
    const ownVenueWrapper = document.getElementById("ownVenueField");
    const emariohVenueWrapper = document.getElementById("emariohVenueField");
    const venueSetupHint = document.getElementById("venueSetupHint");

    if (!venueOptions.length || !ownVenueField || !ownVenueWrapper || !emariohVenueWrapper || !venueSetupHint) {
        return;
    }

    const syncVenueOption = () => {
        const selectedOption = document.querySelector('input[name="venueOption"]:checked')?.value || "own";
        const usingEmariohVenue = selectedOption === "emarioh";

        ownVenueWrapper.hidden = usingEmariohVenue;
        ownVenueField.disabled = usingEmariohVenue;
        ownVenueField.required = !usingEmariohVenue;

        if (usingEmariohVenue) {
            ownVenueField.value = "";
        }

        emariohVenueWrapper.hidden = !usingEmariohVenue;
        venueSetupHint.textContent = usingEmariohVenue
            ? "Use the Emarioh in-house venue, subject to availability."
            : "Add venue name and city.";
    };

    venueOptions.forEach((option) => {
        option.addEventListener("change", syncVenueOption);
    });

    syncVenueOption();
}

function initializePackageSelector() {
    const categorySelect = document.getElementById("packageCategory");
    const optionSelect = document.getElementById("packageOption");
    const summaryMeta = document.getElementById("packageSummaryMeta");
    const summaryBadge = document.getElementById("packageSummaryBadge");
    const summaryTitle = document.getElementById("packageSummaryTitle");
    const summaryDescription = document.getElementById("packageSummaryDescription");
    const summaryTags = document.getElementById("packageSummaryTags");
    const eventTypeSelect = document.getElementById("eventType");

    if (!categorySelect || !optionSelect || !summaryMeta || !summaryBadge || !summaryTitle || !summaryDescription || !summaryTags) {
        return;
    }

    const packageCatalog = {
        "per-head": [
            {
                value: "PHP 350/head",
                label: "PHP 350 / head",
                meta: "Per-head",
                badge: "50 pax minimum",
                description: "Rice, chicken, pork, pasta, vegetables, beverage",
                tags: ["Min. 50 pax", "Buffet-ready"]
            },
            {
                value: "PHP 400/head",
                label: "PHP 400 / head",
                meta: "Per-head",
                badge: "50 pax minimum",
                description: "Rice, chicken, pork, fish, vegetables, beverage",
                tags: ["Fish included", "Min. 50 pax"]
            },
            {
                value: "PHP 500/head",
                label: "PHP 500 / head",
                meta: "Per-head",
                badge: "50 pax minimum",
                description: "Rice, soup, chicken, pork, fish, vegetables, beverage",
                tags: ["Soup included", "Min. 50 pax"]
            },
            {
                value: "PHP 600/head",
                label: "PHP 600 / head",
                meta: "Per-head",
                badge: "Premium per-head",
                description: "Rice, soup, chicken, pork, fish, pasta, vegetables, dessert",
                tags: ["Dessert included", "Premium per-head"]
            }
        ],
        celebration: [
            {
                value: "50 pax celebration package",
                label: "50 pax - PHP 50,000",
                meta: "Celebration",
                badge: "Wedding and birthday",
                description: "3 mains, dessert, buffet setup, cake, styling",
                tags: ["Cake included", "Styled setup"]
            },
            {
                value: "100 pax celebration package",
                label: "100 pax - PHP 85,000",
                meta: "Celebration",
                badge: "Wedding and birthday",
                description: "Buffet setup, cake, themed styling, service support",
                tags: ["Tiffany chairs", "Service support"]
            },
            {
                value: "150 pax celebration package",
                label: "150 pax - PHP 120,000",
                meta: "Celebration",
                badge: "Wedding and birthday",
                description: "Dining setup, cake, centerpieces, full styling",
                tags: ["Large event setup", "Centerpieces"]
            }
        ]
    };

    const eventTypeCategoryMap = {
        Wedding: "celebration",
        Birthday: "celebration",
        "Corporate Event": "per-head",
        Debut: "celebration",
        Anniversary: "celebration",
        "Social Gathering / Reunion": "per-head"
    };

    const renderTags = (tags) => {
        summaryTags.innerHTML = "";

        tags.forEach((tag) => {
            const chip = document.createElement("span");
            chip.className = "booking-package-summary__tag";
            chip.textContent = tag;
            summaryTags.appendChild(chip);
        });
    };

    const updateSummary = () => {
        const category = categorySelect.value;
        const selectedPackage = packageCatalog[category].find((item) => item.value === optionSelect.value) || packageCatalog[category][0];

        if (!selectedPackage) {
            return;
        }

        summaryMeta.textContent = selectedPackage.meta;
        summaryBadge.textContent = selectedPackage.badge;
        summaryTitle.textContent = selectedPackage.label;
        summaryDescription.textContent = selectedPackage.description;
        renderTags(selectedPackage.tags);
    };

    const populateOptions = (preserveValue = false) => {
        const category = categorySelect.value;
        const options = packageCatalog[category] || [];
        const previousValue = preserveValue ? optionSelect.value : "";

        optionSelect.innerHTML = "";

        options.forEach((item) => {
            const option = document.createElement("option");
            option.value = item.value;
            option.textContent = item.label;
            optionSelect.appendChild(option);
        });

        const hasPreviousValue = previousValue && options.some((item) => item.value === previousValue);
        optionSelect.value = hasPreviousValue ? previousValue : options[0]?.value || "";
        updateSummary();
    };

    categorySelect.addEventListener("change", () => {
        populateOptions();
    });

    optionSelect.addEventListener("change", updateSummary);

    if (eventTypeSelect) {
        const syncCategoryFromEvent = () => {
            const nextCategory = eventTypeCategoryMap[eventTypeSelect.value];

            if (!nextCategory || categorySelect.value === nextCategory) {
                return;
            }

            categorySelect.value = nextCategory;
            populateOptions();
        };

        eventTypeSelect.addEventListener("change", syncCategoryFromEvent);
        syncCategoryFromEvent();
    }

    populateOptions(true);
}

function startOfDay(date) {
    const normalized = new Date(date);
    normalized.setHours(0, 0, 0, 0);
    return normalized;
}

function formatLocalDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");

    return `${year}-${month}-${day}`;
}

function formatDisplayDate(dateValue, format = "full") {
    const [year, month, day] = dateValue.split("-").map(Number);
    const options = format === "compact"
        ? { month: "short", day: "numeric" }
        : { month: "long", day: "numeric", year: "numeric" };

    return new Intl.DateTimeFormat("en-PH", options).format(new Date(year, month - 1, day));
}
