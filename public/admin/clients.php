<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';

$db = emarioh_db();
$currentUser = emarioh_require_page_role('admin');
$clients = $db->query("
    SELECT
        u.*,
        cp.alternate_contact AS profile_alternate_contact,
        cp.preferred_contact AS profile_preferred_contact,
        cp.notes AS profile_notes,
        cp.last_activity_at
    FROM users u
    LEFT JOIN client_profiles cp
        ON cp.user_id = u.id
    WHERE u.role = 'client'
    ORDER BY u.full_name ASC, u.id ASC
")->fetchAll();
$allBookings = emarioh_fetch_booking_requests($db, [
    'order_by' => 'submitted_desc',
]);

$bookingsByUserId = [];

foreach ($allBookings as $booking) {
    $userId = (int) ($booking['user_id'] ?? 0);

    if ($userId <= 0) {
        continue;
    }

    $bookingsByUserId[$userId] ??= [];
    $bookingsByUserId[$userId][] = $booking;
}

$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

function emarioh_admin_clients_date_label(?string $value): string
{
    $timestamp = strtotime((string) $value);
    return $timestamp === false ? 'No activity yet' : date('F j, Y', $timestamp);
}

function emarioh_admin_clients_datetime_label(?string $value): string
{
    $timestamp = strtotime((string) $value);
    return $timestamp === false ? 'Not available' : date('F j, Y | g:i A', $timestamp);
}

function emarioh_admin_clients_client_since_label(array $client): string
{
    $createdAt = (int) ($client['created_at'] ?? 0);

    if ($createdAt <= 0) {
        return 'Not available';
    }

    return date('F Y', $createdAt);
}

function emarioh_admin_clients_total_bookings_label(int $count): string
{
    if ($count <= 0) {
        return 'No active booking';
    }

    return $count === 1 ? '1 booking' : $count . ' bookings';
}

function emarioh_admin_clients_summary_bookings_label(int $count): string
{
    if ($count <= 0) {
        return '0 bookings';
    }

    return $count === 1 ? '1 booking' : $count . ' total bookings';
}

function emarioh_admin_clients_preferred_contact(array $client, string $mobile): string
{
    $preferredContact = trim((string) ($client['profile_preferred_contact'] ?? ''));

    if ($preferredContact !== '') {
        return $preferredContact;
    }

    if ($mobile !== '') {
        return 'Mobile';
    }

    return 'Not specified';
}

function emarioh_admin_clients_last_activity_value(array $client, ?array $latestBooking): ?string
{
    $profileActivity = trim((string) ($client['last_activity_at'] ?? ''));
    $bookingActivity = '';

    if ($latestBooking !== null) {
        $bookingActivity = trim((string) (($latestBooking['updated_at'] ?? '') ?: ($latestBooking['submitted_at'] ?? '')));
    }

    $profileTimestamp = strtotime($profileActivity);
    $bookingTimestamp = strtotime($bookingActivity);

    if ($profileTimestamp === false) {
        return $bookingTimestamp === false ? null : $bookingActivity;
    }

    if ($bookingTimestamp === false) {
        return $profileActivity;
    }

    return $profileTimestamp >= $bookingTimestamp ? $profileActivity : $bookingActivity;
}

function emarioh_admin_clients_notes(array $client, ?array $latestBooking): string
{
    $notes = [];
    $profileNotes = trim((string) ($client['profile_notes'] ?? ''));
    $eventNotes = trim((string) ($latestBooking['event_notes'] ?? ''));
    $adminNotes = trim((string) ($latestBooking['admin_notes'] ?? ''));

    if ($profileNotes !== '') {
        $notes[] = $profileNotes;
    }

    if ($eventNotes !== '') {
        $notes[] = $eventNotes;
    }

    if ($adminNotes !== '') {
        $notes[] = 'Admin notes: ' . $adminNotes;
    }

    return $notes === [] ? 'No notes provided.' : implode("\n\n", $notes);
}

function emarioh_admin_clients_history(array $clientBookings, string $latestReference = ''): string
{
    $historyEntries = [];
    $todayTimestamp = strtotime(date('Y-m-d'));

    foreach ($clientBookings as $booking) {
        $reference = (string) ($booking['reference'] ?? '');
        $status = (string) ($booking['status'] ?? 'pending_review');
        $eventDate = (string) ($booking['event_date'] ?? '');
        $eventTimestamp = strtotime($eventDate);

        if ($reference === $latestReference) {
            continue;
        }

        if (!in_array($status, ['approved', 'completed'], true)) {
            continue;
        }

        if ($status === 'approved' && ($eventTimestamp === false || $eventTimestamp >= $todayTimestamp)) {
            continue;
        }

        $historyEntries[] = implode('^', [
            $eventTimestamp === false ? 'Date not provided' : date('F j, Y', $eventTimestamp),
            trim((string) ($booking['event_type'] ?? 'Completed Event')) ?: 'Completed Event',
            trim((string) ($booking['package_label'] ?? 'Package not provided')) ?: 'Package not provided',
            trim((string) ($booking['venue_name'] ?? 'Venue not provided')) ?: 'Venue not provided',
            'Completed',
        ]);
    }

    return implode('||', $historyEntries);
}

function emarioh_admin_clients_event_schedule(?array $booking): string
{
    if ($booking === null) {
        return 'No event date yet';
    }

    $eventDate = trim((string) ($booking['event_date'] ?? ''));
    $eventTime = trim((string) ($booking['event_time'] ?? ''));
    $dateTimeValue = trim($eventDate . ' ' . $eventTime);

    return emarioh_admin_clients_datetime_label($dateTimeValue);
}

function emarioh_render_admin_client_rows(array $clients, array $bookingsByUserId, callable $escape): string
{
    if ($clients === []) {
        return '<tr><td colspan="5" class="text-center text-secondary">No clients found.</td></tr>';
    }

    $rows = [];

    foreach ($clients as $client) {
        $userId = (int) ($client['id'] ?? 0);
        $clientBookings = array_values($bookingsByUserId[$userId] ?? []);
        $latestBooking = $clientBookings[0] ?? null;
        $totalBookings = count($clientBookings);
        $clientName = trim((string) ($client['full_name'] ?? 'Client'));
        $mobile = emarioh_format_mobile((string) ($client['mobile'] ?? ''));
        $preferredContact = emarioh_admin_clients_preferred_contact($client, $mobile);
        $alternateContact = trim((string) ($client['profile_alternate_contact'] ?? ''));
        $alternateContact = $alternateContact !== '' ? $alternateContact : 'Not provided';
        $lastBookingName = trim((string) ($latestBooking['event_type'] ?? ''));
        $lastBookingName = $lastBookingName !== '' ? $lastBookingName : 'No active booking';
        $lastReference = trim((string) ($latestBooking['reference'] ?? ''));
        $lastReference = $lastReference !== '' ? $lastReference : 'No booking reference';
        $venue = trim((string) ($latestBooking['venue_name'] ?? ''));
        $venue = $venue !== '' ? $venue : 'No venue submitted';
        $clientSince = emarioh_admin_clients_client_since_label($client);
        $lastActivityValue = emarioh_admin_clients_last_activity_value($client, $latestBooking);
        $lastActivityDate = emarioh_admin_clients_date_label($lastActivityValue);
        $lastActivitySummary = emarioh_admin_clients_datetime_label($lastActivityValue);
        $status = (string) ($latestBooking['status'] ?? '');
        $statusLabel = $latestBooking !== null ? emarioh_booking_status_label($status) : 'Inactive';
        $statusClass = $latestBooking !== null ? emarioh_booking_admin_status_class($status) : 'inactive';
        $rowStatus = $latestBooking !== null ? emarioh_booking_filter_key($status) : 'inactive';
        $notes = emarioh_admin_clients_notes($client, $latestBooking);
        $history = emarioh_admin_clients_history($clientBookings, (string) ($latestBooking['reference'] ?? ''));
        $bookingHref = $latestBooking !== null && $lastReference !== 'No booking reference'
            ? 'admin-bookings.php#' . rawurlencode($lastReference)
            : 'admin-bookings.php';
        $searchIndex = strtolower(implode(' ', array_filter([
            $clientName,
            $mobile,
            $lastBookingName,
            $lastReference,
        ])));

        $rows[] = '
            <tr class="client-table__row" data-client-row data-client-status="' . $escape($rowStatus) . '" data-client-search="' . $escape($searchIndex) . '">
                <td class="client-table__cell client-table__cell--client"><span class="client-table__primary">' . $escape($clientName) . '</span><span class="client-table__meta">' . $escape(emarioh_admin_clients_total_bookings_label($totalBookings)) . '</span></td>
                <td class="client-table__cell client-table__cell--contact"><span class="client-table__primary">' . $escape($mobile !== '' ? $mobile : 'No mobile number') . '</span><span class="client-table__meta">Preferred: ' . $escape($preferredContact) . '</span></td>
                <td class="client-table__cell client-table__cell--booking"><span class="client-table__primary">' . $escape($lastBookingName) . '</span><span class="client-table__meta">' . $escape($lastReference) . '</span></td>
                <td class="client-table__cell client-table__cell--activity"><span class="client-table__plain">' . $escape($lastActivityDate) . '</span></td>
                <td class="client-table__cell client-table__cell--action">
                    <button
                        class="action-btn client-table__action"
                        type="button"
                        data-client-view
                        data-client-name="' . $escape($clientName) . '"
                        data-mobile="' . $escape($mobile !== '' ? $mobile : 'No mobile number') . '"
                        data-alternate-contact="' . $escape($alternateContact) . '"
                        data-client-since="' . $escape($clientSince) . '"
                        data-preferred-contact="' . $escape($preferredContact) . '"
                        data-total-bookings="' . $escape(emarioh_admin_clients_summary_bookings_label($totalBookings)) . '"
                        data-last-booking="' . $escape($lastBookingName) . '"
                        data-last-reference="' . $escape($lastReference) . '"
                        data-last-event-date="' . $escape(emarioh_admin_clients_event_schedule($latestBooking)) . '"
                        data-venue="' . $escape($venue) . '"
                        data-last-activity="' . $escape($lastActivitySummary) . '"
                        data-status-label="' . $escape($statusLabel) . '"
                        data-status-class="' . $escape($statusClass) . '"
                        data-notes="' . $escape($notes) . '"
                        data-history="' . $escape($history) . '"
                        data-booking-href="' . $escape($bookingHref) . '"
                    >View</button>
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
    <title>Emarioh Catering Services Clients</title>
    <?= emarioh_render_vendor_head_assets(); ?>
    <link rel="stylesheet" href="assets/css/index.css?v=20260418o">
    <link rel="stylesheet" href="assets/css/pages/admin-clients.css?v=20260418c">
</head>
<body class="admin-dashboard-page admin-clients-page" data-auth-guard="admin">
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
                            <a class="nav-link active" href="admin-clients.php"><span class="nav-link__icon"><i class="bi bi-people"></i></span><span>Clients</span></a>
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
                            <h1 class="topbar-copy__title">Clients</h1>
                        </div>
                    </div>
                </header>

                <main class="dashboard-content">
                    <section class="dashboard-primary">
                        <section class="surface-card clients-screen">
                            <div class="admin-toolbar admin-toolbar--clients justify-content-end">
                                <label class="admin-search" for="clientSearchInput">
                                    <span class="admin-search__icon"><i class="bi bi-search"></i></span>
                                    <input class="admin-search__input" id="clientSearchInput" type="search" placeholder="Search client, contact, or booking">
                                </label>
                            </div>

                            <div class="table-responsive dashboard-table-wrap dashboard-table-wrap--clients">
                                <table class="admin-table admin-table--clients">
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Contact</th>
                                            <th>Latest Booking</th>
                                            <th>Last Activity</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?= emarioh_render_admin_client_rows(is_array($clients) ? $clients : [], $bookingsByUserId, $escape) ?>
                                    </tbody>
                                </table>
                            </div>

                            <p class="booking-filter-empty" data-client-empty hidden>No clients found.</p>
                        </section>
                    </section>
                </main>

                <?= emarioh_render_admin_mobile_nav(basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'))) ?>
            </div>
        </div>
    </div>

    <div class="modal fade booking-modal booking-details-modal" id="clientDetailsModal" tabindex="-1" aria-labelledby="clientDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content booking-modal__content">
                <div class="modal-header booking-modal__header booking-details-modal__header">
                    <div class="booking-details-modal__heading">
                        <p class="panel-heading__eyebrow">Client Details</p>
                        <h2 class="booking-modal__title" id="clientDetailsModalLabel">Client Name</h2>
                        <p class="booking-details-modal__subtitle" id="clientDetailsLastBooking">Latest Booking</p>
                        <p class="booking-details-modal__summary" id="clientDetailsSummary">Client since Month Year | 0 bookings | Last activity not available</p>
                    </div>
                    <div class="booking-details-modal__header-actions">
                        <span class="status-pill status-pill--pending" id="clientDetailsStatus">Pending</span>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>

                <div class="modal-body booking-modal__body">
                    <div class="booking-details-grid">
                        <section class="booking-details-card">
                            <h3 class="booking-details-card__title">Contact</h3>
                            <dl class="booking-details-list">
                                <div><dt>Mobile</dt><dd id="clientDetailsMobile">0917 000 0000</dd></div>
                                <div><dt>Alternate Contact</dt><dd id="clientDetailsAlternateContact">Not provided</dd></div>
                                <div><dt>Preferred Contact</dt><dd id="clientDetailsPreferredContact">Mobile</dd></div>
                            </dl>
                        </section>

                        <section class="booking-details-card">
                            <h3 class="booking-details-card__title">Latest Booking</h3>
                            <dl class="booking-details-list">
                                <div><dt>Booking</dt><dd id="clientDetailsBookingName">Booking</dd></div>
                                <div><dt>Reference</dt><dd id="clientDetailsReference">Reference</dd></div>
                                <div><dt>Event Date</dt><dd id="clientDetailsEventDate">Event date</dd></div>
                                <div><dt>Venue</dt><dd id="clientDetailsVenue">Venue</dd></div>
                            </dl>
                        </section>

                        <section class="booking-details-card booking-details-card--full">
                            <h3 class="booking-details-card__title">Completed Events</h3>
                            <div class="client-history-list" id="clientDetailsHistory"></div>
                            <p class="client-history-empty" id="clientDetailsHistoryEmpty" hidden>No completed events yet.</p>
                        </section>
                    </div>
                </div>

                <div class="modal-footer booking-modal__footer">
                    <a class="action-btn action-btn--primary text-decoration-none" href="admin-bookings.php" id="clientDetailsBookingLink">Open Booking</a>
                    <button type="button" class="action-btn" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?= emarioh_render_vendor_runtime_assets(true); ?>
    <script src="assets/js/auth-api.js"></script>
    <script src="assets/js/logout-confirmation.js"></script>
    <script src="assets/js/pages/index.js?v=20260418d"></script>
</body>
</html>
