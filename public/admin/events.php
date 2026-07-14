<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';

$db = emarioh_db();
$currentUser = emarioh_require_page_role('admin');
$scheduleBookings = emarioh_fetch_booking_requests($db, [
    'order_by' => 'event_date_asc',
]);

$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

function emarioh_admin_events_status_value(string $status): string
{
    return match ($status) {
        'approved', 'completed' => 'approved',
        'rejected', 'cancelled' => 'rejected',
        default => 'pending',
    };
}

function emarioh_admin_events_time_label(?string $value): string
{
    $timestamp = strtotime((string) $value);
    return $timestamp === false ? 'Time not set' : date('g:i A', $timestamp);
}

function emarioh_admin_events_time_sort(?string $value): int
{
    $timestamp = strtotime((string) $value);

    if ($timestamp === false) {
        return 0;
    }

    return ((int) date('G', $timestamp) * 60) + (int) date('i', $timestamp);
}

$scheduleEvents = array_map(static function (array $booking): array {
    $guestCount = (int) ($booking['guest_count'] ?? 0);

    return [
        'reference' => (string) ($booking['reference'] ?? ''),
        'date' => (string) ($booking['event_date'] ?? ''),
        'time' => emarioh_admin_events_time_label((string) ($booking['event_time'] ?? '')),
        'timeSort' => emarioh_admin_events_time_sort((string) ($booking['event_time'] ?? '')),
        'client' => (string) ($booking['primary_contact'] ?? 'Client'),
        'eventType' => (string) ($booking['event_type'] ?? 'Event'),
        'packageName' => trim((string) ($booking['package_label'] ?? '')) ?: 'Package to follow',
        'venue' => (string) ($booking['venue_name'] ?? 'Venue to follow'),
        'guests' => $guestCount > 0 ? $guestCount . ' pax' : 'TBA',
        'status' => emarioh_admin_events_status_value((string) ($booking['status'] ?? 'pending_review')),
    ];
}, $scheduleBookings);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emarioh Catering Services Booking Calendar</title>
    <?= emarioh_render_vendor_head_assets(); ?>
    <link rel="stylesheet" href="assets/css/index.css?v=20260709b">
    <link rel="stylesheet" href="assets/css/pages/admin-events.css?v=20260709g">
    <link rel="stylesheet" href="assets/css/admin-mobile-notification.css?v=20260710d">
</head>
<body class="admin-dashboard-page admin-events-page" data-schedule-default-filter="all" data-auth-guard="admin">
    <div class="dashboard-shell container-fluid">
        <div class="dashboard-frame">
            <aside class="dashboard-sidebar offcanvas-xl offcanvas-start border-0" tabindex="-1" id="dashboardSidebar" aria-labelledby="dashboardSidebarLabel">
                <div class="offcanvas-header sidebar-mobile-header d-xl-none">
                    <div class="sidebar-mobile-brand">
                        <span class="sidebar-brand__frame sidebar-brand__frame--small">
                            <img src="assets/images/logo.jpg" alt="Emarioh Catering Services" class="sidebar-brand__logo">
                        </span>
                        <div class="sidebar-brand__copy">
                            <span class="sidebar-brand__name">Emarioh</span>
                            <span class="sidebar-brand__sub">Catering Services</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>

                <div class="offcanvas-body p-0">
                    <div class="sidebar-inner d-flex flex-column h-100">
                        <div class="sidebar-brand">
                            <a href="index.php" class="sidebar-brand__link text-decoration-none" id="dashboardSidebarLabel" aria-label="Emarioh Catering Services Admin Dashboard">
                                <span class="sidebar-brand__frame">
                                    <img src="assets/images/logo.jpg" alt="Emarioh Catering Services" class="sidebar-brand__logo">
                                </span>
                                <span class="sidebar-brand__copy">
                                    <span class="sidebar-brand__name">Emarioh</span>
                                    <span class="sidebar-brand__sub">Catering Services</span>
                                </span>
                            </a>
                        </div>

                        <div class="sidebar-divider" aria-hidden="true"></div>

                        <nav class="dashboard-nav nav flex-column" aria-label="Admin navigation">
                            <a class="nav-link" href="index.php"><span class="nav-link__icon"><i class="bi bi-grid-1x2-fill"></i></span><span>Dashboard</span></a>
                            <?= emarioh_render_admin_notification_nav_link($db) ?>
                            <a class="nav-link active" href="admin-events.php" aria-current="page"><span class="nav-link__icon"><i class="bi bi-calendar-event"></i></span><span>Booking Calendar</span></a>
                            <a class="nav-link" href="admin-bookings.php"><span class="nav-link__icon"><i class="bi bi-journal-check"></i></span><span>Booking Management</span></a>
                            <a class="nav-link" href="admin-clients.php"><span class="nav-link__icon"><i class="bi bi-people"></i></span><span>Clients</span></a>
                            <a class="nav-link" href="admin-payments.php"><span class="nav-link__icon"><i class="bi bi-wallet2"></i></span><span>Payment</span></a>
                            <a class="nav-link" href="admin-inquiries.php"><span class="nav-link__icon"><i class="bi bi-envelope-paper"></i></span><span>Website Inquiries</span></a>
                            <a class="nav-link" href="admin-settings.php"><span class="nav-link__icon"><i class="bi bi-gear"></i></span><span>Settings</span></a>
                        </nav>

                        <div class="sidebar-footer mt-auto">
                            <a class="sidebar-logout" href="logout.php">
                                <span class="sidebar-logout__icon">
                                    <i class="bi bi-box-arrow-right"></i>
                                </span>
                                <span class="sidebar-logout__label">Log out</span>
                            </a>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="dashboard-main">
                <header class="dashboard-topbar">
                    <div class="topbar-leading">
                        <button class="btn mobile-menu-button d-xl-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#dashboardSidebar" aria-controls="dashboardSidebar" aria-label="Open navigation">
                            <i class="bi bi-list"></i>
                        </button>

                        <div class="topbar-copy">
                            <h1 class="topbar-copy__title">Booking Calendar</h1>
                            <p class="schedule-calendar-summary" id="dashboardCalendarHint">No bookings listed for <?= $escape(date('F Y')) ?></p>
                        </div>
                    </div>
                    <?= emarioh_render_admin_mobile_notification_button($db) ?>
                </header>

                <main class="dashboard-content">
                    <section class="dashboard-primary">
                        <section class="surface-card surface-card--schedule booking-calendar-card">
                            <div class="schedule-toolbar" aria-label="Booking calendar controls">
                                <div class="booking-filters schedule-filters" aria-label="Schedule status filters">
                                    <button class="booking-filter-chip is-active" type="button" data-schedule-filter="all" aria-pressed="true">All</button>
                                    <button class="booking-filter-chip" type="button" data-schedule-filter="pending" aria-pressed="false">Pending</button>
                                    <button class="booking-filter-chip" type="button" data-schedule-filter="approved" aria-pressed="false">Booked</button>
                                </div>
                                <div class="schedule-toolbar__actions">
                                    <div class="schedule-month-nav" aria-label="Schedule month controls">
                                        <button class="schedule-icon-button" id="scheduleMonthPrev" type="button" aria-label="View previous month">
                                            <i class="bi bi-chevron-left"></i>
                                        </button>
                                        <div class="schedule-month-nav__copy">
                                            <strong id="scheduleMonthLabel"><?= $escape(date('F Y')) ?></strong>
                                        </div>
                                        <button class="schedule-icon-button" id="scheduleMonthNext" type="button" aria-label="View next month">
                                            <i class="bi bi-chevron-right"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="booking-calendar-layout">
                                <section class="schedule-calendar-panel" aria-label="Booking month view">
                                    <div class="schedule-calendar-panel__header">
                                        <div>
                                            <h3>Month View</h3>
                                        </div>
                                    </div>

                                    <div class="event-legend" aria-label="Calendar legend">
                                        <span class="event-legend__item"><span class="event-legend__dot event-legend__dot--approved"></span>Booked</span>
                                        <span class="event-legend__item"><span class="event-legend__dot event-legend__dot--pending"></span>Pending</span>
                                    </div>

                                    <div class="event-calendar-scroll">
                                        <div class="event-calendar">
                                            <div class="event-calendar__weekdays" aria-hidden="true">
                                                <span class="event-calendar__weekday">Sun</span>
                                                <span class="event-calendar__weekday">Mon</span>
                                                <span class="event-calendar__weekday">Tue</span>
                                                <span class="event-calendar__weekday">Wed</span>
                                                <span class="event-calendar__weekday">Thu</span>
                                                <span class="event-calendar__weekday">Fri</span>
                                                <span class="event-calendar__weekday">Sat</span>
                                            </div>
                                            <div class="event-calendar__days" id="eventCalendarDays" aria-live="polite"></div>
                                        </div>
                                    </div>
                                </section>

                            </div>
                        </section>
                    </section>
                </main>

                <?= emarioh_render_admin_mobile_nav(basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'))) ?>
            </div>
        </div>
    </div>

    <div class="modal fade schedule-modal" id="scheduleBookingModal" tabindex="-1" aria-labelledby="scheduleBookingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="schedule-modal__heading">
                        <h2 class="modal-title" id="scheduleBookingModalLabel">Schedule details</h2>
                        <div class="schedule-modal__header-meta">
                            <span class="schedule-day-panel__badge schedule-day-panel__badge--open" id="scheduleBookingModalBadge">Open</span>
                            <p class="schedule-modal__summary" id="scheduleBookingModalSummary">0 events</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="schedule-modal__list" id="scheduleBookingModalList"></div>
                </div>
            </div>
        </div>
    </div>

    <?= emarioh_render_vendor_runtime_assets(true); ?>
    <script src="assets/js/auth-api.js"></script>
    <script src="assets/js/logout-confirmation.js"></script>
    <script>
        window.EMARIOH_SCHEDULE_EVENTS = <?= json_encode($scheduleEvents, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="assets/js/pages/index.js?v=20260417c"></script>
    <script src="assets/js/pages/admin-events.js?v=20260709b"></script>
</body>
</html>



















