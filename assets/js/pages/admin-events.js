document.addEventListener("DOMContentLoaded", () => {
    const STATUS_META = {
        pending: {
            label: "Pending",
            filterLabel: "pending"
        },
        approved: {
            label: "Booked",
            filterLabel: "booked"
        },
        rejected: {
            label: "Cancelled",
            filterLabel: "cancelled"
        }
    };
    const VISIBLE_SCHEDULE_STATUSES = new Set(["pending", "approved"]);

    const MONTH_INDEX = {
        january: 1,
        february: 2,
        march: 3,
        april: 4,
        may: 5,
        june: 6,
        july: 7,
        august: 8,
        september: 9,
        october: 10,
        november: 11,
        december: 12
    };

    const filterButtons = document.querySelectorAll("[data-schedule-filter]");
    const defaultScheduleFilter = document.body.dataset.scheduleDefaultFilter || "all";
    const prevMonthButton = document.getElementById("scheduleMonthPrev");
    const nextMonthButton = document.getElementById("scheduleMonthNext");
    const monthLabel = document.getElementById("scheduleMonthLabel");
    const calendarDays = document.getElementById("eventCalendarDays");
    const bookingModalElement = document.getElementById("scheduleBookingModal");
    const bookingModalLabel = document.getElementById("scheduleBookingModalLabel");
    const bookingModalHeaderMeta = bookingModalElement?.querySelector(".schedule-modal__header-meta");
    const bookingModalBadge = document.getElementById("scheduleBookingModalBadge");
    const bookingModalSummary = document.getElementById("scheduleBookingModalSummary");
    const bookingModalList = document.getElementById("scheduleBookingModalList");
    const dashboardUpcomingButton = document.getElementById("dashboardUpcomingButton");
    const scheduleTableSummary = document.getElementById("scheduleTableSummary");
    const scheduleTopbarSummary = document.getElementById("scheduleTopbarSummary");
    const scheduleTableBody = document.getElementById("scheduleEventTableBody");
    const scheduleAgendaList = document.getElementById("scheduleAgendaList");
    const scheduleEmptyState = document.getElementById("scheduleEmptyState");
    const scheduleEmptyTitle = scheduleEmptyState?.querySelector("h4");
    const scheduleEmptyCopy = scheduleEmptyState?.querySelector("p");
    const scheduleDesktopTable = scheduleTableBody?.closest(".schedule-desktop-table");
    const dashboardTotalCount = document.getElementById("dashboardTotalCount");
    const dashboardPendingCount = document.getElementById("dashboardPendingCount");
    const dashboardBookedCount = document.getElementById("dashboardBookedCount");
    const dashboardCancelledCount = document.getElementById("dashboardCancelledCount");
    const dashboardTotalNote = document.getElementById("dashboardTotalNote");
    const dashboardCalendarHint = document.getElementById("dashboardCalendarHint");
    const scheduleSelectedDateTitle = document.getElementById("scheduleSelectedDateTitle");
    const scheduleSelectedDateMeta = document.getElementById("scheduleSelectedDateMeta");
    const scheduleSelectedDateBadge = document.getElementById("scheduleSelectedDateBadge");
    const scheduleSelectedDateList = document.getElementById("scheduleSelectedDateList");
    const scheduleSelectedDateEmpty = document.getElementById("scheduleSelectedDateEmpty");
    const scheduleSelectedDateEmptyTitle = scheduleSelectedDateEmpty?.querySelector("h4");
    const scheduleSelectedDateEmptyCopy = scheduleSelectedDateEmpty?.querySelector("p");
    const dashboardUpcomingSummary = document.getElementById("dashboardUpcomingSummary");
    const dashboardUpcomingList = document.getElementById("dashboardUpcomingList");
    const dashboardUpcomingEmpty = document.getElementById("dashboardUpcomingEmpty");
    const dashboardUpcomingEmptyTitle = dashboardUpcomingEmpty?.querySelector("h4");
    const dashboardUpcomingEmptyCopy = dashboardUpcomingEmpty?.querySelector("p");
    const hasCalendar = Boolean(calendarDays && monthLabel);
    const hasEventList = Boolean(scheduleTableSummary && scheduleTableBody && scheduleAgendaList && scheduleEmptyState);
    const hasDashboardSummary = Boolean(
        dashboardTotalCount
        && dashboardPendingCount
        && dashboardBookedCount
        && dashboardCancelledCount
        && dashboardTotalNote
    );
    const hasSelectedDatePanel = Boolean(
        scheduleSelectedDateTitle
        && scheduleSelectedDateMeta
        && scheduleSelectedDateBadge
        && scheduleSelectedDateList
        && scheduleSelectedDateEmpty
    );
    const selectedDatePanelMediaQuery = typeof window.matchMedia === "function"
        ? window.matchMedia("(max-width: 767.98px)")
        : null;
    const hasDashboardUpcoming = Boolean(
        dashboardUpcomingSummary
        && dashboardUpcomingList
        && dashboardUpcomingEmpty
    );
    const hasBookingModal = Boolean(
        bookingModalElement
        && bookingModalLabel
        && bookingModalBadge
        && bookingModalSummary
        && bookingModalList
        && window.bootstrap?.Modal
    );
    const bookingModal = hasBookingModal ? new window.bootstrap.Modal(bookingModalElement) : null;


    let scheduleEvents = [];
    let activeFilter = Array.from(filterButtons)
        .find((button) => button.classList.contains("is-active"))
        ?.dataset.scheduleFilter || defaultScheduleFilter;
    let currentMonth = getMonthStart(new Date());
    let selectedDate = toISODate(currentMonth);

    if (!["all", "pending", "approved"].includes(activeFilter)) {
        activeFilter = defaultScheduleFilter === "approved" ? "approved" : "all";
    }

    function escapeHtml(value = "") {
        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/\"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    function parseISODate(isoDate) {
        const [year, month, day] = isoDate.split("-").map(Number);
        return new Date(year, month - 1, day);
    }

    function startOfDay(date) {
        return new Date(date.getFullYear(), date.getMonth(), date.getDate());
    }

    function getMonthStart(date) {
        return new Date(date.getFullYear(), date.getMonth(), 1);
    }

    function toISODate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");
        return `${year}-${month}-${day}`;
    }

    function formatMonthYear(date) {
        return date.toLocaleDateString("en-PH", {
            month: "long",
            year: "numeric"
        });
    }

    function formatLongDate(isoDate) {
        return parseISODate(isoDate).toLocaleDateString("en-PH", {
            month: "long",
            day: "numeric",
            year: "numeric"
        });
    }

    function formatShortDate(isoDate) {
        return parseISODate(isoDate).toLocaleDateString("en-PH", {
            month: "short",
            day: "numeric",
            year: "numeric"
        });
    }

    function isSameMonth(date, monthDate) {
        return date.getFullYear() === monthDate.getFullYear() && date.getMonth() === monthDate.getMonth();
    }

    function getStatusMeta(status) {
        return STATUS_META[status] || STATUS_META.pending;
    }

    function normalizeStatus(statusValue = "") {
        const normalized = statusValue.trim().toLowerCase();

        if (normalized === "approved" || normalized === "booked") {
            return "approved";
        }

        if (normalized === "rejected" || normalized === "cancelled" || normalized === "canceled") {
            return "rejected";
        }

        return "pending";
    }

    function isCalendarVisibleEvent(event) {
        return Boolean(event && VISIBLE_SCHEDULE_STATUSES.has(event.status));
    }

    function parseDisplayDate(dateLabel = "") {
        const trimmedDate = dateLabel.trim();
        const matchedDate = trimmedDate.match(/^([A-Za-z]+)\s+(\d{1,2}),\s*(\d{4})$/);

        if (!matchedDate) {
            const parsedDate = new Date(trimmedDate);
            return Number.isNaN(parsedDate.getTime()) ? "" : toISODate(parsedDate);
        }

        const [, monthName, dayValue, yearValue] = matchedDate;
        const monthNumber = MONTH_INDEX[monthName.toLowerCase()];

        if (!monthNumber) {
            return "";
        }

        return `${yearValue}-${String(monthNumber).padStart(2, "0")}-${String(Number(dayValue)).padStart(2, "0")}`;
    }

    function parseTimeValue(timeLabel = "") {
        const matchedTime = timeLabel.trim().match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);

        if (!matchedTime) {
            return Number.MAX_SAFE_INTEGER;
        }

        let [, hourValue, minuteValue, period] = matchedTime;
        let hourNumber = Number(hourValue);
        const minuteNumber = Number(minuteValue);

        if (period.toUpperCase() === "PM" && hourNumber !== 12) {
            hourNumber += 12;
        }

        if (period.toUpperCase() === "AM" && hourNumber === 12) {
            hourNumber = 0;
        }

        return (hourNumber * 60) + minuteNumber;
    }

    function matchesFilter(event) {
        if (!isCalendarVisibleEvent(event)) {
            return false;
        }

        if (activeFilter === "all") {
            return true;
        }

        return event.status === activeFilter;
    }

    function sortEvents(events) {
        return [...events].sort((left, right) => {
            if (left.date === right.date) {
                return left.timeSort - right.timeSort;
            }

            return left.date.localeCompare(right.date);
        });
    }

    function getReferenceDate() {
        return startOfDay(new Date());
    }

    function isUpcomingEvent(event) {
        return startOfDay(parseISODate(event.date)) >= getReferenceDate();
    }

    function getMonthEvents(monthDate = currentMonth) {
        return sortEvents(
            scheduleEvents.filter((event) => isSameMonth(parseISODate(event.date), monthDate))
        );
    }

    function getVisibleMonthScheduleEvents(monthDate = currentMonth) {
        return sortEvents(
            scheduleEvents.filter((event) => isCalendarVisibleEvent(event) && isSameMonth(parseISODate(event.date), monthDate))
        );
    }

    function getFilteredEvents() {
        return sortEvents(scheduleEvents.filter((event) => matchesFilter(event)));
    }

    function getVisibleMonthEvents() {
        return getFilteredEvents().filter((event) => isSameMonth(parseISODate(event.date), currentMonth));
    }

    function getUpcomingEvents() {
        return getFilteredEvents().filter((event) => isUpcomingEvent(event));
    }

    function getEventsForDate(dateISO, sourceEvents = getVisibleMonthEvents()) {
        return sourceEvents.filter((event) => event.date === dateISO);
    }

    function getEventByReference(reference, sourceEvents = scheduleEvents) {
        return sourceEvents.find((event) => event.reference === reference) || null;
    }

    function getDefaultSelectedDate(monthDate) {
        const monthEvents = getVisibleMonthScheduleEvents(monthDate);
        return monthEvents[0]?.date || toISODate(monthDate);
    }

    function getStatusCounts(events) {
        return events.reduce((counts, event) => {
            counts.total += 1;
            counts[event.status] += 1;
            return counts;
        }, {
            total: 0,
            pending: 0,
            approved: 0,
            rejected: 0
        });
    }

    function syncSelectedDate() {
        if (!hasCalendar) {
            return;
        }

        const monthEvents = getVisibleMonthEvents();
        const monthStartISO = toISODate(currentMonth);
        const selectedDateObject = parseISODate(selectedDate);

        if (!isSameMonth(selectedDateObject, currentMonth)) {
            selectedDate = monthEvents[0]?.date || monthStartISO;
            return;
        }

        const selectedDateEvents = getEventsForDate(selectedDate, monthEvents);

        if (!selectedDateEvents.length && monthEvents.length) {
            selectedDate = monthEvents[0].date;
        }
    }

    function getDateSummary(dateISO) {
        const dateEvents = getEventsForDate(dateISO);
        const activeEvents = dateEvents.filter((event) => event.status !== "rejected");

        if (!dateEvents.length) {
            return {
                badgeClass: "open",
                badgeLabel: "Open",
                countLabel: "",
                hasEvent: false
            };
        }

        if (dateEvents.every((event) => event.status === "rejected")) {
            return {
                badgeClass: "rejected",
                badgeLabel: getStatusMeta("rejected").label,
                countLabel: `${dateEvents.length} item${dateEvents.length === 1 ? "" : "s"}`,
                hasEvent: true
            };
        }

        if (dateEvents.some((event) => event.status === "approved")) {
            return {
                badgeClass: "approved",
                badgeLabel: getStatusMeta("approved").label,
                countLabel: `${activeEvents.length} item${activeEvents.length === 1 ? "" : "s"}`,
                hasEvent: true
            };
        }

        return {
            badgeClass: "pending",
            badgeLabel: getStatusMeta("pending").label,
            countLabel: `${activeEvents.length} item${activeEvents.length === 1 ? "" : "s"}`,
            hasEvent: true
        };
    }

    function createCalendarButton(date, isMuted = false) {
        const dateISO = toISODate(date);
        const summary = getDateSummary(dateISO);
        const button = document.createElement("button");

        button.type = "button";
        button.className = "event-calendar__day";
        button.setAttribute("aria-pressed", String(selectedDate === dateISO));
        button.dataset.date = dateISO;

        if (isMuted) {
            button.classList.add("event-calendar__day--muted");
            button.disabled = true;
            button.innerHTML = `<span class="event-calendar__date">${date.getDate()}</span>`;
            return button;
        }

        if (selectedDate === dateISO) {
            button.classList.add("event-calendar__day--selected");
        }

        if (!summary.hasEvent) {
            button.classList.add("event-calendar__day--empty");
        }

        if (dateISO === toISODate(new Date())) {
            button.classList.add("event-calendar__day--today");
        }

        const summaryMarkup = summary.hasEvent
            ? `
                <div class="event-calendar__summary">
                    <span class="event-calendar__pill event-calendar__pill--${summary.badgeClass}">${summary.badgeLabel}</span>
                    <span class="event-calendar__count">${summary.countLabel}</span>
                </div>
            `
            : "";

        button.innerHTML = `
            <span class="event-calendar__date">${date.getDate()}</span>
            ${summaryMarkup}
        `;

        button.addEventListener("click", () => {
            selectedDate = dateISO;
            renderSchedule();

            if (!shouldUseSelectedDatePanel() && summary.hasEvent) {
                openBookingModal(dateISO);
            }
        });

        return button;
    }

    function renderCalendar() {
        if (!hasCalendar) {
            return;
        }

        const year = currentMonth.getFullYear();
        const month = currentMonth.getMonth();
        const firstDayOfMonth = new Date(year, month, 1);
        const leadingDays = firstDayOfMonth.getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const previousMonthDays = new Date(year, month, 0).getDate();
        const totalCells = Math.ceil((leadingDays + daysInMonth) / 7) * 7;

        monthLabel.textContent = formatMonthYear(currentMonth);
        calendarDays.innerHTML = "";

        for (let index = 0; index < totalCells; index += 1) {
            if (index < leadingDays) {
                const day = previousMonthDays - leadingDays + index + 1;
                calendarDays.appendChild(createCalendarButton(new Date(year, month - 1, day), true));
                continue;
            }

            const dayNumber = index - leadingDays + 1;

            if (dayNumber <= daysInMonth) {
                calendarDays.appendChild(createCalendarButton(new Date(year, month, dayNumber)));
                continue;
            }

            const trailingDay = dayNumber - daysInMonth;
            calendarDays.appendChild(createCalendarButton(new Date(year, month + 1, trailingDay), true));
        }
    }

    function setStatusBadge(element, summary) {
        if (!element) {
            return;
        }

        element.className = "schedule-day-panel__badge";
        element.classList.add(`schedule-day-panel__badge--${summary.badgeClass}`);
        element.textContent = summary.badgeLabel;
    }

    function getBookingHref(reference) {
        return `admin-bookings.php#${encodeURIComponent(reference)}`;
    }

    function hasPinnedScheduleFilter() {
        return filterButtons.length <= 1 && activeFilter !== "all";
    }

    function getTableSummaryText(count) {
        if (hasPinnedScheduleFilter()) {
            return `${count} upcoming event${count === 1 ? "" : "s"}`;
        }

        if (activeFilter === "all") {
            return `${count} upcoming event${count === 1 ? "" : "s"}`;
        }

        return `${count} upcoming ${getStatusMeta(activeFilter).filterLabel} event${count === 1 ? "" : "s"}`;
    }

    function getEmptyStateText() {
        if (hasPinnedScheduleFilter()) {
            if (activeFilter === "approved") {
                return {
                    title: "No upcoming events",
                    copy: "Approved bookings will appear here once they are confirmed."
                };
            }

            const filterLabel = getStatusMeta(activeFilter).filterLabel;

            return {
                title: "No upcoming events",
                copy: `There are no upcoming ${filterLabel} schedules to show right now.`
            };
        }

        if (activeFilter === "all") {
            return {
                title: "No upcoming events",
                copy: "There are no upcoming bookings to show right now."
            };
        }

        const filterLabel = getStatusMeta(activeFilter).filterLabel;

        return {
            title: `No upcoming ${filterLabel} events`,
            copy: `Try another filter to view other upcoming ${filterLabel} schedules.`
        };
    }

    function getSelectedDateEmptyStateText(hasOtherBookings = false) {
        if (activeFilter === "all") {
            return {
                title: "No bookings on this day",
                copy: "Choose another date or month to review scheduled events."
            };
        }

        const filterLabel = getStatusMeta(activeFilter).filterLabel;

        if (hasOtherBookings) {
            return {
                title: `No ${filterLabel} bookings`,
                copy: `This date has bookings, but none match the current ${filterLabel} filter.`
            };
        }

        return {
            title: `No ${filterLabel} bookings`,
            copy: `No ${filterLabel} bookings are scheduled for this date.`
        };
    }

    function getDashboardCalendarHintText(count) {
        const monthText = formatMonthYear(currentMonth);

        if (count === 0) {
            if (activeFilter === "all") {
                return `No bookings listed for ${monthText}`;
            }

            return `No ${getStatusMeta(activeFilter).filterLabel} bookings for ${monthText}`;
        }

        if (activeFilter === "all") {
            return `${count} booking${count === 1 ? "" : "s"} listed for ${monthText}`;
        }

        return `${count} ${getStatusMeta(activeFilter).filterLabel} booking${count === 1 ? "" : "s"} listed for ${monthText}`;
    }

    function getDashboardUpcomingSummaryText(totalCount, visibleCount) {
        if (totalCount <= visibleCount) {
            return getTableSummaryText(totalCount);
        }

        const summaryLabel = activeFilter === "all"
            ? "upcoming events"
            : `upcoming ${getStatusMeta(activeFilter).filterLabel} events`;

        return `Next ${visibleCount} of ${totalCount} ${summaryLabel}`;
    }

    function getUpcomingModalSummaryText(count) {
        if (activeFilter === "all") {
            return `${count} upcoming event${count === 1 ? "" : "s"}`;
        }

        return `${count} upcoming ${getStatusMeta(activeFilter).filterLabel} event${count === 1 ? "" : "s"}`;
    }

    function createViewActionMarkup(reference) {
        return `
            <button class="action-btn action-btn--primary schedule-table-action" type="button" data-schedule-view="${escapeHtml(reference)}">
                View
            </button>
        `;
    }

    function createTableRowMarkup(event) {
        const statusMeta = getStatusMeta(event.status);

        return `
            <tr>
                <td>
                    <strong>${escapeHtml(formatShortDate(event.date))}</strong>
                    <span>${escapeHtml(event.time)}</span>
                </td>
                <td>
                    <strong>${escapeHtml(event.client)}</strong>
                    <span>${escapeHtml(event.reference)}</span>
                </td>
                <td>
                    <strong>${escapeHtml(event.eventType)}</strong>
                    <span>${escapeHtml(event.packageName)}</span>
                </td>
                <td>
                    <strong>${escapeHtml(event.venue)}</strong>
                    <span>${escapeHtml(event.guests)}</span>
                </td>
                <td>
                    <span class="status-pill status-pill--${escapeHtml(event.status)}">${escapeHtml(statusMeta.label)}</span>
                </td>
                <td>${createViewActionMarkup(event.reference)}</td>
            </tr>
        `;
    }

    function createAgendaCardMarkup(event) {
        const statusMeta = getStatusMeta(event.status);

        return `
            <article class="schedule-agenda-card">
                <div class="schedule-agenda-card__top">
                    <div>
                        <p class="schedule-agenda-card__date">${escapeHtml(formatShortDate(event.date))} | ${escapeHtml(event.time)}</p>
                        <h4 class="schedule-agenda-card__title">${escapeHtml(event.eventType)}</h4>
                        <p class="schedule-agenda-card__client">${escapeHtml(event.client)} | ${escapeHtml(event.reference)}</p>
                    </div>
                    <span class="status-pill status-pill--${escapeHtml(event.status)}">${escapeHtml(statusMeta.label)}</span>
                </div>
                <p class="schedule-agenda-card__client">${escapeHtml(event.packageName)} | ${escapeHtml(event.guests)}</p>
                <p class="schedule-agenda-card__venue"><i class="bi bi-geo-alt"></i><span>${escapeHtml(event.venue)}</span></p>
                <div class="schedule-agenda-card__actions">
                    ${createViewActionMarkup(event.reference)}
                </div>
            </article>
        `;
    }

    function createDayPanelCardMarkup(event) {
        const statusMeta = getStatusMeta(event.status);

        return `
            <article class="schedule-day-card">
                <div class="schedule-day-card__top">
                    <div>
                        <p class="schedule-day-card__time">${escapeHtml(event.time)}</p>
                        <h4 class="schedule-day-card__title">${escapeHtml(event.eventType)}</h4>
                        <p class="schedule-day-card__client">${escapeHtml(event.client)} | ${escapeHtml(event.reference)}</p>
                    </div>
                    <span class="status-pill status-pill--${escapeHtml(event.status)}">${escapeHtml(statusMeta.label)}</span>
                </div>
                <div class="schedule-modal__meta">
                    <p class="schedule-modal__meta-item"><i class="bi bi-journal-richtext"></i><span>${escapeHtml(event.packageName)}</span></p>
                    <p class="schedule-modal__meta-item"><i class="bi bi-people"></i><span>${escapeHtml(event.guests)}</span></p>
                </div>
                <p class="schedule-day-card__venue"><i class="bi bi-geo-alt"></i><span>${escapeHtml(event.venue)}</span></p>
            </article>
        `;
    }

    function createModalCardMarkup(event, {
        showCardStatus = false,
        compact = false
    } = {}) {
        const statusMeta = getStatusMeta(event.status);
        const metaMarkup = compact
            ? ""
            : `
                <div class="schedule-modal__meta">
                    <p class="schedule-modal__meta-item"><i class="bi bi-journal-richtext"></i><span>${escapeHtml(event.packageName)}</span></p>
                    <p class="schedule-modal__meta-item"><i class="bi bi-people"></i><span>${escapeHtml(event.guests)}</span></p>
                </div>
            `;

        return `
            <article class="schedule-day-card schedule-modal__card${compact ? " schedule-modal__card--compact" : ""}">
                <div class="schedule-day-card__top">
                    <div>
                        <p class="schedule-day-card__time">${escapeHtml(event.time)}</p>
                        <h4 class="schedule-day-card__title">${escapeHtml(event.eventType)}</h4>
                        <p class="schedule-day-card__client">${escapeHtml(event.client)} | ${escapeHtml(event.reference)}</p>
                    </div>
                    ${showCardStatus ? `<span class="status-pill status-pill--${escapeHtml(event.status)}">${escapeHtml(statusMeta.label)}</span>` : ""}
                </div>
                ${metaMarkup}
                <p class="schedule-day-card__venue"><i class="bi bi-geo-alt"></i><span>${escapeHtml(event.venue)}</span></p>
            </article>
        `;
    }

    function buildScheduleEvent(button, row) {
        const detailSource = button?.dataset?.reference ? button : row;

        if (!detailSource) {
            return null;
        }

        const status = normalizeStatus(detailSource.dataset.statusClass || row?.dataset.bookingStatus || "pending");
        const isoDate = parseDisplayDate(detailSource.dataset.eventDate || "");
        const reference = detailSource.dataset.reference?.trim() || "";

        if (!isoDate || !reference) {
            return null;
        }

        return {
            reference,
            date: isoDate,
            time: detailSource.dataset.eventTime?.trim() || "Time not set",
            timeSort: parseTimeValue(detailSource.dataset.eventTime || ""),
            client: detailSource.dataset.clientName?.trim() || "Client not available",
            eventType: detailSource.dataset.eventType?.trim() || "Event not available",
            packageName: detailSource.dataset.package?.trim() || "Package not available",
            venue: detailSource.dataset.venue?.trim() || "Venue not available",
            guests: detailSource.dataset.guestCount?.trim() || "Guest count not available",
            status
        };
    }

    function normalizeInjectedScheduleEvent(event) {
        if (!event || !event.reference || !event.date) {
            return null;
        }

        return {
            reference: String(event.reference || "").trim(),
            date: String(event.date || "").trim(),
            time: String(event.time || "Time not set").trim(),
            timeSort: Number.isFinite(Number(event.timeSort))
                ? Number(event.timeSort)
                : parseTimeValue(String(event.time || "")),
            client: String(event.client || "Client not available").trim(),
            eventType: String(event.eventType || "Event not available").trim(),
            packageName: String(event.packageName || "Package not available").trim(),
            venue: String(event.venue || "Venue not available").trim(),
            guests: String(event.guests || "Guest count not available").trim(),
            status: normalizeStatus(String(event.status || "pending"))
        };
    }

    function extractScheduleEventsFromMarkup(markup) {
        const parser = new DOMParser();
        const bookingDocument = parser.parseFromString(markup, "text/html");
        const bookingRows = Array.from(bookingDocument.querySelectorAll("[data-booking-row]"));

        return sortEvents(
            bookingRows
                .map((row) => buildScheduleEvent(row.querySelector("[data-booking-view]"), row))
                .filter(Boolean)
        );
    }

    async function loadScheduleEvents() {
        if (Array.isArray(window.EMARIOH_SCHEDULE_EVENTS)) {
            return sortEvents(
                window.EMARIOH_SCHEDULE_EVENTS
                    .map((event) => normalizeInjectedScheduleEvent(event))
                    .filter(Boolean)
            );
        }

        try {
            const response = await fetch("admin-bookings.php", { cache: "no-store" });

            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            const markup = await response.text();
            const bookingEvents = extractScheduleEventsFromMarkup(markup);

            if (bookingEvents.length) {
                return bookingEvents;
            }
        } catch (error) {
            console.warn("Unable to load booking events for the schedule page.", error);
        }

        return [];
    }

    function renderBookingModal({
        title,
        summaryText,
        badge,
        events,
        showCardStatus = false,
        compact = false,
        showHeaderMeta = true
    }) {
        bookingModalLabel.textContent = title;
        if (bookingModalHeaderMeta) {
            bookingModalHeaderMeta.hidden = !showHeaderMeta;
        }
        bookingModalSummary.textContent = summaryText || "";
        bookingModalSummary.hidden = !summaryText || !showHeaderMeta;
        bookingModalBadge.hidden = !badge || !showHeaderMeta;

        if (badge && showHeaderMeta) {
            setStatusBadge(bookingModalBadge, badge);
        }

        bookingModalList.innerHTML = events.map((event) => createModalCardMarkup(event, {
            showCardStatus,
            compact
        })).join("");
        bookingModal.show();
    }

    function openBookingModal(dateISO) {
        if (!hasBookingModal) {
            return;
        }

        const dateEvents = getEventsForDate(dateISO, getVisibleMonthEvents());

        if (!dateEvents.length) {
            return;
        }

        const summary = getDateSummary(dateISO);
        const showCardStatus = dateEvents.length > 1;

        renderBookingModal({
            title: formatLongDate(dateISO),
            summaryText: `${dateEvents.length} event${dateEvents.length === 1 ? "" : "s"}`,
            badge: summary,
            events: dateEvents,
            showCardStatus
        });
    }

    function openEventDetailsModal(reference) {
        if (!hasBookingModal || !reference) {
            return;
        }

        const event = getEventByReference(reference, getUpcomingEvents()) || getEventByReference(reference);

        if (!event) {
            return;
        }

        currentMonth = getMonthStart(parseISODate(event.date));
        selectedDate = event.date;
        renderSchedule();

        renderBookingModal({
            title: event.eventType,
            summaryText: `${formatLongDate(event.date)} | ${event.time}`,
            badge: {
                badgeClass: event.status,
                badgeLabel: getStatusMeta(event.status).label
            },
            events: [event]
        });
    }

    function openUpcomingEventsModal() {
        if (!hasBookingModal) {
            return;
        }

        const upcomingEvents = getUpcomingEvents();

        if (!upcomingEvents.length) {
            return;
        }

        renderBookingModal({
            title: "Next Events",
            summaryText: "",
            badge: null,
            events: upcomingEvents,
            showCardStatus: true,
            compact: true,
            showHeaderMeta: false
        });
    }

    function renderUpcomingButtonState() {
        if (!dashboardUpcomingButton) {
            return;
        }

        const upcomingCount = getUpcomingEvents().length;

        dashboardUpcomingButton.disabled = upcomingCount === 0;
        dashboardUpcomingButton.setAttribute(
            "aria-label",
            upcomingCount === 0
                ? "No upcoming events available"
                : `${getUpcomingModalSummaryText(upcomingCount)}. Open next events`
        );
    }

    function renderDashboardSummary() {
        if (!hasDashboardSummary) {
            return;
        }

        const monthEvents = getMonthEvents(currentMonth);
        const counts = getStatusCounts(monthEvents);
        const monthText = formatMonthYear(currentMonth);

        dashboardTotalCount.textContent = String(counts.total);
        dashboardPendingCount.textContent = String(counts.pending);
        dashboardBookedCount.textContent = String(counts.approved);
        dashboardCancelledCount.textContent = String(counts.rejected);
        dashboardTotalNote.textContent = counts.total
            ? `For ${monthText}`
            : `No schedules for ${monthText}`;
    }

    function renderSelectedDatePanel() {
        if (!shouldUseSelectedDatePanel()) {
            return;
        }

        const filteredDateEvents = getEventsForDate(selectedDate, getVisibleMonthEvents());
        const monthDateEvents = getEventsForDate(selectedDate, getVisibleMonthScheduleEvents(currentMonth));
        const summary = getDateSummary(selectedDate);
        const emptyStateText = getSelectedDateEmptyStateText(monthDateEvents.length > 0);

        scheduleSelectedDateTitle.textContent = formatLongDate(selectedDate);
        setStatusBadge(scheduleSelectedDateBadge, summary);
        scheduleSelectedDateList.innerHTML = filteredDateEvents.map((event) => createDayPanelCardMarkup(event)).join("");

        if (filteredDateEvents.length) {
            scheduleSelectedDateMeta.textContent = activeFilter === "all"
                ? `${filteredDateEvents.length} booking${filteredDateEvents.length === 1 ? "" : "s"} scheduled for this date.`
                : `${filteredDateEvents.length} ${getStatusMeta(activeFilter).filterLabel} booking${filteredDateEvents.length === 1 ? "" : "s"} scheduled for this date.`;
            scheduleSelectedDateList.hidden = false;
            scheduleSelectedDateEmpty.hidden = true;
            return;
        }

        scheduleSelectedDateMeta.textContent = emptyStateText.copy;
        scheduleSelectedDateList.hidden = true;
        scheduleSelectedDateEmpty.hidden = false;

        if (scheduleSelectedDateEmptyTitle) {
            scheduleSelectedDateEmptyTitle.textContent = emptyStateText.title;
        }

        if (scheduleSelectedDateEmptyCopy) {
            scheduleSelectedDateEmptyCopy.textContent = emptyStateText.copy;
        }
    }

    function renderDashboardUpcoming() {
        if (!hasDashboardUpcoming) {
            return;
        }

        const visibleEvents = getUpcomingEvents();
        const previewEvents = visibleEvents.slice(0, 4);
        const emptyStateText = getEmptyStateText();

        dashboardUpcomingSummary.textContent = getDashboardUpcomingSummaryText(visibleEvents.length, previewEvents.length);
        dashboardUpcomingList.innerHTML = previewEvents.map((event) => createAgendaCardMarkup(event)).join("");
        dashboardUpcomingList.hidden = previewEvents.length === 0;
        dashboardUpcomingEmpty.hidden = previewEvents.length !== 0;

        if (dashboardUpcomingEmptyTitle) {
            dashboardUpcomingEmptyTitle.textContent = emptyStateText.title;
        }

        if (dashboardUpcomingEmptyCopy) {
            dashboardUpcomingEmptyCopy.textContent = emptyStateText.copy;
        }
    }

    function renderSchedule() {
        if (!hasCalendar) {
            return;
        }

        syncSelectedDate();
        renderCalendar();

        if (dashboardCalendarHint) {
            dashboardCalendarHint.textContent = getDashboardCalendarHintText(getVisibleMonthEvents().length);
        }

        renderDashboardSummary();
        renderUpcomingButtonState();
        renderSelectedDatePanel();
        renderDashboardUpcoming();
    }

    function shouldUseSelectedDatePanel() {
        return hasSelectedDatePanel && !selectedDatePanelMediaQuery?.matches;
    }

    function renderEventList() {
        if (!hasEventList) {
            return;
        }

        const visibleEvents = getUpcomingEvents();
        const hasEvents = visibleEvents.length > 0;
        const emptyStateText = getEmptyStateText();

        const summaryText = getTableSummaryText(visibleEvents.length);

        scheduleTableSummary.textContent = summaryText;
        if (scheduleTopbarSummary) {
            scheduleTopbarSummary.textContent = summaryText;
            scheduleTopbarSummary.hidden = false;
        }
        scheduleTableBody.innerHTML = visibleEvents.map((event) => createTableRowMarkup(event)).join("");
        scheduleAgendaList.innerHTML = visibleEvents.map((event) => createAgendaCardMarkup(event)).join("");

        if (scheduleEmptyTitle) {
            scheduleEmptyTitle.textContent = emptyStateText.title;
        }

        if (scheduleEmptyCopy) {
            scheduleEmptyCopy.textContent = emptyStateText.copy;
        }

        scheduleEmptyState.hidden = hasEvents;

        if (scheduleDesktopTable) {
            scheduleDesktopTable.hidden = !hasEvents;
        }

        scheduleAgendaList.hidden = !hasEvents;
    }

    async function initializeSchedule() {
        scheduleEvents = await loadScheduleEvents();
        currentMonth = getMonthStart(getReferenceDate());
        selectedDate = getDefaultSelectedDate(currentMonth);
        renderSchedule();
        renderEventList();
    }

    filterButtons.forEach((button) => {
        button.addEventListener("click", () => {
            activeFilter = button.dataset.scheduleFilter || "all";

            filterButtons.forEach((item) => {
                const isActive = item === button;
                item.classList.toggle("is-active", isActive);
                item.setAttribute("aria-pressed", String(isActive));
            });

            renderSchedule();
            renderEventList();
        });
    });

    function handleScheduleViewAction(clickEvent) {
        const viewTrigger = clickEvent.target.closest("[data-schedule-view]");

        if (!viewTrigger) {
            return;
        }

        clickEvent.preventDefault();
        openEventDetailsModal(viewTrigger.dataset.scheduleView || "");
    }

    scheduleTableBody?.addEventListener("click", handleScheduleViewAction);
    scheduleAgendaList?.addEventListener("click", handleScheduleViewAction);
    dashboardUpcomingList?.addEventListener("click", handleScheduleViewAction);
    dashboardUpcomingButton?.addEventListener("click", openUpcomingEventsModal);

    if (selectedDatePanelMediaQuery) {
        const handleSelectedDatePanelViewportChange = () => {
            renderSchedule();
        };

        if (typeof selectedDatePanelMediaQuery.addEventListener === "function") {
            selectedDatePanelMediaQuery.addEventListener("change", handleSelectedDatePanelViewportChange);
        } else if (typeof selectedDatePanelMediaQuery.addListener === "function") {
            selectedDatePanelMediaQuery.addListener(handleSelectedDatePanelViewportChange);
        }
    }

    prevMonthButton?.addEventListener("click", () => {
        currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() - 1, 1);
        selectedDate = toISODate(currentMonth);
        renderSchedule();
    });

    nextMonthButton?.addEventListener("click", () => {
        currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 1);
        selectedDate = toISODate(currentMonth);
        renderSchedule();
    });

    initializeSchedule();
});






