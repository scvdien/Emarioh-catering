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
    initializeLiveSummary();
    initializeBookingSubmission();
    restoreBookingDraft();
    initializeBookingDraftAutosave();
    initializeClearFieldsAction();
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
        hiddenInput.dispatchEvent(new Event("change", { bubbles: true }));
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
                dayButton.dataset.tooltip = "Reserved";
                dayButton.setAttribute("aria-label", `${formatDisplayDate(dateValue)} reserved`);
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

    window.setBookingSelectedDate = (dateValue) => {
        if (!dateValue) {
            selectedDate = "";
            hiddenInput.value = "";
            visibleMonth = new Date(minMonth);
            picker.classList.remove("is-filled");
            updateTriggerLabel();
            renderCalendar();
            hiddenInput.dispatchEvent(new Event("change", { bubbles: true }));
            return;
        }
        selectedDate = dateValue;
        hiddenInput.value = dateValue;
        const [year, month] = dateValue.split("-").map(Number);
        visibleMonth = new Date(year, month - 1, 1);
        picker.classList.add("is-filled");
        updateTriggerLabel();
        renderCalendar();
        hiddenInput.dispatchEvent(new Event("change", { bubbles: true }));
    };
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

const DISH_CATALOG = {
    noodles: {
        id: "noodles",
        name: "Noodles / Pasta",
        icon: "🍝",
        badge: "Choose 1",
        options: [
            "Creamy Carbonara",
            "Baked Macaroni",
            "Filipino-Style Spaghetti",
            "Pancit Canton Guisado",
            "Sotanghon Guisado"
        ]
    },
    vegetables: {
        id: "vegetables",
        name: "Vegetables / Salad",
        icon: "🥗",
        badge: "Choose 1",
        options: [
            "Buttered Mixed Vegetables",
            "Special Chopsuey",
            "Creamy Potato Salad",
            "Caesar Salad with Croutons"
        ]
    },
    chicken: {
        id: "chicken",
        name: "Chicken Dish",
        icon: "🍗",
        badge: "Choose 1",
        options: [
            "Buttered Chicken",
            "Chicken Teriyaki",
            "Chicken Cordon Bleu",
            "Fried Chicken with Gravy",
            "Chicken Pastel"
        ]
    },
    pork: {
        id: "pork",
        name: "Pork Dish",
        icon: "🥩",
        badge: "Choose 1",
        options: [
            "Pork Hamonado",
            "Sweet & Sour Pork",
            "Roast Pork with Mushroom Gravy",
            "Pork Menudo",
            "Pork Spareribs BBQ"
        ]
    },
    fish: {
        id: "fish",
        name: "Fish Dish",
        icon: "🐟",
        badge: "Choose 1",
        options: [
            "Sweet & Sour Fish Fillet",
            "Fish Fillet with Tartar Sauce",
            "Fish Fillet in Lemon Butter Sauce"
        ]
    },
    dessert: {
        id: "dessert",
        name: "Dessert",
        icon: "🍮",
        badge: "Choose 1",
        options: [
            "Buko Pandan Salad",
            "Creamy Fruit Salad",
            "Mango Tapioca Float",
            "Coffee Jelly"
        ]
    },
    beverage: {
        id: "beverage",
        name: "Beverage",
        icon: "🍹",
        badge: "Choose 1",
        options: [
            "House Red Iced Tea",
            "Blue Lemonade",
            "Cucumber Lemonade",
            "Four Seasons Punch"
        ]
    },
    beverage1: {
        id: "beverage1",
        name: "Beverage 1",
        icon: "🍹",
        badge: "Choice 1",
        options: [
            "House Red Iced Tea",
            "Blue Lemonade",
            "Cucumber Lemonade",
            "Four Seasons Punch"
        ]
    },
    beverage2: {
        id: "beverage2",
        name: "Beverage 2",
        icon: "🍹",
        badge: "Choice 2",
        options: [
            "Blue Lemonade",
            "House Red Iced Tea",
            "Cucumber Lemonade",
            "Four Seasons Punch"
        ]
    },
    beverages_multi: {
        id: "beverages_multi",
        name: "Beverages",
        icon: "🍹",
        badge: "Choose 2",
        type: "multiselect",
        maxSelect: 2,
        options: [
            "House Red Iced Tea",
            "Cucumber Lemonade",
            "Blue Lemonade",
            "Four Seasons Punch"
        ]
    }
};

const selectedDishChoices = {};

function getActiveDishCourses(pkg) {
    if (!pkg) {
        return [];
    }

    const inclusions = Array.isArray(pkg.inclusions) ? pkg.inclusions : [];
    const inclusionsJoined = inclusions.join(" | ").toLowerCase();
    const courses = [];

    if (inclusionsJoined.includes("chicken") || inclusionsJoined.includes("main dish")) {
        courses.push(DISH_CATALOG.chicken);
    }

    if (inclusionsJoined.includes("pork") || inclusionsJoined.includes("main dish")) {
        courses.push(DISH_CATALOG.pork);
    }

    if (inclusionsJoined.includes("fish")) {
        courses.push(DISH_CATALOG.fish);
    }

    if (inclusionsJoined.includes("noodle") || inclusionsJoined.includes("pasta")) {
        courses.push(DISH_CATALOG.noodles);
    }

    if (inclusionsJoined.includes("vegetable") || inclusionsJoined.includes("salad")) {
        courses.push(DISH_CATALOG.vegetables);
    }

    if (inclusionsJoined.includes("dessert")) {
        courses.push(DISH_CATALOG.dessert);
    }

    if (inclusionsJoined.includes("2 kinds of beverage") || inclusionsJoined.includes("two beverage")) {
        courses.push(DISH_CATALOG.beverages_multi);
    } else if (inclusionsJoined.includes("beverage") || inclusionsJoined.includes("drink")) {
        courses.push(DISH_CATALOG.beverage);
    }

    if (courses.length === 0) {
        courses.push(DISH_CATALOG.chicken, DISH_CATALOG.pork, DISH_CATALOG.vegetables, DISH_CATALOG.beverage);
    }

    return courses;
}

function getSelectedDishesSummaryText(pkg) {
    const activeCourses = getActiveDishCourses(pkg);
    if (!activeCourses.length) {
        return "";
    }

    const lines = ["🍽️ Selected Menu Choices:"];
    activeCourses.forEach((course) => {
        if (course.type === "multiselect") {
            const choices = Array.isArray(selectedDishChoices[course.id]) ? selectedDishChoices[course.id] : [];
            const displayChoice = choices.length > 0 ? choices.join(", ") : "None selected";
            lines.push(`• ${course.name}: ${displayChoice}`);
        } else {
            const choice = selectedDishChoices[course.id] || course.options[0];
            lines.push(`• ${course.name}: ${choice}`);
        }
    });

    return lines.join("\n");
}

function formatInclusionWithChoice(itemText) {
    const lower = itemText.toLowerCase();

    if (lower.includes("3 main dish")) {
        const parts = [];
        if (selectedDishChoices.chicken) parts.push(`Chicken: <strong>${selectedDishChoices.chicken}</strong>`);
        if (selectedDishChoices.pork) parts.push(`Pork: <strong>${selectedDishChoices.pork}</strong>`);
        if (selectedDishChoices.fish) parts.push(`Fish: <strong>${selectedDishChoices.fish}</strong>`);
        if (parts.length) {
            return `3 Main Dishes (${parts.join(" • ")})`;
        }
    }

    if (lower.includes("vegetables, soup, dessert")) {
        const veg = selectedDishChoices.vegetables ? `Vegetables: <strong>${selectedDishChoices.vegetables}</strong>` : "Vegetables";
        const des = selectedDishChoices.dessert ? `Dessert: <strong>${selectedDishChoices.dessert}</strong>` : "Dessert";
        return `${veg}, Soup, ${des}`;
    }

    if (lower.includes("one (1) round choice of beverage") || lower.includes("round choice of beverage")) {
        const multi = Array.isArray(selectedDishChoices.beverages_multi) ? selectedDishChoices.beverages_multi : [];
        const bev = selectedDishChoices.beverage || selectedDishChoices.beverage1 || multi[0] || "Choice of Beverage";
        return `Beverage: <strong>${bev}</strong> (Unlimited Rice, Water & Ice)`;
    }

    if (lower.includes("2 kinds of beverage") || lower.includes("two beverage")) {
        const multi = Array.isArray(selectedDishChoices.beverages_multi) ? selectedDishChoices.beverages_multi : [];
        if (multi.length >= 2) {
            return `2 Beverages: <strong>${multi[0]}</strong> & <strong>${multi[1]}</strong>`;
        }
        if (multi.length === 1) {
            return `2 Beverages: <strong>${multi[0]}</strong> & <span class="text-muted">(1 more to select)</span>`;
        }
        return `2 Kinds of Beverage <span class="text-muted">(Please select 2)</span>`;
    }

    if (lower.includes("noodle") || lower.includes("pasta")) {
        if (selectedDishChoices.noodles) {
            return `Noodles / Pasta: <strong>${selectedDishChoices.noodles}</strong>`;
        }
    }

    if (lower.includes("vegetable") || lower.includes("salad")) {
        if (selectedDishChoices.vegetables) {
            return `Vegetables / Salad: <strong>${selectedDishChoices.vegetables}</strong>`;
        }
    }

    if (lower === "chicken" || lower.startsWith("chicken (") || lower.startsWith("chicken dish")) {
        if (selectedDishChoices.chicken) {
            return `Chicken: <strong>${selectedDishChoices.chicken}</strong>`;
        }
    }

    if (lower === "pork" || lower.startsWith("pork (") || lower.startsWith("pork dish")) {
        if (selectedDishChoices.pork) {
            return `Pork: <strong>${selectedDishChoices.pork}</strong>`;
        }
    }

    if (lower === "fish" || lower.startsWith("fish (") || lower.startsWith("fish dish")) {
        if (selectedDishChoices.fish) {
            return `Fish: <strong>${selectedDishChoices.fish}</strong>`;
        }
    }

    if (lower === "dessert" || lower.startsWith("dessert (")) {
        if (selectedDishChoices.dessert) {
            return `Dessert: <strong>${selectedDishChoices.dessert}</strong>`;
        }
    }

    if (lower === "beverage" || lower.startsWith("beverage (")) {
        if (selectedDishChoices.beverage) {
            return `Beverage: <strong>${selectedDishChoices.beverage}</strong>`;
        }
    }

    return itemText;
}

function syncDishSelectionsToModals(pkg) {
    if (!pkg) {
        return;
    }

    const summaryInclusions = document.getElementById("packageSummaryInclusions");
    if (!summaryInclusions) return;

    const inclusions = Array.isArray(pkg.inclusions) ? pkg.inclusions : [];
    summaryInclusions.innerHTML = "";

    inclusions.forEach((itemText) => {
        const formattedHtml = formatInclusionWithChoice(itemText);
        const isCustomized = formattedHtml !== itemText;

        const li = document.createElement("li");
        li.className = `booking-summary-menu-item${isCustomized ? " booking-summary-menu-item--chosen" : ""}`;

        const icon = document.createElement("span");
        icon.className = "booking-summary-menu-icon";
        icon.innerHTML = isCustomized ? '<i class="bi bi-check-circle-fill"></i>' : '<i class="bi bi-check2"></i>';

        const textWrap = document.createElement("div");
        textWrap.className = "booking-summary-menu-text";
        textWrap.innerHTML = formattedHtml;

        li.appendChild(icon);
        li.appendChild(textWrap);
        summaryInclusions.appendChild(li);
    });
}

function renderDishSelectors(pkg) {
    const selectorCard = document.getElementById("bookingDishSelectorCard");
    const dishGrid = document.getElementById("bookingDishGrid");
    const countBadge = document.getElementById("dishSelectionCountBadge");

    if (!selectorCard || !dishGrid) {
        return;
    }

    const activeCourses = getActiveDishCourses(pkg);

    if (activeCourses.length === 0) {
        selectorCard.hidden = true;
        dishGrid.innerHTML = "";
        return;
    }

    selectorCard.hidden = false;
    if (countBadge) {
        countBadge.textContent = `${activeCourses.length} Choices`;
    }

    dishGrid.innerHTML = "";

    activeCourses.forEach((course) => {
        const col = document.createElement("div");
        col.className = "col-md-6 col-lg-4";

        const itemBox = document.createElement("div");
        itemBox.className = "booking-dish-item";

        const headerDiv = document.createElement("div");
        headerDiv.className = "d-flex align-items-center justify-content-between mb-2";

        const label = document.createElement("label");
        label.htmlFor = `dishSelect_${course.id}`;
        label.className = "form-label mb-0 text-dark fw-bold";
        label.innerHTML = `<span class="me-1">${course.icon}</span> ${course.name}`;

        const badge = document.createElement("span");
        badge.className = "badge bg-light text-secondary border small";
        badge.textContent = course.badge;

        headerDiv.appendChild(label);
        headerDiv.appendChild(badge);
        itemBox.appendChild(headerDiv);

        if (course.type === "multiselect") {
            const maxSelect = course.maxSelect || 2;
            let currentChoices = Array.isArray(selectedDishChoices[course.id])
                ? [...selectedDishChoices[course.id]]
                : [];

            selectedDishChoices.beverage1 = currentChoices[0] || "";
            selectedDishChoices.beverage2 = currentChoices[1] || "";

            const dropdownWrap = document.createElement("div");
            dropdownWrap.className = "dropdown position-relative";

            const dropdownBtn = document.createElement("button");
            dropdownBtn.type = "button";
            dropdownBtn.id = `dishSelect_${course.id}`;
            dropdownBtn.className = "booking-multi-select-btn";
            dropdownBtn.setAttribute("data-bs-toggle", "dropdown");
            dropdownBtn.setAttribute("data-bs-auto-close", "outside");
            dropdownBtn.setAttribute("aria-expanded", "false");

            const btnText = document.createElement("span");
            btnText.className = "text-truncate d-inline-block";
            dropdownBtn.appendChild(btnText);

            const dropdownMenu = document.createElement("div");
            dropdownMenu.className = "dropdown-menu booking-multi-select-menu";

            const updateMultiSelectState = () => {
                const checkedBoxes = Array.from(dropdownMenu.querySelectorAll(".booking-multi-checkbox:checked"));
                const checkedVals = checkedBoxes.map((cb) => cb.value);

                selectedDishChoices[course.id] = checkedVals;
                selectedDishChoices.beverage1 = checkedVals[0] || "";
                selectedDishChoices.beverage2 = checkedVals[1] || "";

                if (checkedVals.length >= maxSelect) {
                    btnText.textContent = checkedVals.join(", ");
                    btnText.classList.remove("text-muted");
                    badge.className = "badge bg-primary-subtle text-primary border border-primary-subtle small";
                    badge.textContent = `${checkedVals.length} Selected`;
                } else if (checkedVals.length > 0) {
                    btnText.textContent = `${checkedVals.join(", ")} (${maxSelect - checkedVals.length} more)`;
                    btnText.classList.remove("text-muted");
                    badge.className = "badge bg-warning-subtle text-dark border small";
                    badge.textContent = `${checkedVals.length} of ${maxSelect}`;
                } else {
                    btnText.textContent = `-- Select ${maxSelect} Beverages --`;
                    btnText.classList.add("text-muted");
                    badge.className = "badge bg-light text-secondary border small";
                    badge.textContent = `Choose ${maxSelect}`;
                }

                // Disable unchecked options once max is reached
                const allBoxes = dropdownMenu.querySelectorAll(".booking-multi-checkbox");
                allBoxes.forEach((cb) => {
                    const row = cb.closest(".booking-multi-select-option");
                    if (!cb.checked && checkedVals.length >= maxSelect) {
                        cb.disabled = true;
                        if (row) row.classList.add("is-disabled");
                    } else {
                        cb.disabled = false;
                        if (row) row.classList.remove("is-disabled");
                    }
                });

                syncDishSelectionsToModals(pkg);
            };

            course.options.forEach((opt, idx) => {
                const row = document.createElement("label");
                row.className = "booking-multi-select-option";
                row.htmlFor = `multi_cb_${course.id}_${idx}`;

                const cb = document.createElement("input");
                cb.type = "checkbox";
                cb.className = "booking-multi-checkbox";
                cb.value = opt;
                cb.id = `multi_cb_${course.id}_${idx}`;
                cb.checked = currentChoices.includes(opt);

                const span = document.createElement("span");
                span.textContent = opt;

                cb.addEventListener("change", () => {
                    updateMultiSelectState();
                });

                row.appendChild(cb);
                row.appendChild(span);
                dropdownMenu.appendChild(row);
            });

            const hint = document.createElement("div");
            hint.className = "booking-multi-select-hint";
            hint.innerHTML = `<i class="bi bi-info-circle me-1"></i> Choose up to ${maxSelect} beverages`;
            dropdownMenu.appendChild(hint);

            dropdownWrap.appendChild(dropdownBtn);
            dropdownWrap.appendChild(dropdownMenu);
            itemBox.appendChild(dropdownWrap);

            updateMultiSelectState();
        } else {
            if (!selectedDishChoices[course.id] || !course.options.includes(selectedDishChoices[course.id])) {
                selectedDishChoices[course.id] = course.options[0];
            }

            const select = document.createElement("select");
            select.id = `dishSelect_${course.id}`;
            select.name = `dish_${course.id}`;
            select.className = "form-select booking-dish-select";
            select.dataset.courseId = course.id;

            course.options.forEach((opt) => {
                const optElem = document.createElement("option");
                optElem.value = opt;
                optElem.textContent = opt;
                if (opt === selectedDishChoices[course.id]) {
                    optElem.selected = true;
                }
                select.appendChild(optElem);
            });

            select.addEventListener("change", (e) => {
                selectedDishChoices[course.id] = e.target.value;
                syncDishSelectionsToModals(pkg);
            });

            itemBox.appendChild(select);
        }

        col.appendChild(itemBox);
        dishGrid.appendChild(col);
    });

    syncDishSelectionsToModals(pkg);
}

function initializePackageSelector() {
    const categorySelect = document.getElementById("packageCategory");
    const optionSelect = document.getElementById("packageOption");
    const summaryMeta = document.getElementById("packageSummaryMeta");
    const summaryBadge = document.getElementById("packageSummaryBadge");
    const summaryTitle = document.getElementById("packageSummaryTitle");
    const summaryDescription = document.getElementById("packageSummaryDescription");
    const summaryTags = document.getElementById("packageSummaryTags");
    const summaryInclusions = document.getElementById("packageSummaryInclusions");
    const inclusionsCount = document.getElementById("packageInclusionsCount");
    const guestCountInput = document.getElementById("guestCount");
    const guestCountHint = document.getElementById("guestCountHint");
    const eventTypeSelect = document.getElementById("eventType");
    const catalogApi = window.EmariohPackageCatalog;
    const hasSummary = Boolean(summaryMeta && summaryBadge && summaryTitle && summaryDescription && summaryTags);

    const validateGuestCount = () => {
        if (!guestCountInput) {
            return true;
        }
        const val = Number.parseInt(guestCountInput.value, 10);
        if (!guestCountInput.value) {
            if (guestCountHint) {
                guestCountHint.textContent = "Minimum of 50 guests required.";
                guestCountHint.className = "booking-field__hint";
            }
            return false;
        }
        if (Number.isNaN(val) || val < 50) {
            if (guestCountHint) {
                guestCountHint.textContent = "⚠️ Minimum of 50 guests is required for catering packages.";
                guestCountHint.className = "booking-guest-count-feedback";
            }
            return false;
        }
        if (guestCountHint) {
            guestCountHint.textContent = "Minimum of 50 guests required.";
            guestCountHint.className = "booking-field__hint";
        }
        return true;
    };

    if (guestCountInput) {
        guestCountInput.addEventListener("input", validateGuestCount);
        guestCountInput.addEventListener("change", validateGuestCount);
        guestCountInput.addEventListener("invalid", (event) => {
            event.preventDefault();
        });
        guestCountInput.addEventListener("keydown", (event) => {
            if (event.key === "Enter") {
                event.preventDefault();
                guestCountInput.blur();
            }
        });
    }

    document.querySelectorAll(".booking-form").forEach((form) => {
        form.addEventListener("submit", (event) => {
            if (!form.classList.contains("booking-form--details")) {
                event.preventDefault();
            }
        });
    });

    if (!catalogApi || !categorySelect || !optionSelect) {
        return;
    }

    const eventTypeCategoryMap = {
        Wedding: "celebration",
        Birthday: "celebration",
        "Corporate Event": "per-head",
        Debut: "celebration",
        Anniversary: "celebration",
        "Social Gathering / Reunion": "per-head"
    };

    const renderTags = (tags) => {
        if (!summaryTags) {
            return;
        }

        summaryTags.innerHTML = "";
        summaryTags.classList.add("d-none");
        summaryTags.hidden = true;
    };

    const setUnavailableState = (message) => {
        categorySelect.disabled = true;
        optionSelect.disabled = true;
        optionSelect.innerHTML = "";

        if (hasSummary) {
            summaryMeta.textContent = "Packages unavailable";
            summaryBadge.textContent = "Please check again later";
            summaryTitle.textContent = "No active packages";
            summaryDescription.textContent = message;
            renderTags(["Admin update needed"]);
        }

        if (summaryInclusions) {
            summaryInclusions.innerHTML = '<li class="booking-summary-menu-item text-muted">No package details available.</li>';
        }
        if (inclusionsCount) {
            inclusionsCount.textContent = "0 Items";
        }
        const selectorCard = document.getElementById("bookingDishSelectorCard");
        const dishGrid = document.getElementById("bookingDishGrid");
        if (selectorCard) {
            selectorCard.hidden = true;
        }
        if (dishGrid) {
            dishGrid.innerHTML = "";
        }
    };

    const getAvailableGroups = () => Object.keys(catalogApi.GROUPS).filter((group) => catalogApi.getActivePackages(group).length > 0);

    const getSelectedPackageState = () => {
        const selection = catalogApi.parsePackageSelectionValue(optionSelect.value);
        const selectedPackage = catalogApi.getPackageById(selection.packageId || optionSelect.value);
        const selectedTier = catalogApi.getPackagePricingTier(selectedPackage, selection.tierLabel);

        return {
            selectedPackage,
            selectedTier
        };
    };

    const syncCategoryOptions = () => {
        const availableGroups = getAvailableGroups();

        if (!availableGroups.length) {
            setUnavailableState("No active package offers are currently available for booking.");
            return false;
        }

        const previousGroup = categorySelect.value;
        categorySelect.innerHTML = availableGroups.map((group) => {
            const groupMeta = catalogApi.GROUPS[group];
            return `<option value="${group}">${groupMeta.label}</option>`;
        }).join("");
        categorySelect.disabled = false;
        optionSelect.disabled = false;
        categorySelect.value = availableGroups.includes(previousGroup) ? previousGroup : availableGroups[0];
        return true;
    };

    const updateSummary = () => {
        const selectorCard = document.getElementById("bookingDishSelectorCard");
        const dishGrid = document.getElementById("bookingDishGrid");

        if (!optionSelect.value) {
            if (selectorCard) {
                selectorCard.hidden = true;
            }
            if (dishGrid) {
                dishGrid.innerHTML = "";
            }
            if (hasSummary) {
                summaryMeta.textContent = "Select Package";
                summaryBadge.textContent = "Required";
                summaryTitle.textContent = "Choose a package option";
                summaryDescription.textContent = "Please choose a package option above to configure menu and dish choices.";
                renderTags([]);
            }
            if (summaryInclusions) {
                summaryInclusions.innerHTML = '<li class="booking-summary-menu-item text-muted">Select a package option to view inclusions.</li>';
            }
            if (inclusionsCount) {
                inclusionsCount.textContent = "0 Items";
            }
            const modalMeta = document.getElementById("modalPreviewPackageMeta");
            const modalTitle = document.getElementById("modalPreviewPackageTitle");
            const modalRate = document.getElementById("modalPreviewPackageRate");
            const modalDownPayment = document.getElementById("modalPreviewDownPaymentInfo");
            const modalTotal = document.getElementById("modalPreviewTotalEstimate");
            const modalCountBadge = document.getElementById("modalInclusionsCountBadge");
            if (modalMeta) modalMeta.textContent = "Per-head";
            if (modalTitle) modalTitle.textContent = "No package selected";
            if (modalRate) modalRate.textContent = "—";
            if (modalDownPayment) modalDownPayment.innerHTML = '<i class="bi bi-info-circle text-secondary me-1"></i> Please select a package';
            if (modalTotal) modalTotal.textContent = "—";
            if (modalCountBadge) modalCountBadge.textContent = "0 Items";
            return;
        }

        const { selectedPackage, selectedTier } = getSelectedPackageState();

        if (!selectedPackage) {
            if (selectorCard) {
                selectorCard.hidden = true;
            }
            if (dishGrid) {
                dishGrid.innerHTML = "";
            }
            return;
        }

        if (!hasSummary) {
            return;
        }

        const groupMeta = catalogApi.GROUPS[selectedPackage.group] || {};
        const tierLabels = new Set(
            (selectedPackage.pricingTiers || []).map((tier) => String(tier?.label || "").trim().toLowerCase())
        );
        const selectedDownPaymentAmount = catalogApi.getPackageDownPaymentAmount
            ? catalogApi.getPackageDownPaymentAmount(selectedPackage, selectedTier?.label || "")
            : String(selectedPackage.downPaymentAmount || "").trim();

        // Calculate estimated total and down payment in pesos
        let estimatedTotal = 0;
        let guestCountVal = Number.parseInt(guestCountInput?.value || "50", 10) || 50;
        if (guestCountVal < 50) guestCountVal = 50;

        if (selectedPackage.group === "per-head") {
            const priceMatch = String(selectedPackage.rateLabel || "").match(/(\d+[\d,]*)/);
            if (priceMatch) {
                const unitPrice = Number(priceMatch[1].replace(/,/g, ""));
                estimatedTotal = unitPrice * guestCountVal;
            }
        } else {
            const tierPriceStr = selectedTier?.price || selectedPackage.rateLabel || "";
            const priceMatch = String(tierPriceStr).match(/(\d+[\d,]*)/);
            if (priceMatch) {
                estimatedTotal = Number(priceMatch[1].replace(/,/g, ""));
            }
        }

        // Build formatted down payment label (e.g. "20% Down payment (₱6,000.00 est.)")
        const dpPercent = Number.parseFloat(selectedDownPaymentAmount);
        let downPaymentLabel = "";
        if (selectedPackage.allowDownPayment && !Number.isNaN(dpPercent) && dpPercent > 0) {
            if (estimatedTotal > 0) {
                const dpAmount = Math.round(estimatedTotal * (dpPercent / 100));
                const dpFormatted = `₱${dpAmount.toLocaleString("en-PH", { minimumFractionDigits: 2 })}`;
                downPaymentLabel = `${dpPercent}% Down payment (${dpFormatted} est.)`;
            } else {
                downPaymentLabel = `${dpPercent}% QRPh down payment`;
            }
        } else if (selectedPackage.allowDownPayment) {
            downPaymentLabel = "QRPh down payment available";
        } else {
            downPaymentLabel = "Full payment after approval";
        }

        const shortLabel = String(groupMeta.shortLabel || "").trim();
        const categoryLabel = String(selectedPackage.category || "").trim();
        const metaText = shortLabel.toLowerCase() === categoryLabel.toLowerCase() || !categoryLabel
            ? shortLabel
            : `${shortLabel} | ${categoryLabel}`;

        summaryMeta.textContent = metaText;
        summaryBadge.textContent = selectedTier?.label || selectedPackage.guestLabel;
        summaryTitle.textContent = selectedPackage.name;
        summaryDescription.textContent = `${selectedTier?.price || selectedPackage.rateLabel} | ${selectedPackage.description}`;
        renderTags();

        // Sync Modal Package Details
        const modalMeta = document.getElementById("modalPreviewPackageMeta");
        const modalTitle = document.getElementById("modalPreviewPackageTitle");
        const modalRate = document.getElementById("modalPreviewPackageRate");
        const modalDownPayment = document.getElementById("modalPreviewDownPaymentInfo");
        const modalTotal = document.getElementById("modalPreviewTotalEstimate");
        const modalCountBadge = document.getElementById("modalInclusionsCountBadge");

        if (modalMeta) modalMeta.textContent = metaText;
        if (modalTitle) modalTitle.textContent = selectedPackage.name;
        if (modalRate) modalRate.textContent = selectedTier?.price || selectedPackage.rateLabel;
        if (modalDownPayment) {
            modalDownPayment.innerHTML = selectedPackage.allowDownPayment && downPaymentLabel
                ? `<i class="bi bi-shield-check text-success me-1"></i> ${downPaymentLabel}`
                : `<i class="bi bi-info-circle text-secondary me-1"></i> Full payment after approval`;
        }
        if (modalTotal) {
            if (estimatedTotal > 0) {
                if (selectedPackage.group === "per-head") {
                    modalTotal.textContent = `Estimated: ₱${estimatedTotal.toLocaleString("en-PH", { minimumFractionDigits: 2 })} (${guestCountVal} pax)`;
                } else {
                    modalTotal.textContent = `Estimated: ₱${estimatedTotal.toLocaleString("en-PH", { minimumFractionDigits: 2 })}`;
                }
            } else {
                modalTotal.textContent = selectedTier?.price || selectedPackage.rateLabel;
            }
        }

        const inclusions = Array.isArray(selectedPackage.inclusions) ? selectedPackage.inclusions : [];
        const countText = `${inclusions.length} ${inclusions.length === 1 ? "Item" : "Items"}`;

        if (inclusionsCount) {
            inclusionsCount.textContent = countText;
        }
        if (modalCountBadge) {
            modalCountBadge.textContent = countText;
        }

        // Render interactive dish selectors & sync to confirmation modal
        renderDishSelectors(selectedPackage);
    };

    const populateOptions = (preserveValue = false, autoSelect = true) => {
        if (!syncCategoryOptions()) {
            return;
        }

        const category = categorySelect.value;
        const options = catalogApi.getActivePackages(category);
        const optionSelections = options.flatMap((item) => catalogApi.getPackageOptionSelections(item));
        const previousValue = preserveValue ? optionSelect.value : "";

        optionSelect.innerHTML = "";

        // Add default placeholder option
        const placeholderOption = document.createElement("option");
        placeholderOption.value = "";
        placeholderOption.textContent = "-- Select a Package Option --";
        placeholderOption.disabled = true;
        placeholderOption.selected = true;
        optionSelect.appendChild(placeholderOption);

        optionSelections.forEach((selection) => {
            const option = document.createElement("option");
            option.value = selection.value;
            option.textContent = selection.label;
            option.dataset.packageId = selection.packageId;
            option.dataset.packageTierLabel = selection.tierLabel;
            option.dataset.packageTierPrice = selection.tierPrice;
            optionSelect.appendChild(option);
        });

        const previousSelection = previousValue ? catalogApi.parsePackageSelectionValue(previousValue) : null;
        const matchedSelection = previousValue ? (
            optionSelections.find((selection) => selection.value === previousValue)
            || (previousSelection ? optionSelections.find((selection) => (
                selection.packageId === previousSelection.packageId
                && (!previousSelection.tierLabel || selection.tierLabel === previousSelection.tierLabel)
            )) : null)
            || (previousSelection ? optionSelections.find((selection) => selection.packageId === previousSelection.packageId) : null)
        ) : null;

        if (matchedSelection) {
            placeholderOption.selected = false;
            optionSelect.value = matchedSelection.value;
        } else if (autoSelect && optionSelections.length > 0) {
            // Smart auto-select: choose signature buffet (All Occasions 600 / Head) if available, or first available package
            const preferred = optionSelections.find((s) => s.packageId === "signature-buffet-package") || optionSelections[0];
            placeholderOption.selected = false;
            optionSelect.value = preferred.value;
        } else {
            placeholderOption.selected = true;
            optionSelect.value = "";
        }

        updateSummary();
    };

    window.resetPackageSelection = () => {
        populateOptions(false, false);
    };

    categorySelect.addEventListener("change", () => {
        populateOptions();
    });

    optionSelect.addEventListener("change", updateSummary);
    if (guestCountInput) {
        guestCountInput.addEventListener("input", updateSummary);
        guestCountInput.addEventListener("change", updateSummary);
    }

    if (eventTypeSelect) {
        const syncCategoryFromEvent = () => {
            const nextCategory = eventTypeCategoryMap[eventTypeSelect.value];
            const availableGroups = getAvailableGroups();

            if (!nextCategory || !availableGroups.includes(nextCategory) || categorySelect.value === nextCategory) {
                return;
            }

            categorySelect.value = nextCategory;
            populateOptions(true);
        };

        eventTypeSelect.addEventListener("change", syncCategoryFromEvent);
        syncCategoryFromEvent();
    }

    populateOptions(true);
}

let bookingConfirmedFromModal = false;

function initializeLiveSummary() {
    const liveGuests = document.getElementById("summaryLiveGuests");
    const liveType = document.getElementById("summaryLiveType");
    const liveSchedule = document.getElementById("summaryLiveSchedule");
    const liveVenue = document.getElementById("summaryLiveVenue");
    const modalPreviewType = document.getElementById("modalPreviewEventType");
    const modalPreviewGuests = document.getElementById("modalPreviewGuests");
    const modalPreviewSchedule = document.getElementById("modalPreviewSchedule");
    const modalPreviewVenue = document.getElementById("modalPreviewVenue");
    const modalSubmitBtn = document.getElementById("modalSubmitProxyButton");
    const mainSubmitBtn = document.getElementById("bookingSubmitButton");
    const eventTypeSelect = document.getElementById("eventType");
    const eventTypeOtherGroup = document.getElementById("eventTypeOtherGroup");
    const eventTypeOtherInput = document.getElementById("eventTypeOther");
    const eventTypeResetBtn = document.getElementById("eventTypeResetBtn");
    const eventDateInput = document.getElementById("eventDate");
    const eventTimeInput = document.getElementById("eventTime");
    const guestCountInput = document.getElementById("guestCount");
    const venueInput = document.getElementById("venue");
    const venueOptions = document.querySelectorAll('input[name="venueOption"]');

    const syncLiveSummary = () => {
        let typeText = "Event Celebration";
        if (eventTypeSelect) {
            if (eventTypeSelect.value === "Others") {
                const customType = eventTypeOtherInput?.value.trim();
                typeText = customType || "Other Event";
            } else if (eventTypeSelect.value) {
                typeText = eventTypeSelect.value;
            }
        }
        if (liveType) {
            liveType.textContent = typeText;
        }
        if (modalPreviewType) {
            modalPreviewType.textContent = typeText;
        }

        const count = Number.parseInt(guestCountInput?.value || "0", 10);
        const guestText = !Number.isNaN(count) && count > 0 ? `${count} Guests` : "Min. 50 Guests";
        if (liveGuests) {
            liveGuests.innerHTML = `<i class="bi bi-people me-1"></i> ${guestText}`;
        }
        if (modalPreviewGuests) {
            modalPreviewGuests.textContent = guestText;
        }

        const dateVal = eventDateInput ? eventDateInput.value : "";
        const timeVal = eventTimeInput ? eventTimeInput.value : "";
        let dateDisplay = "Date not selected";
        if (dateVal) {
            const yearPart = dateVal.split("-")[0];
            dateDisplay = `${formatDisplayDate(dateVal, "compact")}, ${yearPart}`;
        }
        let timeDisplay = "";
        if (timeVal) {
            const [hStr, mStr] = timeVal.split(":");
            const h = Number.parseInt(hStr, 10);
            const ampm = h >= 12 ? "PM" : "AM";
            const displayH = h % 12 || 12;
            timeDisplay = ` at ${displayH}:${mStr} ${ampm}`;
        }
        const scheduleText = dateVal ? `${dateDisplay}${timeDisplay}` : "Date not selected";
        if (liveSchedule) {
            liveSchedule.textContent = scheduleText;
        }
        if (modalPreviewSchedule) {
            modalPreviewSchedule.textContent = scheduleText;
        }

        const selectedVenueOption = document.querySelector('input[name="venueOption"]:checked')?.value || "own";
        let venueText = "Own venue";
        if (selectedVenueOption === "emarioh") {
            venueText = "Emarioh In-house Venue";
        } else {
            const venueVal = venueInput ? venueInput.value.trim() : "";
            venueText = venueVal || "Own venue";
        }
        if (liveVenue) {
            liveVenue.textContent = venueText;
        }
        if (modalPreviewVenue) {
            modalPreviewVenue.textContent = venueText;
        }
    };

    if (eventTypeSelect) {
        eventTypeSelect.addEventListener("change", () => {
            if (eventTypeSelect.value === "Others") {
                eventTypeSelect.hidden = true;
                if (eventTypeOtherGroup) eventTypeOtherGroup.hidden = false;
                eventTypeOtherInput?.focus();
            } else {
                eventTypeSelect.hidden = false;
                if (eventTypeOtherGroup) eventTypeOtherGroup.hidden = true;
                if (eventTypeOtherInput) eventTypeOtherInput.value = "";
            }
            syncLiveSummary();
        });
    }

    if (eventTypeResetBtn) {
        eventTypeResetBtn.addEventListener("click", () => {
            if (eventTypeOtherGroup) eventTypeOtherGroup.hidden = true;
            if (eventTypeOtherInput) eventTypeOtherInput.value = "";
            if (eventTypeSelect) {
                eventTypeSelect.hidden = false;
                eventTypeSelect.value = "";
                eventTypeSelect.focus();
            }
            syncLiveSummary();
        });
    }

    if (eventTypeOtherInput) {
        eventTypeOtherInput.addEventListener("input", syncLiveSummary);
        eventTypeOtherInput.addEventListener("keydown", (e) => {
            if (e.key === "Escape") {
                eventTypeResetBtn?.click();
            }
        });
    }
    if (eventDateInput) {
        eventDateInput.addEventListener("change", syncLiveSummary);
    }
    if (eventTimeInput) {
        eventTimeInput.addEventListener("input", syncLiveSummary);
        eventTimeInput.addEventListener("change", syncLiveSummary);
    }
    if (guestCountInput) {
        guestCountInput.addEventListener("input", syncLiveSummary);
        guestCountInput.addEventListener("change", syncLiveSummary);
    }
    if (venueInput) {
        venueInput.addEventListener("input", syncLiveSummary);
        venueInput.addEventListener("change", syncLiveSummary);
    }
    venueOptions.forEach((option) => {
        option.addEventListener("change", syncLiveSummary);
    });

    if (modalSubmitBtn && mainSubmitBtn) {
        modalSubmitBtn.addEventListener("click", () => {
            const submitFeedback = document.getElementById("bookingSubmitFeedback");
            const bookingPayload = collectBookingPayload(submitFeedback);
            const modalEl = document.getElementById("bookingDetailsModal");

            if (!bookingPayload) {
                // If any required field is invalid/missing, hide modal and let collectBookingPayload focus on it
                if (modalEl && window.bootstrap) {
                    const modalInstance = window.bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
                return;
            }

            modalSubmitBtn.disabled = true;
            modalSubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Submitting...';

            if (modalEl && window.bootstrap) {
                const modalInstance = window.bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }

            bookingConfirmedFromModal = true;
            const detailsForm = document.querySelector(".booking-form--details");
            if (detailsForm && typeof detailsForm.requestSubmit === "function") {
                detailsForm.requestSubmit();
            } else {
                mainSubmitBtn.click();
            }
        });
    }

    window.syncBookingLiveSummary = syncLiveSummary;

    syncLiveSummary();
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

function initializeBookingSubmission() {
    const detailsForm = document.querySelector(".booking-form--details");
    const submitButton = document.getElementById("bookingSubmitButton");
    const submitFeedback = document.getElementById("bookingSubmitFeedback");
    const submitTitle = document.getElementById("bookingSubmitTitle");
    const submitText = document.getElementById("bookingSubmitText");
    const modalSubmitBtn = document.getElementById("modalSubmitProxyButton");

    if (!detailsForm || !submitButton || !submitFeedback || detailsForm.dataset.bookingBound === "true") {
        return;
    }

    detailsForm.dataset.bookingBound = "true";

    const executeBookingSubmission = async () => {
        if (submitButton.disabled) {
            renderBookingSubmitFeedback(
                submitFeedback,
                submitText?.textContent || "Please settle your existing booking payment before submitting another request.",
                "error"
            );
            return;
        }

        const bookingPayload = collectBookingPayload(submitFeedback);

        if (!bookingPayload) {
            return;
        }

        // Option 1 flow: If not yet confirmed via modal review, open review modal first!
        if (!bookingConfirmedFromModal) {
            if (typeof window.syncBookingLiveSummary === "function") {
                window.syncBookingLiveSummary();
            }
            const modalEl = document.getElementById("bookingDetailsModal");
            if (modalEl) {
                try {
                    if (window.bootstrap?.Modal) {
                        const modalInstance = window.bootstrap.Modal.getOrCreateInstance(modalEl);
                        modalInstance.show();
                        return;
                    }
                } catch (modalErr) {
                    console.error("Failed to show booking review modal:", modalErr);
                }
            }
        }

        // Reset confirmation flag
        bookingConfirmedFromModal = false;

        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Submitting...';

        if (submitTitle) {
            submitTitle.textContent = "Submitting Request...";
        }

        if (submitText) {
            submitText.textContent = "Please wait while we save your booking request.";
        }

        renderBookingSubmitFeedback(submitFeedback, "Saving your booking request...", "info");

        try {
            const responsePayload = await postBookingJson("api/bookings/create.php", bookingPayload);

            clearBookingDraft();
            syncClientPortalBookingState(bookingPayload, responsePayload);
            renderBookingSubmitFeedback(
                submitFeedback,
                responsePayload.message || "Booking request submitted successfully.",
                "success"
            );

            window.location.assign(responsePayload.redirect_url || "client-my-bookings.php");
        } catch (error) {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="bi bi-clipboard2-check me-2"></i><span>Review &amp; Submit Booking Request →</span>';
            if (modalSubmitBtn) {
                modalSubmitBtn.disabled = false;
                modalSubmitBtn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Confirm &amp; Submit Request →';
            }

            if (submitTitle) {
                submitTitle.textContent = "Ready To Submit?";
            }

            if (submitText) {
                submitText.textContent = "After submission, your request will appear in My Bookings while the team checks availability and prepares billing.";
            }

            renderBookingSubmitFeedback(
                submitFeedback,
                error?.message || "Booking request could not be submitted right now. Please try again.",
                "error"
            );
        }
    };

    submitButton.addEventListener("click", (event) => {
        event.preventDefault();
        executeBookingSubmission();
    });

    detailsForm.addEventListener("submit", (event) => {
        event.preventDefault();
        executeBookingSubmission();
    });

    detailsForm.addEventListener("input", () => {
        if (submitFeedback && submitFeedback.dataset.state === "error") {
            renderBookingSubmitFeedback(submitFeedback, "");
        }
    });

    detailsForm.addEventListener("change", () => {
        if (submitFeedback && submitFeedback.dataset.state === "error") {
            renderBookingSubmitFeedback(submitFeedback, "");
        }
    });
}

function highlightInvalidField(field, submitFeedback, message) {
    if (submitFeedback) {
        renderBookingSubmitFeedback(submitFeedback, message, "error");
    }
    if (field) {
        field.classList.add("is-invalid");
        field.scrollIntoView({ behavior: "smooth", block: "center" });
        setTimeout(() => {
            try {
                field.focus({ preventScroll: true });
            } catch (_) {
                field.focus();
            }
        }, 150);

        const removeInvalid = () => {
            field.classList.remove("is-invalid");
            field.removeEventListener("input", removeInvalid);
            field.removeEventListener("change", removeInvalid);
            if (submitFeedback && submitFeedback.dataset.state === "error") {
                renderBookingSubmitFeedback(submitFeedback, "");
            }
        };
        field.addEventListener("input", removeInvalid);
        field.addEventListener("change", removeInvalid);
    }
}

function collectBookingPayload(submitFeedback) {
    const eventTypeField = document.getElementById("eventType");
    const eventDateField = document.getElementById("eventDate");
    const eventDateTrigger = document.getElementById("eventDateTrigger");
    const eventTimeField = document.getElementById("eventTime");
    const guestCountField = document.getElementById("guestCount");
    const venueField = document.getElementById("venue");
    const packageCategoryField = document.getElementById("packageCategory");
    const packageOptionField = document.getElementById("packageOption");
    const primaryContactField = document.getElementById("primaryContact");
    const primaryMobileField = document.getElementById("primaryMobile");
    const alternateContactField = document.getElementById("alternateContact");
    const eventNotesField = document.getElementById("eventNotes");
    const bookingNotesField = document.getElementById("bookingNotes");
    const selectedVenueOption = document.querySelector('input[name="venueOption"]:checked')?.value || "own";
    const usingOwnVenue = selectedVenueOption === "own";
    const eventTypeOtherInput = document.getElementById("eventTypeOther");
    const catalogApi = window.EmariohPackageCatalog;
    let resolvedEventType = eventTypeField?.value.trim() || "";

    if (!resolvedEventType) {
        highlightInvalidField(
            eventTypeField,
            submitFeedback,
            "Please select an event type before submitting your request."
        );
        return null;
    }

    if (resolvedEventType === "Others") {
        const customType = eventTypeOtherInput?.value.trim() || "";
        if (!customType) {
            highlightInvalidField(
                eventTypeOtherInput,
                submitFeedback,
                "Please type your event name (e.g. Christening, Graduation) before submitting."
            );
            return null;
        }
        resolvedEventType = customType;
    }

    if (!eventDateField?.value) {
        highlightInvalidField(
            eventDateTrigger,
            submitFeedback,
            "Please choose an event date before submitting your request."
        );
        return null;
    }

    if (!eventTimeField?.value.trim()) {
        highlightInvalidField(
            eventTimeField,
            submitFeedback,
            "Please choose an event start time before submitting your request."
        );
        return null;
    }

    const guestCountHint = document.getElementById("guestCountHint");
    const parsedGuestCount = Number.parseInt(guestCountField?.value, 10) || 0;
    if (!guestCountField?.value.trim() || parsedGuestCount < 50) {
        if (guestCountHint) {
            guestCountHint.textContent = "⚠️ Minimum of 50 guests is required for catering packages.";
            guestCountHint.className = "booking-guest-count-feedback";
        }
        highlightInvalidField(
            guestCountField,
            submitFeedback,
            "Please enter at least 50 guests before submitting."
        );
        return null;
    }

    if (usingOwnVenue && !venueField?.value.trim()) {
        highlightInvalidField(
            venueField,
            submitFeedback,
            "Please provide the venue or event location before submitting."
        );
        return null;
    }

    if (!packageCategoryField?.value || !packageOptionField?.value) {
        highlightInvalidField(
            packageOptionField,
            submitFeedback,
            "Please choose a package before submitting your request."
        );
        return null;
    }

    if (!primaryContactField?.value.trim()) {
        highlightInvalidField(
            primaryContactField,
            submitFeedback,
            "Please provide the main contact name before submitting."
        );
        return null;
    }

    if (!primaryMobileField?.value.trim()) {
        highlightInvalidField(
            primaryMobileField,
            submitFeedback,
            "Please enter a valid Philippine mobile number before submitting."
        );
        return null;
    }

    const selectedOption = packageOptionField.options[packageOptionField.selectedIndex] || null;
    const selectionValue = packageOptionField.value;
    const selectionDetails = catalogApi?.parsePackageSelectionValue
        ? catalogApi.parsePackageSelectionValue(selectionValue)
        : { packageId: selectionValue, tierLabel: "" };
    const packageItem = catalogApi?.getPackageById
        ? catalogApi.getPackageById(selectionDetails.packageId || selectionValue)
        : null;
    const selectedTier = catalogApi?.getPackagePricingTier
        ? catalogApi.getPackagePricingTier(packageItem, selectionDetails.tierLabel)
        : null;
    const selectedDownPaymentAmount = catalogApi?.getPackageDownPaymentAmount
        ? catalogApi.getPackageDownPaymentAmount(packageItem, selectedTier?.label || selectionDetails.tierLabel || "")
        : String(packageItem?.downPaymentAmount || "").trim();
    if (packageItem) {
        const activeCourses = getActiveDishCourses(packageItem);
        const multiCourse = activeCourses.find((c) => c.type === "multiselect");
        if (multiCourse) {
            const multiPicks = Array.isArray(selectedDishChoices[multiCourse.id]) ? selectedDishChoices[multiCourse.id] : [];
            const requiredCount = multiCourse.maxSelect || 2;
            if (multiPicks.length < requiredCount) {
                const btn = document.getElementById(`dishSelect_${multiCourse.id}`);
                highlightInvalidField(
                    btn,
                    submitFeedback,
                    `Please select ${requiredCount} beverages for your package.`
                );
                if (btn && !btn.classList.contains("is-open")) {
                    btn.click();
                }
                return null;
            }
        }
    }

    const dishesSummaryText = getSelectedDishesSummaryText(packageItem);
    const mergedNotes = buildMergedBookingNotes(
        dishesSummaryText,
        eventNotesField?.value || "",
        bookingNotesField?.value || ""
    );

    return {
        event_type: resolvedEventType,
        event_date: eventDateField.value,
        event_time: eventTimeField.value,
        guest_count: Number.parseInt(guestCountField.value, 10) || 0,
        venue_option: selectedVenueOption,
        venue_name: usingOwnVenue ? venueField.value.trim() : "Emarioh In-House Venue",
        package_category_value: packageCategoryField.value,
        package_selection_value: selectionValue,
        package_label: selectedOption?.textContent?.trim() || packageItem?.name || "Selected Package",
        package_tier_label: selectedTier?.label || selectionDetails.tierLabel || "",
        package_tier_price: selectedTier?.price || "",
        package_allows_down_payment: Boolean(packageItem?.allowDownPayment),
        package_down_payment_amount: selectedDownPaymentAmount,
        primary_contact: primaryContactField.value.trim(),
        primary_mobile: primaryMobileField.value.trim(),
        alternate_contact: alternateContactField?.value.trim() || "",
        event_notes: mergedNotes
    };
}

function buildMergedBookingNotes(dishesText, eventNotes, bookingNotes) {
    const sections = [];
    const trimmedDishes = String(dishesText || "").trim();
    const trimmedEventNotes = String(eventNotes || "").trim();
    const trimmedBookingNotes = String(bookingNotes || "").trim();

    if (trimmedDishes) {
        sections.push(trimmedDishes);
    }

    if (trimmedEventNotes) {
        sections.push(`Special Requests / Preferences:\n${trimmedEventNotes}`);
    }

    if (trimmedBookingNotes) {
        sections.push(`Additional Notes:\n${trimmedBookingNotes}`);
    }

    return sections.join("\n\n");
}

async function postBookingJson(url, payload) {
    const resolvedUrl = window.EmariohRuntime?.resolveUrl
        ? window.EmariohRuntime.resolveUrl(url)
        : url;
    const response = await fetch(resolvedUrl, {
        method: "POST",
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json"
        },
        credentials: "same-origin",
        body: JSON.stringify(payload || {})
    });
    const rawText = await response.text();
    let parsedPayload = {};

    try {
        parsedPayload = rawText ? JSON.parse(rawText) : {};
    } catch (error) {
        parsedPayload = {};
    }

    if (!response.ok || parsedPayload.ok === false) {
        throw new Error(parsedPayload.message || "Request failed.");
    }

    return parsedPayload;
}

let bookingSubmitFeedbackTimer = null;

function renderBookingSubmitFeedback(element, message, tone = "info", autoDismissMs = null) {
    if (!element) {
        return;
    }

    if (bookingSubmitFeedbackTimer) {
        window.clearTimeout(bookingSubmitFeedbackTimer);
        bookingSubmitFeedbackTimer = null;
    }

    const trimmed = String(message || "").trim();
    if (!trimmed) {
        if (element.classList.contains("is-visible")) {
            element.classList.add("is-hiding");
            bookingSubmitFeedbackTimer = window.setTimeout(() => {
                element.innerHTML = "";
                element.className = "booking-submit-feedback";
                element.style.display = "none";
                element.classList.remove("is-hiding", "is-visible");
                element.removeAttribute("data-state");
            }, 300);
        } else {
            element.innerHTML = "";
            element.className = "booking-submit-feedback";
            element.style.display = "none";
            element.removeAttribute("data-state");
        }
        return;
    }

    const iconClass = tone === "error"
        ? "bi-exclamation-triangle-fill"
        : (tone === "success" ? "bi-check-circle-fill" : "bi-info-circle-fill");

    element.innerHTML = `
        <span class="booking-submit-feedback__content">
            <i class="bi ${iconClass} me-2"></i>
            <span>${trimmed}</span>
        </span>
        <button type="button" class="btn-close ms-2 booking-submit-feedback__close" aria-label="Close" title="Dismiss"></button>
    `;
    element.dataset.state = tone;
    element.classList.remove("is-hiding");
    element.className = `booking-submit-feedback is-visible booking-submit-feedback--${tone}`;
    element.style.display = "inline-flex";

    const closeBtn = element.querySelector(".booking-submit-feedback__close");
    if (closeBtn) {
        closeBtn.addEventListener("click", (evt) => {
            evt.preventDefault();
            evt.stopPropagation();
            renderBookingSubmitFeedback(element, "");
        });
    }

    // Auto-dismiss timeout logic (default: 6 seconds for warning/error, then smoothly fades out)
    let duration = autoDismissMs;
    if (duration === null || duration === undefined) {
        if (tone === "error") {
            duration = 6000;
        } else if (tone === "success") {
            duration = 5000;
        } else if (tone === "info") {
            if (trimmed.toLowerCase().includes("saving")) {
                duration = 0; // Don't auto-dismiss saving state
            } else {
                duration = 4000;
            }
        } else {
            duration = 5000;
        }
    }

    if (duration > 0) {
        bookingSubmitFeedbackTimer = window.setTimeout(() => {
            renderBookingSubmitFeedback(element, "");
        }, duration);
    }
}

function syncClientPortalBookingState(bookingPayload, responsePayload) {
    try {
        const storageKey = "emariohClientPortalState";
        const currentState = JSON.parse(window.localStorage.getItem(storageKey) || "{}");
        const selectionDetails = window.EmariohPackageCatalog?.parsePackageSelectionValue
            ? window.EmariohPackageCatalog.parsePackageSelectionValue(bookingPayload.package_selection_value)
            : { packageId: bookingPayload.package_selection_value, tierLabel: bookingPayload.package_tier_label || "" };
        const nextState = {
            ...currentState,
            clientName: bookingPayload.primary_contact,
            bookingRequest: {
                reference: responsePayload.reference || "",
                eventType: bookingPayload.event_type,
                eventDate: bookingPayload.event_date,
                eventTime: bookingPayload.event_time,
                guestCount: String(bookingPayload.guest_count),
                venueOption: bookingPayload.venue_option,
                venue: bookingPayload.venue_name,
                packageCategoryValue: bookingPayload.package_category_value,
                packageValue: bookingPayload.package_selection_value,
                packageBaseValue: selectionDetails.packageId || bookingPayload.package_selection_value,
                packageTierLabel: bookingPayload.package_tier_label || selectionDetails.tierLabel || "",
                packageTierPrice: bookingPayload.package_tier_price || "",
                packageAllowsDownPayment: Boolean(bookingPayload.package_allows_down_payment),
                packageDownPaymentAmount: bookingPayload.package_down_payment_amount || "",
                packageLabel: bookingPayload.package_label,
                primaryContact: bookingPayload.primary_contact,
                primaryMobile: bookingPayload.primary_mobile,
                alternateContact: bookingPayload.alternate_contact,
                notes: bookingPayload.event_notes,
                status: "pending_review",
                submittedAt: new Date().toISOString()
            }
        };

        delete nextState.billingDetails;
        window.localStorage.setItem(storageKey, JSON.stringify(nextState));
    } catch (error) {
        return;
    }
}

const BOOKING_DRAFT_KEY = "emarioh_booking_form_draft_v1";

function saveBookingDraft() {
    try {
        const eventTypeField = document.getElementById("eventType");
        const eventTypeOtherInput = document.getElementById("eventTypeOther");
        const eventDateField = document.getElementById("eventDate");
        const eventTimeField = document.getElementById("eventTime");
        const guestCountField = document.getElementById("guestCount");
        const venueOption = document.querySelector('input[name="venueOption"]:checked')?.value || "own";
        const venueField = document.getElementById("venue");
        const packageCategoryField = document.getElementById("packageCategory");
        const packageOptionField = document.getElementById("packageOption");
        const primaryContactField = document.getElementById("primaryContact");
        const primaryMobileField = document.getElementById("primaryMobile");
        const alternateContactField = document.getElementById("alternateContact");
        const eventNotesField = document.getElementById("eventNotes");

        const draft = {
            eventType: eventTypeField?.value || "",
            eventTypeOther: eventTypeOtherInput?.value || "",
            eventDate: eventDateField?.value || "",
            eventTime: eventTimeField?.value || "",
            guestCount: guestCountField?.value || "",
            venueOption: venueOption,
            venue: venueField?.value || "",
            packageCategory: packageCategoryField?.value || "",
            packageOption: packageOptionField?.value || "",
            dishChoices: { ...selectedDishChoices },
            primaryContact: primaryContactField?.value || "",
            primaryMobile: primaryMobileField?.value || "",
            alternateContact: alternateContactField?.value || "",
            eventNotes: eventNotesField?.value || ""
        };
        window.sessionStorage.setItem(BOOKING_DRAFT_KEY, JSON.stringify(draft));
    } catch (_) {}
}

function restoreBookingDraft() {
    try {
        const raw = window.sessionStorage.getItem(BOOKING_DRAFT_KEY);
        if (!raw) return;
        const draft = JSON.parse(raw);
        if (!draft) return;

        // Restore Event Type
        const eventTypeSelect = document.getElementById("eventType");
        const eventTypeOtherInput = document.getElementById("eventTypeOther");
        const eventTypeOtherGroup = document.getElementById("eventTypeOtherGroup");
        if (draft.eventType && eventTypeSelect) {
            eventTypeSelect.value = draft.eventType;
            if (draft.eventType === "Others") {
                eventTypeSelect.hidden = true;
                if (eventTypeOtherGroup) eventTypeOtherGroup.hidden = false;
                if (eventTypeOtherInput && draft.eventTypeOther) {
                    eventTypeOtherInput.value = draft.eventTypeOther;
                }
            } else {
                eventTypeSelect.hidden = false;
                if (eventTypeOtherGroup) eventTypeOtherGroup.hidden = true;
            }
        }

        // Restore Date
        if (draft.eventDate && typeof window.setBookingSelectedDate === "function") {
            window.setBookingSelectedDate(draft.eventDate);
        }

        // Restore Time
        const eventTimeField = document.getElementById("eventTime");
        if (draft.eventTime && eventTimeField) {
            eventTimeField.value = draft.eventTime;
        }

        // Restore Guest Count
        const guestCountField = document.getElementById("guestCount");
        if (draft.guestCount && guestCountField) {
            guestCountField.value = draft.guestCount;
            guestCountField.dispatchEvent(new Event("input", { bubbles: true }));
        }

        // Restore Venue
        if (draft.venueOption) {
            const venueRadio = document.querySelector(`input[name="venueOption"][value="${draft.venueOption}"]`);
            if (venueRadio) {
                venueRadio.checked = true;
                venueRadio.dispatchEvent(new Event("change", { bubbles: true }));
            }
        }
        const venueField = document.getElementById("venue");
        if (draft.venue && venueField) {
            venueField.value = draft.venue;
        }

        // Restore Package Category & Option
        const packageCategoryField = document.getElementById("packageCategory");
        const packageOptionField = document.getElementById("packageOption");
        if (draft.packageCategory && packageCategoryField) {
            packageCategoryField.value = draft.packageCategory;
            packageCategoryField.dispatchEvent(new Event("change", { bubbles: true }));
        }
        if (draft.packageOption && packageOptionField) {
            packageOptionField.value = draft.packageOption;
            packageOptionField.dispatchEvent(new Event("change", { bubbles: true }));
        }

        // Restore Dish Choices
        if (draft.dishChoices && typeof draft.dishChoices === "object") {
            Object.assign(selectedDishChoices, draft.dishChoices);
            Object.keys(draft.dishChoices).forEach((courseId) => {
                const select = document.getElementById(`dishSelect_${courseId}`);
                if (select) {
                    select.value = draft.dishChoices[courseId];
                }
            });
        }

        // Restore Contacts & Notes
        const primaryContactField = document.getElementById("primaryContact");
        if (draft.primaryContact && primaryContactField && !primaryContactField.value) {
            primaryContactField.value = draft.primaryContact;
        }
        const primaryMobileField = document.getElementById("primaryMobile");
        if (draft.primaryMobile && primaryMobileField && !primaryMobileField.value) {
            primaryMobileField.value = draft.primaryMobile;
        }
        const alternateContactField = document.getElementById("alternateContact");
        if (draft.alternateContact && alternateContactField) {
            alternateContactField.value = draft.alternateContact;
        }
        const eventNotesField = document.getElementById("eventNotes");
        if (draft.eventNotes && eventNotesField) {
            eventNotesField.value = draft.eventNotes;
        }

        if (typeof window.syncBookingLiveSummary === "function") {
            window.syncBookingLiveSummary();
        }
    } catch (_) {}
}

function clearBookingDraft() {
    try {
        window.sessionStorage.removeItem(BOOKING_DRAFT_KEY);
    } catch (_) {}
}

function initializeBookingDraftAutosave() {
    const form = document.getElementById("bookingFormShell");
    if (!form) return;

    form.addEventListener("input", saveBookingDraft);
    form.addEventListener("change", saveBookingDraft);
}

function clearAllBookingFields() {
    // 1. Reset Event Type
    const eventTypeSelect = document.getElementById("eventType");
    const eventTypeOtherInput = document.getElementById("eventTypeOther");
    const eventTypeOtherGroup = document.getElementById("eventTypeOtherGroup");
    if (eventTypeSelect) {
        eventTypeSelect.value = "";
        eventTypeSelect.hidden = false;
        eventTypeSelect.dispatchEvent(new Event("change", { bubbles: true }));
    }
    if (eventTypeOtherInput) {
        eventTypeOtherInput.value = "";
    }
    if (eventTypeOtherGroup) {
        eventTypeOtherGroup.hidden = true;
    }

    // 2. Reset Date Picker
    if (typeof window.setBookingSelectedDate === "function") {
        window.setBookingSelectedDate("");
    }

    // 3. Reset Start Time
    const eventTimeField = document.getElementById("eventTime");
    if (eventTimeField) {
        eventTimeField.value = "";
        eventTimeField.dispatchEvent(new Event("change", { bubbles: true }));
    }

    // 4. Reset Guest Count
    const guestCountInput = document.getElementById("guestCount");
    const guestCountHint = document.getElementById("guestCountHint");
    if (guestCountInput) {
        guestCountInput.value = "";
        guestCountInput.dispatchEvent(new Event("input", { bubbles: true }));
    }
    if (guestCountHint) {
        guestCountHint.textContent = "Minimum of 50 guests required.";
        guestCountHint.className = "booking-field__hint";
    }

    // 5. Reset Venue
    const ownVenueRadio = document.querySelector('input[name="venueOption"][value="own"]');
    if (ownVenueRadio) {
        ownVenueRadio.checked = true;
        ownVenueRadio.dispatchEvent(new Event("change", { bubbles: true }));
    }
    const venueField = document.getElementById("venue");
    if (venueField) {
        venueField.value = "";
    }

    // 6. Reset Package Category & Option
    const packageCategoryField = document.getElementById("packageCategory");
    if (packageCategoryField) {
        packageCategoryField.value = "per-head";
    }
    if (typeof window.resetPackageSelection === "function") {
        window.resetPackageSelection();
    }

    // Clear dish choices
    for (const key in selectedDishChoices) {
        delete selectedDishChoices[key];
    }

    // 7. Reset Contacts & Notes
    const primaryContactField = document.getElementById("primaryContact");
    if (primaryContactField) {
        primaryContactField.value = primaryContactField.dataset.defaultValue ?? primaryContactField.defaultValue ?? "";
    }
    const primaryMobileField = document.getElementById("primaryMobile");
    if (primaryMobileField) {
        primaryMobileField.value = primaryMobileField.dataset.defaultValue ?? primaryMobileField.defaultValue ?? "";
    }
    const alternateContactField = document.getElementById("alternateContact");
    if (alternateContactField) {
        alternateContactField.value = "";
    }
    const eventNotesField = document.getElementById("eventNotes");
    if (eventNotesField) {
        eventNotesField.value = "";
    }
    const bookingNotesField = document.getElementById("bookingNotes");
    if (bookingNotesField) {
        bookingNotesField.value = "";
    }

    // 8. Clear Session Storage Draft
    clearBookingDraft();

    // 9. Reset Validation and Feedback Message
    const feedbackEl = document.getElementById("bookingSubmitFeedback");
    if (feedbackEl && typeof renderBookingSubmitFeedback === "function") {
        renderBookingSubmitFeedback(feedbackEl, "All form fields have been reset.", "info");
        window.setTimeout(() => {
            renderBookingSubmitFeedback(feedbackEl, "");
        }, 3500);
    }

    // 10. Sync Live Summary
    if (typeof window.syncBookingLiveSummary === "function") {
        window.syncBookingLiveSummary();
    }

    // 11. Smooth scroll to top of form
    const formTop = document.getElementById("bookingFormShell");
    if (formTop) {
        formTop.scrollIntoView({ behavior: "smooth", block: "start" });
    }
}

window.clearAllBookingFields = clearAllBookingFields;

function initializeClearFieldsAction() {
    const confirmBtn = document.getElementById("confirmClearAllFieldsBtn");
    const modalEl = document.getElementById("bookingClearConfirmModal");

    if (confirmBtn) {
        confirmBtn.addEventListener("click", () => {
            if (modalEl && window.bootstrap?.Modal) {
                const modalInstance = window.bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
            clearAllBookingFields();
        });
    }
}
