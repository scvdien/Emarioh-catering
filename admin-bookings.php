<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$db = emarioh_db();
$currentUser = emarioh_require_page_role('admin');
$bookings = emarioh_fetch_booking_requests($db, [
    'order_by' => 'submitted_desc',
]);

$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

$noticeMessage = '';
$noticeClass = 'success';

if (isset($_GET['error'])) {
    $noticeMessage = 'The booking status could not be updated right now. Please try again.';
    $noticeClass = 'danger';
}

function emarioh_admin_datetime_label(?string $value): string
{
    $timestamp = strtotime((string) $value);

    if ($timestamp === false) {
        return 'Not provided';
    }

    return date('F j, Y | g:i A', $timestamp);
}

function emarioh_admin_date_label(?string $value): string
{
    $timestamp = strtotime((string) $value);
    return $timestamp === false ? 'Date pending' : date('F j, Y', $timestamp);
}

function emarioh_admin_time_label(?string $value): string
{
    $timestamp = strtotime((string) $value);
    return $timestamp === false ? 'Time pending' : date('g:i A', $timestamp);
}

function emarioh_admin_booking_notes(array $booking): string
{
    $notes = [];
    $eventNotes = trim((string) ($booking['event_notes'] ?? ''));
    $adminNotes = trim((string) ($booking['admin_notes'] ?? ''));

    if ($eventNotes !== '') {
        $notes[] = $eventNotes;
    }

    if ($adminNotes !== '') {
        $notes[] = 'Admin notes: ' . $adminNotes;
    }

    return $notes === [] ? 'No notes provided.' : implode("\n\n", $notes);
}

function emarioh_render_admin_booking_rows(array $bookings, callable $escape): string
{
    if ($bookings === []) {
        return '<tr><td colspan="5" class="text-center text-secondary">No booking requests yet.</td></tr>';
    }

    $rows = [];

    foreach ($bookings as $booking) {
        $status = (string) ($booking['status'] ?? 'pending_review');
        $statusLabel = emarioh_booking_status_label($status);
        $statusClass = emarioh_booking_admin_status_class($status);
        $filterKey = emarioh_booking_filter_key($status);
        $reference = (string) ($booking['reference'] ?? 'BK-TBA');
        $clientName = (string) ($booking['primary_contact'] ?? 'Client');
        $mobile = emarioh_format_mobile((string) ($booking['primary_mobile'] ?? ''));
        $eventType = (string) ($booking['event_type'] ?? 'Event');
        $eventDate = emarioh_admin_date_label((string) ($booking['event_date'] ?? ''));
        $eventTime = emarioh_admin_time_label((string) ($booking['event_time'] ?? ''));
        $packageLabel = trim((string) ($booking['package_label'] ?? '')) ?: 'Package to follow';
        $venueOptionLabel = emarioh_booking_venue_option_label((string) ($booking['venue_option'] ?? 'own'));
        $venueName = (string) ($booking['venue_name'] ?? 'Venue to follow');
        $submittedAt = emarioh_admin_datetime_label((string) ($booking['submitted_at'] ?? ''));
        $guestCount = (int) ($booking['guest_count'] ?? 0);
        $notes = emarioh_admin_booking_notes($booking);
        $actions = '
            <button
                class="action-btn booking-row-actions__view"
                type="button"
                data-booking-view
                aria-label="View booking ' . $escape($reference) . ' for ' . $escape($clientName) . '"
            >View</button>
        ';

        if ($status === 'pending_review') {
            $actions .= '
                <button class="action-btn action-btn--primary" type="button" data-booking-admin-action="approved" data-booking-id="' . (int) $booking['id'] . '" data-booking-reference="' . $escape($reference) . '">Approve</button>
                <button class="action-btn action-btn--soft" type="button" data-booking-admin-action="rejected" data-booking-id="' . (int) $booking['id'] . '" data-booking-reference="' . $escape($reference) . '">Reject</button>
            ';
        } elseif (emarioh_can_send_final_event_reminder($booking)) {
            $actions .= '
                <button class="action-btn action-btn--soft" type="button" data-booking-message-action="final_event_reminder" data-booking-id="' . (int) $booking['id'] . '" data-booking-reference="' . $escape($reference) . '">Final Reminder</button>
            ';
        }

        $rows[] = '
            <tr
                class="admin-booking-row"
                data-booking-row
                data-booking-status="' . $escape($filterKey) . '"
                data-reference="' . $escape($reference) . '"
                data-submitted-at="' . $escape($submittedAt) . '"
                data-client-name="' . $escape($clientName) . '"
                data-mobile="' . $escape($mobile) . '"
                data-event-type="' . $escape($eventType) . '"
                data-event-date="' . $escape($eventDate) . '"
                data-event-time="' . $escape($eventTime) . '"
                data-guest-count="' . $escape($guestCount > 0 ? $guestCount . ' pax' : 'TBA') . '"
                data-venue-option="' . $escape($venueOptionLabel) . '"
                data-venue="' . $escape($venueName) . '"
                data-package="' . $escape($packageLabel) . '"
                data-status-label="' . $escape($statusLabel) . '"
                data-status-class="' . $escape($statusClass) . '"
                data-notes="' . $escape($notes) . '"
            >
                <td>
                    <span class="d-block fw-semibold">' . $escape($clientName) . '</span>
                    <span class="d-block text-secondary small fw-normal">' . $escape($mobile) . '</span>
                </td>
                <td>
                    <span class="d-block fw-semibold">' . $escape($reference) . '</span>
                    <span class="d-block text-secondary small fw-normal">Submitted ' . $escape(date('M j, Y', strtotime((string) ($booking['submitted_at'] ?? 'now')))) . '</span>
                </td>
                <td>
                    <span class="d-block fw-semibold">' . $escape($eventType) . '</span>
                    <span class="d-block text-secondary small fw-normal">' . $escape($eventDate . ' | ' . $eventTime) . '</span>
                    <span class="d-block text-secondary small fw-normal">' . $escape($packageLabel) . '</span>
                </td>
                <td><span class="status-pill status-pill--' . $escape($statusClass) . '">' . $escape($statusLabel) . '</span></td>
                <td>
                    <div class="booking-row-actions">
                        <div class="action-menu" data-action-menu>
                            <button class="action-menu__toggle" type="button" aria-expanded="false" aria-label="Open actions" data-action-menu-toggle>
                                <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                            </button>
                            <div class="table-actions action-menu__panel" data-action-menu-panel hidden>
                                ' . $actions . '
                            </div>
                        </div>
                    </div>
                </td>
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
    <title>Emarioh Catering Services Booking Management</title>
    <?= emarioh_render_vendor_head_assets(); ?>
    <link rel="stylesheet" href="assets/css/index.css?v=20260418o">
    <link rel="stylesheet" href="assets/css/admin-bookings.css?v=20260418h">
</head>
<body class="admin-dashboard-page admin-bookings-page" data-auth-guard="admin">
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
                            <a class="nav-link active" href="admin-bookings.php"><span class="nav-link__icon"><i class="bi bi-journal-check"></i></span><span>Booking Management</span></a>
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
                            <h1 class="topbar-copy__title">Booking Management</h1>
                        </div>
                        <div class="booking-status-select-wrap">
                            <label class="visually-hidden" for="bookingStatusSelect">Filter bookings</label>
                            <select class="form-select booking-status-select" id="bookingStatusSelect" aria-label="Filter bookings">
                                <option value="all">All</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                </header>

                <main class="dashboard-content">
                    <section class="dashboard-primary">
                        <section class="surface-card surface-card--booking-queue booking-queue-card">
                            <?php if ($noticeMessage !== ''): ?>
                                <div class="alert alert-<?= $escape($noticeClass) ?> mb-4" role="alert">
                                    <?= $escape($noticeMessage) ?>
                                </div>
                            <?php endif; ?>

                            <div class="booking-queue-toolbar">
                                <div class="booking-filters" aria-label="Quick booking filters">
                                    <button class="booking-filter-chip is-active" type="button" data-booking-filter="all" aria-pressed="true">All</button>
                                    <button class="booking-filter-chip" type="button" data-booking-filter="pending" aria-pressed="false">Pending</button>
                                    <button class="booking-filter-chip" type="button" data-booking-filter="approved" aria-pressed="false">Approved</button>
                                    <button class="booking-filter-chip" type="button" data-booking-filter="rejected" aria-pressed="false">Rejected</button>
                                    <button class="booking-filter-chip" type="button" data-booking-filter="cancelled" aria-pressed="false">Cancelled</button>
                                </div>
                            </div>

                            <div class="table-responsive dashboard-table-wrap booking-queue-table">
                                <table class="admin-table admin-table--bookings">
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Reference</th>
                                            <th>Event &amp; Package</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?= emarioh_render_admin_booking_rows($bookings, $escape) ?>
                                    </tbody>
                                </table>
                            </div>

                            <p class="booking-filter-empty" data-booking-empty hidden>No bookings found for this filter.</p>
                        </section>
                    </section>
                </main>

                <?= emarioh_render_admin_mobile_nav(basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'))) ?>
            </div>
        </div>
    </div>

    <div class="modal fade booking-modal booking-details-modal" id="bookingDetailsModal" tabindex="-1" aria-labelledby="bookingDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content booking-modal__content">
                <div class="modal-header booking-modal__header booking-details-modal__header">
                    <div class="booking-details-modal__heading">
                        <p class="panel-heading__eyebrow">Booking Details</p>
                        <h2 class="booking-modal__title" id="bookingDetailsModalLabel">Client Name</h2>
                        <p class="booking-details-modal__subtitle" id="bookingDetailsPackage">Package</p>
                    </div>
                    <div class="booking-details-modal__header-actions">
                        <span class="status-pill status-pill--pending" id="bookingDetailsStatus">Pending</span>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body booking-modal__body">
                    <div class="booking-details-grid">
                        <section class="booking-details-card">
                            <h3 class="booking-details-card__title">Request Summary</h3>
                            <dl class="booking-details-list">
                                <div><dt>Reference</dt><dd id="bookingDetailsReference">-</dd></div>
                                <div><dt>Submitted</dt><dd id="bookingDetailsSubmittedAt">-</dd></div>
                                <div><dt>Status</dt><dd id="bookingDetailsStatusText">-</dd></div>
                                <div><dt>Package</dt><dd id="bookingDetailsPackageSummary">-</dd></div>
                            </dl>
                        </section>

                        <section class="booking-details-card">
                            <h3 class="booking-details-card__title">Client Contact</h3>
                            <dl class="booking-details-list">
                                <div><dt>Client Name</dt><dd id="bookingDetailsClientName">-</dd></div>
                                <div><dt>Mobile Number</dt><dd id="bookingDetailsMobile">-</dd></div>
                            </dl>
                        </section>

                        <section class="booking-details-card">
                            <h3 class="booking-details-card__title">Event Details</h3>
                            <dl class="booking-details-list">
                                <div><dt>Event Type</dt><dd id="bookingDetailsEventType">-</dd></div>
                                <div><dt>Event Date</dt><dd id="bookingDetailsEventDate">-</dd></div>
                                <div><dt>Event Time</dt><dd id="bookingDetailsEventTime">-</dd></div>
                                <div><dt>Guest Count</dt><dd id="bookingDetailsGuestCount">-</dd></div>
                            </dl>
                        </section>

                        <section class="booking-details-card">
                            <h3 class="booking-details-card__title">Venue Details</h3>
                            <dl class="booking-details-list">
                                <div><dt>Venue Option</dt><dd id="bookingDetailsVenueOption">-</dd></div>
                                <div><dt>Venue / Location</dt><dd id="bookingDetailsVenue">-</dd></div>
                            </dl>
                        </section>

                        <section class="booking-details-card booking-details-card--full">
                            <h3 class="booking-details-card__title">Notes</h3>
                            <p class="booking-details-note" id="bookingDetailsNotes">No notes provided.</p>
                        </section>
                    </div>
                </div>
                <div class="modal-footer booking-modal__footer">
                    <button type="button" class="action-btn" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade booking-modal booking-action-modal" id="bookingActionConfirmModal" tabindex="-1" aria-labelledby="bookingActionConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered booking-action-modal__dialog">
            <div class="modal-content booking-modal__content booking-action-modal__content">
                <div class="modal-header booking-modal__header booking-action-modal__header">
                    <div class="booking-action-modal__heading">
                        <h2 class="booking-modal__title booking-action-modal__title" id="bookingActionConfirmModalLabel">Confirm Booking Update</h2>
                    </div>
                </div>
                <div class="modal-body booking-modal__body booking-action-modal__body">
                    <p class="booking-action-modal__text" id="bookingActionConfirmModalText">Confirm this booking action?</p>
                </div>
                <div class="modal-footer booking-modal__footer booking-action-modal__footer">
                    <button type="button" class="action-btn action-btn--ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="action-btn action-btn--primary" id="bookingActionConfirmButton">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <?= emarioh_render_vendor_runtime_assets(true); ?>
    <script src="assets/js/auth-api.js"></script>
    <script src="assets/js/logout-confirmation.js"></script>
    <script src="assets/js/index.js?v=20260418d"></script>
    <script src="assets/js/admin-bookings.js?v=20260417d"></script>
</body>
</html>
