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
    initializeBookingSubmission();
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
    const catalogApi = window.EmariohPackageCatalog;
    const hasSummary = Boolean(summaryMeta && summaryBadge && summaryTitle && summaryDescription && summaryTags);

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

        tags.forEach((tag) => {
            const chip = document.createElement("span");
            chip.className = "booking-package-summary__tag";
            chip.textContent = tag;
            summaryTags.appendChild(chip);
        });
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
        const { selectedPackage, selectedTier } = getSelectedPackageState();

        if (!selectedPackage) {
            return;
        }

        if (!hasSummary) {
            return;
        }

        const groupMeta = catalogApi.GROUPS[selectedPackage.group];
        const tierLabels = new Set(
            (selectedPackage.pricingTiers || []).map((tier) => String(tier?.label || "").trim().toLowerCase())
        );
        const selectedDownPaymentAmount = catalogApi.getPackageDownPaymentAmount
            ? catalogApi.getPackageDownPaymentAmount(selectedPackage, selectedTier?.label || "")
            : String(selectedPackage.downPaymentAmount || "").trim();
        const summaryTagsList = selectedPackage.tags
            .filter((tag) => !tierLabels.has(String(tag || "").trim().toLowerCase()))
            .slice(0, 3);

        if (selectedPackage.allowDownPayment && selectedDownPaymentAmount) {
            summaryTagsList.unshift(`QRPh down payment: ${selectedDownPaymentAmount}`);
        } else if (selectedPackage.allowDownPayment) {
            summaryTagsList.unshift("QRPh down payment available");
        } else {
            summaryTagsList.unshift("Full payment after approval");
        }

        if (selectedTier?.label) {
            summaryTagsList.splice(1, 0, `Selected size: ${selectedTier.label}`);
        }

        if (!summaryTagsList.length) {
            summaryTagsList.push(selectedPackage.rateLabel);
        }

        summaryMeta.textContent = `${groupMeta.shortLabel} | ${selectedPackage.category}`;
        summaryBadge.textContent = selectedTier?.label || selectedPackage.guestLabel;
        summaryTitle.textContent = selectedPackage.name;
        summaryDescription.textContent = `${selectedTier?.price || selectedPackage.rateLabel} | ${selectedPackage.description}`;
        renderTags(summaryTagsList.slice(0, 4));
    };

    const populateOptions = (preserveValue = false) => {
        if (!syncCategoryOptions()) {
            return;
        }

        const category = categorySelect.value;
        const options = catalogApi.getActivePackages(category);
        const optionSelections = options.flatMap((item) => catalogApi.getPackageOptionSelections(item));
        const previousValue = preserveValue ? optionSelect.value : "";

        optionSelect.innerHTML = "";

        optionSelections.forEach((selection) => {
            const option = document.createElement("option");
            option.value = selection.value;
            option.textContent = selection.label;
            option.dataset.packageId = selection.packageId;
            option.dataset.packageTierLabel = selection.tierLabel;
            option.dataset.packageTierPrice = selection.tierPrice;
            optionSelect.appendChild(option);
        });

        const previousSelection = catalogApi.parsePackageSelectionValue(previousValue);
        const matchedSelection = optionSelections.find((selection) => selection.value === previousValue)
            || optionSelections.find((selection) => (
                selection.packageId === previousSelection.packageId
                && (!previousSelection.tierLabel || selection.tierLabel === previousSelection.tierLabel)
            ))
            || optionSelections.find((selection) => selection.packageId === previousSelection.packageId);

        optionSelect.value = matchedSelection?.value || optionSelections[0]?.value || "";
        updateSummary();
    };

    categorySelect.addEventListener("change", () => {
        populateOptions();
    });

    optionSelect.addEventListener("change", updateSummary);

    if (eventTypeSelect) {
        const syncCategoryFromEvent = () => {
            const nextCategory = eventTypeCategoryMap[eventTypeSelect.value];
            const availableGroups = getAvailableGroups();

            if (!nextCategory || !availableGroups.includes(nextCategory) || categorySelect.value === nextCategory) {
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

function initializeBookingSubmission() {
    const detailsForm = document.querySelector(".booking-form--details");
    const submitButton = document.getElementById("bookingSubmitButton");
    const submitFeedback = document.getElementById("bookingSubmitFeedback");
    const submitTitle = document.getElementById("bookingSubmitTitle");
    const submitText = document.getElementById("bookingSubmitText");

    if (!detailsForm || !submitButton || !submitFeedback || detailsForm.dataset.bookingBound === "true") {
        return;
    }

    detailsForm.dataset.bookingBound = "true";

    detailsForm.addEventListener("submit", async (event) => {
        event.preventDefault();

        const bookingPayload = collectBookingPayload(submitFeedback);

        if (!bookingPayload) {
            return;
        }

        submitButton.disabled = true;
        submitButton.textContent = "Submitting...";

        if (submitTitle) {
            submitTitle.textContent = "Submitting Request...";
        }

        if (submitText) {
            submitText.textContent = "Please wait while we save your booking request.";
        }

        renderBookingSubmitFeedback(submitFeedback, "Saving your booking request...", "info");

        try {
            const responsePayload = await postBookingJson("api/bookings/create.php", bookingPayload);

            syncClientPortalBookingState(bookingPayload, responsePayload);
            renderBookingSubmitFeedback(
                submitFeedback,
                responsePayload.message || "Booking request submitted successfully.",
                "success"
            );

            window.location.assign(responsePayload.redirect_url || "client-my-bookings.php");
        } catch (error) {
            submitButton.disabled = false;
            submitButton.textContent = "Submit Request";

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
    });
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
    const catalogApi = window.EmariohPackageCatalog;

    const requiredFields = [
        eventTypeField,
        eventTimeField,
        guestCountField,
        primaryContactField,
        primaryMobileField
    ];

    for (const field of requiredFields) {
        if (!field || !field.reportValidity()) {
            renderBookingSubmitFeedback(
                submitFeedback,
                "Please complete the required booking details before submitting.",
                "error"
            );
            return null;
        }
    }

    if (!eventDateField?.value) {
        renderBookingSubmitFeedback(
            submitFeedback,
            "Please choose an event date before submitting your request.",
            "error"
        );
        eventDateTrigger?.focus();
        return null;
    }

    if (usingOwnVenue && !venueField?.value.trim()) {
        renderBookingSubmitFeedback(
            submitFeedback,
            "Please provide the venue or event location before submitting.",
            "error"
        );
        venueField?.focus();
        return null;
    }

    if (!packageCategoryField?.value || !packageOptionField?.value) {
        renderBookingSubmitFeedback(
            submitFeedback,
            "Please choose a package before submitting your request.",
            "error"
        );
        packageOptionField?.focus();
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
    const mergedNotes = buildMergedBookingNotes(
        eventNotesField?.value || "",
        bookingNotesField?.value || ""
    );

    return {
        event_type: eventTypeField.value.trim(),
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

function buildMergedBookingNotes(eventNotes, bookingNotes) {
    const trimmedEventNotes = String(eventNotes || "").trim();
    const trimmedBookingNotes = String(bookingNotes || "").trim();

    if (trimmedEventNotes && trimmedBookingNotes) {
        return `Event Details:\n${trimmedEventNotes}\n\nAdditional Notes:\n${trimmedBookingNotes}`;
    }

    return trimmedEventNotes || trimmedBookingNotes;
}

async function postBookingJson(url, payload) {
    const response = await fetch(url, {
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

function renderBookingSubmitFeedback(element, message, tone = "info") {
    if (!element) {
        return;
    }

    element.textContent = message || "";
    element.dataset.state = tone;
    element.classList.remove(
        "booking-submit-feedback--error",
        "booking-submit-feedback--success",
        "booking-submit-feedback--info"
    );
    element.classList.add(`booking-submit-feedback--${tone}`);
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
