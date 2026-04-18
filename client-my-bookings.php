<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$db = emarioh_db();
$currentUser = emarioh_require_page_role('client');
$bookings = emarioh_fetch_booking_requests($db, [
    'user_id' => (int) $currentUser['id'],
    'order_by' => 'submitted_desc',
]);

$pendingBookings = array_values(array_filter($bookings, static fn (array $booking): bool => (string) $booking['status'] === 'pending_review'));
$approvedBookings = array_values(array_filter($bookings, static fn (array $booking): bool => in_array((string) $booking['status'], ['approved', 'completed'], true)));
$cancelledBookings = array_values(array_filter($bookings, static fn (array $booking): bool => (string) $booking['status'] === 'cancelled'));
$rejectedBookings = array_values(array_filter($bookings, static fn (array $booking): bool => (string) $booking['status'] === 'rejected'));

$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

$noticeMessage = '';
$noticeClass = 'success';

if (isset($_GET['cancelled'])) {
    $noticeMessage = 'Your booking request was cancelled.';
} elseif (isset($_GET['error'])) {
    $noticeMessage = 'The booking update could not be completed right now. Please try again.';
    $noticeClass = 'danger';
}

function emarioh_client_booking_datetime_label(array $booking): string
{
    $eventDate = strtotime((string) ($booking['event_date'] ?? ''));
    $eventTime = strtotime((string) ($booking['event_time'] ?? ''));

    if ($eventDate === false || $eventTime === false) {
        return 'Schedule pending';
    }

    return date('M j, Y', $eventDate) . ' - ' . date('g:i A', $eventTime);
}

function emarioh_render_client_booking_rows(array $bookings, string $emptyText, callable $escape): string
{
    if ($bookings === []) {
        return '<tr><td colspan="6">' . $escape($emptyText) . '</td></tr>';
    }

    $rows = [];

    foreach ($bookings as $booking) {
        $status = (string) ($booking['status'] ?? 'pending_review');
        $statusLabel = emarioh_booking_status_label($status);
        $statusClass = emarioh_booking_client_status_class($status);
        $reference = (string) ($booking['reference'] ?? 'BK-TBA');
        $eventType = (string) ($booking['event_type'] ?? 'Event');
        $guestCount = (int) ($booking['guest_count'] ?? 0);
        $venueName = (string) ($booking['venue_name'] ?? 'Venue to follow');
        $eventDateTime = emarioh_client_booking_datetime_label($booking);
        $canCancel = in_array($status, ['pending_review', 'approved'], true);

        if ($canCancel) {
            $cancelLabel = $status === 'approved' ? 'Cancel Booking' : 'Cancel Request';
            $actionMarkup = sprintf(
                '<button class="booking-request-action" type="button" data-booking-cancel data-booking-id="%d" data-booking-reference="%s">%s</button>',
                (int) $booking['id'],
                $escape($reference),
                $escape($cancelLabel)
            );
        } else {
            $actionMarkup = '<span class="booking-request-action booking-request-action--muted">Closed</span>';
        }

        $rows[] = '
            <tr>
                <td data-label="Event Date">
                    <span class="d-block fw-semibold">' . $escape($eventDateTime) . '</span>
                    <span class="d-block text-secondary small fw-normal">' . $escape($reference) . '</span>
                </td>
                <td data-label="Event Type">
                    <span class="d-block fw-semibold">' . $escape($eventType) . '</span>
                    <span class="d-block text-secondary small fw-normal">' . $escape((string) ($booking['package_label'] ?? 'Package to follow')) . '</span>
                </td>
                <td data-label="Guests">' . $escape($guestCount > 0 ? $guestCount . ' pax' : 'TBA') . '</td>
                <td data-label="Venue">' . $escape($venueName) . '</td>
                <td data-label="Status"><span class="status-pill status-pill--' . $escape($statusClass) . '">' . $escape($statusLabel) . '</span></td>
                <td data-label="Action">' . $actionMarkup . '</td>
            </tr>
        ';
    }

    return implode("\n", $rows);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emarioh Catering Services Client My Bookings</title>
    <?= emarioh_render_vendor_head_assets(); ?>
    <link rel="stylesheet" href="assets/css/client-bookings.css?v=20260418u">
    <link rel="stylesheet" href="assets/css/client-sidebar-parity.css?v=20260418e">
</head>
<body class="dashboard-page client-dashboard-page client-my-bookings-page client-page--sticky-topbar" data-auth-guard="client">
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
                            <span class="sidebar-brand__sub">Client Portal</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body p-0">
                    <div class="sidebar-inner d-flex flex-column h-100">
                        <div class="sidebar-brand">
                            <a href="client-dashboard.php" class="sidebar-brand__link text-decoration-none" id="dashboardSidebarLabel" aria-label="Emarioh Catering Services Client Portal">
                                <span class="sidebar-brand__frame"><img src="assets/images/logo.jpg" alt="Emarioh Catering Services" class="sidebar-brand__logo"></span>
                                <span class="sidebar-brand__copy"><span class="sidebar-brand__name">Emarioh</span><span class="sidebar-brand__sub">Client Portal</span></span>
                            </a>
                        </div>
                        <div class="sidebar-divider" aria-hidden="true"></div>
                        <nav class="dashboard-nav nav flex-column" aria-label="Client portal navigation">
                            <a class="nav-link" href="client-dashboard.php"><span class="nav-link__icon"><i class="bi bi-grid-1x2-fill"></i></span><span>Dashboard</span></a>
                            <a class="nav-link" href="client-bookings.php"><span class="nav-link__icon"><i class="bi bi-calendar2-plus"></i></span><span>Book Event</span></a>
                            <a class="nav-link active" href="client-my-bookings.php" aria-current="page"><span class="nav-link__icon"><i class="bi bi-calendar2-check"></i></span><span>My Bookings</span></a>
                            <a class="nav-link" href="client-billing.php"><span class="nav-link__icon"><i class="bi bi-receipt-cutoff"></i></span><span>Billing</span></a>
                            <a class="nav-link" href="client-preferences.php"><span class="nav-link__icon"><i class="bi bi-gear"></i></span><span>Account Settings</span></a>
                        </nav>
                        <div class="sidebar-footer mt-auto">
                            <a class="sidebar-logout" href="logout.php" data-logout-link><span class="sidebar-logout__icon"><i class="bi bi-box-arrow-right"></i></span><span class="sidebar-logout__label">Log out</span></a>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="dashboard-main">
                <header class="dashboard-topbar">
                    <div class="topbar-leading">
                        <button class="btn mobile-menu-button d-xl-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#dashboardSidebar" aria-controls="dashboardSidebar" aria-label="Open navigation"><i class="bi bi-list"></i></button>
                        <div class="topbar-copy"><h1 class="topbar-copy__title">My Bookings</h1></div>
                    </div>
                    <div class="booking-topbar-controls">
                        <label class="booking-status-select-wrap booking-status-select-wrap--topbar" for="bookingStatusSelect">
                            <select class="booking-status-select" id="bookingStatusSelect" aria-label="Filter bookings by status">
                                <option value="booking-pending-tab" selected>Pending</option>
                                <option value="booking-approved-tab">Approved</option>
                                <option value="booking-cancelled-tab">Cancelled</option>
                                <option value="booking-rejected-tab">Rejected</option>
                            </select>
                        </label>
                    </div>
                </header>

                <main class="dashboard-content client-dashboard-content">
                    <section class="surface-card booking-requests-card" id="bookingRequestsSection">
                        <?php if ($noticeMessage !== ''): ?>
                            <div class="alert alert-<?= $escape($noticeClass) ?> mb-4" role="alert">
                                <?= $escape($noticeMessage) ?>
                            </div>
                        <?php endif; ?>

                        <ul class="nav nav-pills booking-requests-tabs booking-requests-tabs--hidden" id="bookingStatusTabs" role="tablist" aria-hidden="true">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="booking-pending-tab" data-bs-toggle="pill" data-bs-target="#booking-pending-pane" type="button" role="tab" aria-controls="booking-pending-pane" aria-selected="true">Pending</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="booking-approved-tab" data-bs-toggle="pill" data-bs-target="#booking-approved-pane" type="button" role="tab" aria-controls="booking-approved-pane" aria-selected="false">Approved</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="booking-cancelled-tab" data-bs-toggle="pill" data-bs-target="#booking-cancelled-pane" type="button" role="tab" aria-controls="booking-cancelled-pane" aria-selected="false">Cancelled</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="booking-rejected-tab" data-bs-toggle="pill" data-bs-target="#booking-rejected-pane" type="button" role="tab" aria-controls="booking-rejected-pane" aria-selected="false">Rejected</button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="booking-pending-pane" role="tabpanel" aria-labelledby="booking-pending-tab" tabindex="0">
                                <div class="table-responsive dashboard-table-wrap booking-requests-table">
                                    <table class="admin-table admin-table--client-bookings">
                                        <thead>
                                            <tr>
                                                <th>Event Date</th>
                                                <th>Event Type</th>
                                                <th>Guests</th>
                                                <th>Venue</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="bookingPendingTableBody">
                                            <?= emarioh_render_client_booking_rows($pendingBookings, 'No pending booking request.', $escape) ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="booking-approved-pane" role="tabpanel" aria-labelledby="booking-approved-tab" tabindex="0">
                                <div class="table-responsive dashboard-table-wrap booking-requests-table">
                                    <table class="admin-table admin-table--client-bookings">
                                        <thead>
                                            <tr>
                                                <th>Event Date</th>
                                                <th>Event Type</th>
                                                <th>Guests</th>
                                                <th>Venue</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="bookingApprovedTableBody">
                                            <?= emarioh_render_client_booking_rows($approvedBookings, 'No approved booking yet.', $escape) ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="booking-cancelled-pane" role="tabpanel" aria-labelledby="booking-cancelled-tab" tabindex="0">
                                <div class="table-responsive dashboard-table-wrap booking-requests-table">
                                    <table class="admin-table admin-table--client-bookings">
                                        <thead>
                                            <tr>
                                                <th>Event Date</th>
                                                <th>Event Type</th>
                                                <th>Guests</th>
                                                <th>Venue</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="bookingCancelledTableBody">
                                            <?= emarioh_render_client_booking_rows($cancelledBookings, 'No cancelled booking yet.', $escape) ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="booking-rejected-pane" role="tabpanel" aria-labelledby="booking-rejected-tab" tabindex="0">
                                <div class="table-responsive dashboard-table-wrap booking-requests-table">
                                    <table class="admin-table admin-table--client-bookings">
                                        <thead>
                                            <tr>
                                                <th>Event Date</th>
                                                <th>Event Type</th>
                                                <th>Guests</th>
                                                <th>Venue</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="bookingRejectedTableBody">
                                            <?= emarioh_render_client_booking_rows($rejectedBookings, 'No rejected booking yet.', $escape) ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                <?= emarioh_render_client_mobile_nav(basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'client-dashboard.php'))) ?>
            </div>
        </div>
    </div>

    <div class="modal fade booking-modal" id="bookingCancelModal" tabindex="-1" aria-labelledby="bookingCancelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content booking-modal__content">
                <div class="modal-header booking-modal__header">
                    <div>
                        <p class="panel-heading__eyebrow">Confirmation</p>
                        <h2 class="booking-modal__title" id="bookingCancelModalLabel">Cancel Request</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body booking-modal__body">
                    <p class="booking-cancel-modal__text" id="bookingCancelModalText">Cancel this request? You can submit a new booking again after this.</p>
                </div>
                <div class="modal-footer booking-modal__footer">
                    <button type="button" class="client-action-button" data-bs-dismiss="modal">Keep It</button>
                    <button type="button" class="client-action-button client-action-button--primary" id="bookingCancelConfirmButton">Yes, Cancel Request</button>
                </div>
            </div>
        </div>
    </div>

    <?= emarioh_render_vendor_runtime_assets(true); ?>
    <script src="assets/js/auth-api.js"></script>
    <script src="assets/js/logout-confirmation.js?v=20260418a"></script>
    <script src="assets/js/client-dashboard.js"></script>
    <script src="assets/js/client-my-bookings-page.js?v=20260418b"></script>
</body>
</html>
