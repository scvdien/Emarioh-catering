<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

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
    <title>Emarioh Catering Services Events and Schedule</title>
    <?= emarioh_render_vendor_head_assets(); ?>
    <link rel="stylesheet" href="assets/css/index.css?v=20260418o">
    <link rel="stylesheet" href="assets/css/admin-events.css?v=20260418o">
</head>
<body class="admin-dashboard-page admin-events-page" data-schedule-default-filter="approved" data-auth-guard="admin">
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
                            <a class="nav-link" href="admin-bookings.php"><span class="nav-link__icon"><i class="bi bi-journal-check"></i></span><span>Booking Management</span></a>
                            <a class="nav-link" href="admin-clients.php"><span class="nav-link__icon"><i class="bi bi-people"></i></span><span>Clients</span></a>
                            <a class="nav-link" href="admin-events.php"><span class="nav-link__icon"><i class="bi bi-calendar-event"></i></span><span>Event Schedule</span></a>
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
                            <h1 class="topbar-copy__title">Event Schedule</h1>
                        </div>
                        <p class="schedule-table-summary schedule-topbar-summary" id="scheduleTopbarSummary" hidden>0 upcoming events</p>
                    </div>
                </header>

                <main class="dashboard-content">
                    <section class="dashboard-primary">
                        <section class="surface-card surface-card--schedule-table">
                            <div class="panel-heading panel-heading--compact">
                                <h2>Upcoming Booked Events</h2>
                                <p class="schedule-table-summary" id="scheduleTableSummary">0 upcoming events</p>
                            </div>

                            <div class="schedule-workspace">

                                <div class="table-responsive dashboard-table-wrap schedule-desktop-table">
                                    <table class="admin-table admin-table--events">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Client</th>
                                                <th>Event</th>
                                                <th>Venue</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="scheduleEventTableBody"></tbody>
                                    </table>
                                </div>

                                <div class="schedule-agenda-list" id="scheduleAgendaList"></div>
                                <div class="schedule-empty-state" id="scheduleEmptyState" hidden>
                                    <h4>No upcoming events</h4>
                                    <p>Approved bookings will appear here once they are confirmed.</p>
                                </div>
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
    <script src="assets/js/index.js?v=20260417c"></script>
    <script src="assets/js/admin-events.js?v=20260418d"></script>
</body>
</html>



















