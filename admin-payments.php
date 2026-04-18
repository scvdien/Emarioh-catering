<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$db = emarioh_db();
$currentUser = emarioh_require_page_role('admin');
$approvedBookings = emarioh_fetch_booking_requests($db, [
    'statuses' => ['approved', 'completed'],
    'order_by' => 'submitted_desc',
]);
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

function emarioh_admin_payment_date_label(?string $value): string
{
    $normalizedValue = trim((string) $value);

    if ($normalizedValue === '') {
        return 'Date pending';
    }

    try {
        return (new DateTimeImmutable($normalizedValue))->format('F j, Y');
    } catch (Throwable $exception) {
        return 'Date pending';
    }
}

function emarioh_admin_payment_time_label(?string $value): string
{
    $normalizedValue = trim((string) $value);

    if ($normalizedValue === '') {
        return 'Time pending';
    }

    try {
        return (new DateTimeImmutable('1970-01-01 ' . $normalizedValue))->format('g:i A');
    } catch (Throwable $exception) {
        return 'Time pending';
    }
}

function emarioh_admin_payment_datetime_label(?string $value, string $fallback = 'Not provided'): string
{
    $normalizedValue = trim((string) $value);

    if ($normalizedValue === '') {
        return $fallback;
    }

    try {
        return (new DateTimeImmutable($normalizedValue))->format('F j, Y | g:i A');
    } catch (Throwable $exception) {
        return $fallback;
    }
}

function emarioh_admin_payment_status_meta(array $invoice, array $billingDetails): array
{
    $invoiceStatus = strtolower(trim((string) ($invoice['status'] ?? 'pending')));
    $statusClass = strtolower(trim((string) ($billingDetails['statusPillClass'] ?? $invoiceStatus)));
    $statusLabel = trim((string) ($billingDetails['statusText'] ?? 'Open'));
    $dueDateValue = trim((string) ($invoice['due_date'] ?? ''));
    $isOverdue = false;

    if ($dueDateValue !== '' && in_array($invoiceStatus, ['pending', 'review'], true)) {
        try {
            $dueDate = new DateTimeImmutable($dueDateValue);
            $today = new DateTimeImmutable('today');
            $isOverdue = $dueDate < $today;
        } catch (Throwable $exception) {
            $isOverdue = false;
        }
    }

    if ($invoiceStatus === 'cancelled') {
        return [
            'class' => 'inactive',
            'label' => 'Cancelled',
            'filter' => 'inactive',
        ];
    }

    if ($isOverdue) {
        return [
            'class' => 'rejected',
            'label' => 'Overdue',
            'filter' => 'rejected',
        ];
    }

    if ($statusClass === '') {
        $statusClass = match ($invoiceStatus) {
            'approved' => 'approved',
            'review' => 'review',
            'rejected' => 'rejected',
            default => 'pending',
        };
    }

    if ($statusLabel === '') {
        $statusLabel = match ($statusClass) {
            'approved' => 'Paid',
            'review' => 'Partially Paid',
            'rejected' => 'Overdue',
            default => 'Open',
        };
    }

    return [
        'class' => $statusClass,
        'label' => $statusLabel,
        'filter' => in_array($statusClass, ['pending', 'review', 'approved', 'rejected'], true) ? $statusClass : 'all',
    ];
}

function emarioh_admin_payment_file_href(?string $value): string
{
    $normalizedValue = trim((string) $value);

    if ($normalizedValue === '') {
        return '';
    }

    if (preg_match('/^https?:\/\//i', $normalizedValue) === 1) {
        return $normalizedValue;
    }

    $normalizedValue = str_replace('\\', '/', $normalizedValue);
    $basePath = str_replace('\\', '/', EMARIOH_BASE_PATH);

    if (preg_match('/^[A-Za-z]:\//', $normalizedValue) === 1) {
        if (stripos($normalizedValue, $basePath) !== 0) {
            return '';
        }

        $normalizedValue = substr($normalizedValue, strlen($basePath));
    }

    return ltrim($normalizedValue, '/');
}

function emarioh_admin_payment_preview_href(?array $receipt): string
{
    $receiptHref = emarioh_admin_payment_file_href((string) ($receipt['stored_file_path'] ?? ''));

    if ($receiptHref === '') {
        return '';
    }

    $extension = strtolower(pathinfo($receiptHref, PATHINFO_EXTENSION));
    $previewableExtensions = ['avif', 'bmp', 'gif', 'jpeg', 'jpg', 'png', 'svg', 'webp'];

    return in_array($extension, $previewableExtensions, true) ? $receiptHref : '';
}

function emarioh_admin_payment_receipt_href(array $invoice, ?array $receipt, array $billingDetails): string
{
    $storedFileHref = emarioh_admin_payment_file_href((string) ($receipt['stored_file_path'] ?? ''));

    if ($storedFileHref !== '') {
        return $storedFileHref;
    }

    $receiptHref = trim((string) ($billingDetails['receiptHref'] ?? ''));

    if ($receiptHref !== '') {
        return $receiptHref;
    }

    return max(0, (float) ($invoice['amount_paid'] ?? 0)) > 0
        ? 'client-payment-receipt.php?invoice=' . rawurlencode((string) ($invoice['invoice_number'] ?? ''))
        : '';
}

function emarioh_render_admin_payment_rows(PDO $db, array $bookings, callable $escape): string
{
    if ($bookings === []) {
        return '<tr><td colspan="7" class="text-center text-secondary">No payment records yet.</td></tr>';
    }

    $rows = [];

    foreach ($bookings as $booking) {
        $package = emarioh_find_service_package_for_booking($db, $booking);
        $invoice = emarioh_find_payment_invoice_by_booking($db, (int) ($booking['id'] ?? 0));

        if ($invoice === null) {
            $invoice = emarioh_ensure_payment_invoice_for_booking(
                $db,
                $booking,
                $package,
                (int) ($booking['reviewed_by_user_id'] ?? 0) > 0 ? (int) $booking['reviewed_by_user_id'] : null
            );
        }

        if ($invoice === null) {
            continue;
        }

        $billingDetails = emarioh_build_client_portal_billing_details($db, $booking, $package);

        if ($billingDetails === null) {
            continue;
        }

        $invoice = emarioh_find_payment_invoice_by_booking($db, (int) ($booking['id'] ?? 0)) ?? $invoice;
        $receipt = emarioh_find_payment_receipt_by_invoice($db, (int) ($invoice['id'] ?? 0));
        $logs = emarioh_fetch_payment_logs_for_invoice($db, (int) ($invoice['id'] ?? 0));
        $latestLog = $logs !== [] ? $logs[array_key_last($logs)] : null;
        $statusMeta = emarioh_admin_payment_status_meta($invoice, $billingDetails);
        $invoiceNumber = (string) ($billingDetails['invoiceNumber'] ?? $invoice['invoice_number'] ?? 'INV-TBA');
        $bookingReference = (string) ($booking['reference'] ?? 'BK-TBA');
        $clientName = trim((string) ($booking['primary_contact'] ?? 'Client'));
        $eventName = trim((string) ($billingDetails['description'] ?? $invoice['title'] ?? $booking['event_type'] ?? 'Booking Payment'));
        $eventSchedule = emarioh_admin_payment_date_label((string) ($booking['event_date'] ?? ''))
            . ' | '
            . emarioh_admin_payment_time_label((string) ($booking['event_time'] ?? ''));
        $amountLabel = (string) ($billingDetails['totalAmount'] ?? emarioh_format_money_amount((float) ($invoice['amount_due'] ?? 0)));
        $totalPaidLabel = (string) ($billingDetails['totalPaid'] ?? emarioh_format_money_amount((float) ($invoice['amount_paid'] ?? 0)));
        $pendingBalanceLabel = (string) ($billingDetails['pendingBalance'] ?? emarioh_format_money_amount((float) ($invoice['balance_due'] ?? 0)));
        $lastPaymentLabel = emarioh_admin_payment_datetime_label(
            (string) ($billingDetails['lastPayment'] ?? $invoice['last_payment_at'] ?? ''),
            'No payment posted yet'
        );
        $loggedOnLabel = emarioh_admin_payment_datetime_label(
            (string) ($latestLog['created_at'] ?? $invoice['last_payment_at'] ?? $invoice['created_at'] ?? ''),
            'Not provided'
        );
        $loggedOnParts = explode(' | ', $loggedOnLabel, 2);
        $loggedOnDate = $loggedOnParts[0] ?? $loggedOnLabel;
        $loggedOnTime = $loggedOnParts[1] ?? '';
        $paymentUpdateTitle = trim((string) ($latestLog['title'] ?? $invoice['stage_label'] ?? 'Awaiting Payment'));
        $paymentUpdateSummary = trim((string) ($latestLog['summary'] ?? $latestLog['notes'] ?? $invoice['note_text'] ?? ''));

        if ($paymentUpdateSummary === '') {
            $paymentUpdateSummary = trim((string) ($latestLog['status_label'] ?? $statusMeta['label']));
        }

        $paymentNote = trim((string) ($latestLog['notes'] ?? $billingDetails['paymentNote'] ?? $invoice['note_text'] ?? ''));

        if ($paymentNote === '') {
            $paymentNote = 'Payment details are ready for admin review.';
        }

        $receiptHref = emarioh_admin_payment_receipt_href($invoice, $receipt, $billingDetails);
        $receiptPreviewHref = emarioh_admin_payment_preview_href($receipt);
        $receiptUploadedAt = trim((string) ($receipt['uploaded_at'] ?? $receipt['reviewed_at'] ?? $invoice['gateway_paid_at'] ?? $invoice['last_payment_at'] ?? ''));
        $receiptUploadedAtLabel = $receiptHref !== ''
            ? emarioh_admin_payment_datetime_label($receiptUploadedAt, 'Available to open')
            : '';
        $receiptFileName = trim((string) ($receipt['original_file_name'] ?? $receipt['receipt_reference'] ?? ''));

        if ($receiptFileName === '' && $receiptHref !== '') {
            $receiptFileName = 'Receipt ' . $invoiceNumber;
        }

        $receiptNote = trim((string) ($receipt['notes'] ?? ''));

        if ($receiptNote === '' && $receiptHref !== '') {
            $receiptNote = 'Open the receipt to review the posted payment.';
        }

        $canSyncStatus = trim((string) ($invoice['gateway_checkout_session_id'] ?? '')) !== ''
            && emarioh_paymongo_has_secret_key()
            && !in_array(strtolower(trim((string) ($invoice['status'] ?? 'pending'))), ['approved', 'cancelled'], true);
        $canSendDownPaymentReminder = emarioh_can_send_downpayment_reminder($booking, $invoice);
        $searchText = strtolower(implode(' ', array_filter([
            $clientName,
            $eventName,
            $invoiceNumber,
            $bookingReference,
            $statusMeta['label'],
            $paymentUpdateTitle,
            (string) ($invoice['payment_method'] ?? 'PayMongo QRPh'),
        ])));

        $rows[] = '
            <tr data-payment-row data-payment-status="' . $escape((string) $statusMeta['filter']) . '" data-payment-search="' . $escape($searchText) . '">
                <td>
                    <div class="payment-meta-cell">
                        <strong>' . $escape($clientName) . '</strong>
                        <span>' . $escape($eventName) . '</span>
                        <span>' . $escape((string) ($billingDetails['paymentMethod'] ?? 'PayMongo QRPh')) . '</span>
                    </div>
                </td>
                <td>
                    <div class="payment-meta-cell">
                        <strong>' . $escape($invoiceNumber) . '</strong>
                        <span>' . $escape($bookingReference) . '</span>
                    </div>
                </td>
                <td>
                    <div class="payment-meta-cell">
                        <strong>' . $escape($paymentUpdateTitle) . '</strong>
                        <span>' . $escape($paymentUpdateSummary) . '</span>
                    </div>
                </td>
                <td>
                    <div class="payment-meta-cell">
                        <strong>' . $escape($loggedOnDate) . '</strong>
                        <span>' . $escape($loggedOnTime !== '' ? $loggedOnTime : 'Not provided') . '</span>
                    </div>
                </td>
                <td><strong class="payment-amount">' . $escape($amountLabel) . '</strong></td>
                <td><span class="status-pill status-pill--' . $escape((string) $statusMeta['class']) . '">' . $escape((string) $statusMeta['label']) . '</span></td>
                <td>
                    <button
                        class="action-btn action-btn--ghost"
                        type="button"
                        data-payment-view
                        data-payment-source-type="admin-db"
                        data-booking-id="' . (int) ($booking['id'] ?? 0) . '"
                        data-payment-invoice-number="' . $escape($invoiceNumber) . '"
                        data-payment-title="' . $escape($invoiceNumber) . '"
                        data-client-name="' . $escape($clientName) . '"
                        data-booking-reference="' . $escape($bookingReference) . '"
                        data-event-name="' . $escape($eventName) . '"
                        data-event-schedule="' . $escape($eventSchedule) . '"
                        data-amount="' . $escape($amountLabel) . '"
                        data-due-date="' . $escape(emarioh_admin_payment_date_label((string) ($invoice['due_date'] ?? ''))) . '"
                        data-method="' . $escape((string) ($billingDetails['paymentMethod'] ?? 'PayMongo QRPh')) . '"
                        data-status-label="' . $escape((string) $statusMeta['label']) . '"
                        data-status-class="' . $escape((string) $statusMeta['class']) . '"
                        data-payment-note="' . $escape($paymentNote) . '"
                        data-payment-total-paid="' . $escape($totalPaidLabel) . '"
                        data-payment-pending-balance="' . $escape($pendingBalanceLabel) . '"
                        data-payment-last-payment="' . $escape($lastPaymentLabel) . '"
                        data-payment-logged-at="' . $escape($loggedOnLabel) . '"
                        data-payment-stage="' . $escape((string) ($billingDetails['paymentStage'] ?? ($invoice['stage_label'] ?? 'Awaiting Payment'))) . '"
                        data-payment-log="' . $escape((string) ($billingDetails['paymentLog'] ?? '')) . '"
                        data-booking-href="admin-bookings.php"
                        data-receipt-href="' . $escape($receiptHref) . '"
                        data-receipt-data-url="' . $escape($receiptPreviewHref) . '"
                        data-receipt-uploaded-at="' . $escape($receiptUploadedAtLabel) . '"
                        data-receipt-file-name="' . $escape($receiptFileName) . '"
                        data-receipt-note="' . $escape($receiptNote) . '"
                        data-receipt-empty-label="' . $escape($receiptHref !== '' ? 'Receipt ready to open.' : 'No receipt yet.') . '"
                        data-payment-confirm-mode="' . $escape($canSyncStatus ? 'admin-sync' : '') . '"
                        data-payment-confirm-label="' . $escape($canSyncStatus ? 'Refresh Status' : '') . '"
                        data-payment-reminder-template="' . $escape($canSendDownPaymentReminder ? 'downpayment_reminder' : '') . '"
                        data-payment-reminder-label="' . $escape($canSendDownPaymentReminder ? 'Down Payment Reminder' : '') . '"
                    >View</button>
                </td>
            </tr>
        ';
    }

    if ($rows === []) {
        return '<tr><td colspan="7" class="text-center text-secondary">No payment records yet.</td></tr>';
    }

    return implode("\n", $rows);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emarioh Catering Services Payments</title>
    <?= emarioh_render_vendor_head_assets(); ?>
    <link rel="stylesheet" href="assets/css/index.css?v=20260418x">
</head>
<body class="admin-dashboard-page admin-payments-page" data-auth-guard="admin" data-payment-source="db-only">
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
                            <a class="nav-link active" href="admin-payments.php"><span class="nav-link__icon"><i class="bi bi-wallet2"></i></span><span>Payment</span></a>
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
                            <h1 class="topbar-copy__title">Payments</h1>
                        </div>
                    </div>
                </header>

                <main class="dashboard-content">
                    <section class="dashboard-primary">
                        <section class="surface-card surface-card--payment-queue payment-queue-card">
                            <div class="admin-toolbar payment-toolbar">
                                <label class="admin-search" for="paymentSearchInput">
                                    <span class="admin-search__icon"><i class="bi bi-search"></i></span>
                                    <input class="admin-search__input" type="search" id="paymentSearchInput" placeholder="Search client, invoice, booking">
                                </label>
                                <label class="payment-status-select-wrap" for="paymentStatusSelect">
                                    <select class="payment-status-select" id="paymentStatusSelect" aria-label="Filter payments by status">
                                        <option value="all" selected>All</option>
                                        <option value="pending">Open</option>
                                        <option value="review">Needs Review</option>
                                        <option value="approved">Paid</option>
                                        <option value="rejected">Overdue</option>
                                    </select>
                                </label>
                                <div class="booking-filters booking-filters--compact" aria-label="Payment status filters">
                                    <button class="booking-filter-chip is-active" type="button" data-payment-filter="all" aria-pressed="true">All</button>
                                    <button class="booking-filter-chip" type="button" data-payment-filter="pending" aria-pressed="false">Open</button>
                                    <button class="booking-filter-chip" type="button" data-payment-filter="review" aria-pressed="false">Needs Review</button>
                                    <button class="booking-filter-chip" type="button" data-payment-filter="approved" aria-pressed="false">Paid</button>
                                    <button class="booking-filter-chip" type="button" data-payment-filter="rejected" aria-pressed="false">Overdue</button>
                                </div>
                            </div>

                            <div class="table-responsive dashboard-table-wrap payment-queue-table">
                                <table class="admin-table admin-table--payments">
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Invoice &amp; Booking</th>
                                            <th>Payment Update</th>
                                            <th>Logged On</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?= emarioh_render_admin_payment_rows($db, $approvedBookings, $escape) ?>
                                    </tbody>
                                </table>
                            </div>
                            <p class="booking-filter-empty" data-payment-empty hidden>No matching client payment logs found.</p>
                        </section>
                    </section>
                </main>

                <?= emarioh_render_admin_mobile_nav(basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'))) ?>
            </div>
        </div>
    </div>

    <div class="modal fade booking-modal booking-details-modal" id="paymentDetailsModal" tabindex="-1" aria-labelledby="paymentDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content booking-modal__content">
                <div class="modal-header booking-modal__header booking-details-modal__header">
                    <div>
                        <h2 class="booking-modal__title" id="paymentDetailsModalLabel">Invoice</h2>
                        <p class="booking-details-modal__subtitle" id="paymentDetailsSummary">Client</p>
                    </div>
                    <div class="booking-details-modal__header-actions">
                        <span class="status-pill status-pill--pending" id="paymentDetailsStatus">Open</span>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body booking-modal__body">
                    <div class="booking-details-grid payment-details-grid--simple">
                        <section class="booking-details-card booking-details-card--full payment-summary-card">
                            <div class="payment-overview-grid">
                                <article class="payment-overview-item">
                                    <span>Client</span>
                                    <strong id="paymentDetailsClientName">Not provided</strong>
                                    <p id="paymentDetailsBookingReference">Not provided</p>
                                </article>
                                <article class="payment-overview-item">
                                    <span>Event</span>
                                    <strong id="paymentDetailsEventName">Not provided</strong>
                                    <p id="paymentDetailsEventSchedule">Not provided</p>
                                </article>
                                <article class="payment-overview-item">
                                    <span>Amount</span>
                                    <strong id="paymentDetailsAmount">Not provided</strong>
                                    <p id="paymentDetailsDueDate">Not provided</p>
                                </article>
                                <article class="payment-overview-item">
                                    <span>Method</span>
                                    <strong id="paymentDetailsMethod">Not provided</strong>
                                    <p id="paymentDetailsStage">Not provided</p>
                                </article>
                            </div>
                        </section>
                        <section class="booking-details-card booking-details-card--full payment-details-card--compact">
                            <h3 class="booking-details-card__title">Summary</h3>
                            <div class="payment-details-summary-grid payment-details-summary-grid--compact">
                                <article class="payment-details-summary-item">
                                    <span>Paid</span>
                                    <strong id="paymentDetailsTotalPaid">Not provided</strong>
                                </article>
                                <article class="payment-details-summary-item">
                                    <span>Balance</span>
                                    <strong id="paymentDetailsPendingBalance">Not provided</strong>
                                </article>
                                <article class="payment-details-summary-item">
                                    <span>Last Update</span>
                                    <strong id="paymentDetailsLastPayment">Not provided</strong>
                                </article>
                            </div>
                        </section>
                        <section class="booking-details-card booking-details-card--full">
                            <div class="payment-receipt-card__head">
                                <h3 class="booking-details-card__title">Receipt</h3>
                                <div class="payment-receipt-card__actions">
                                    <button class="action-btn action-btn--soft" id="paymentDetailsOpenReceiptButton" type="button" hidden>Open Receipt</button>
                                    <button class="action-btn action-btn--soft" id="paymentDetailsReminderButton" type="button" hidden>Down Payment Reminder</button>
                                    <button class="action-btn action-btn--primary" id="paymentDetailsConfirmButton" type="button" hidden>Refresh Status</button>
                                </div>
                            </div>
                            <div class="payment-receipt-empty" id="paymentDetailsReceiptEmpty">No receipt yet.</div>
                            <div class="payment-receipt-preview" id="paymentDetailsReceiptPreview" hidden>
                                <div class="payment-receipt-preview__frame">
                                    <img id="paymentDetailsReceiptImage" alt="Client receipt preview">
                                </div>
                                <div class="payment-receipt-preview__meta">
                                    <article class="payment-receipt-preview__item">
                                        <span>Uploaded</span>
                                        <strong id="paymentDetailsReceiptUploadedAt">Not provided</strong>
                                    </article>
                                    <article class="payment-receipt-preview__item">
                                        <span>File Name</span>
                                        <strong id="paymentDetailsReceiptFileName">Not provided</strong>
                                    </article>
                                </div>
                                <p class="payment-receipt-preview__note" id="paymentDetailsReceiptNote">No receipt note provided.</p>
                            </div>
                        </section>
                        <section class="booking-details-card booking-details-card--full">
                            <h3 class="booking-details-card__title">Payment Log</h3>
                            <div class="payment-log-timeline" id="paymentDetailsLog"></div>
                            <p class="payment-log-empty" id="paymentDetailsLogEmpty" hidden>No updates yet.</p>
                        </section>
                    </div>
                </div>
                <div class="modal-footer booking-modal__footer">
                    <button class="action-btn action-btn--primary" type="button" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade booking-modal" id="paymentActionFeedbackModal" tabindex="-1" aria-labelledby="paymentActionFeedbackModalLabel" aria-hidden="true" data-bs-backdrop="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content booking-modal__content">
                <div class="modal-header booking-modal__header booking-details-modal__header">
                    <div>
                        <h2 class="booking-modal__title" id="paymentActionFeedbackModalLabel">Reminder Sent</h2>
                        <p class="booking-details-modal__subtitle" id="paymentActionFeedbackText">Down payment reminder sent successfully.</p>
                    </div>
                    <div class="booking-details-modal__header-actions">
                        <span class="status-pill status-pill--approved" id="paymentActionFeedbackStatus">Success</span>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-footer booking-modal__footer">
                    <button class="action-btn action-btn--primary" type="button" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <?= emarioh_render_vendor_runtime_assets(true); ?>
    <script src="assets/js/auth-api.js"></script>
    <script src="assets/js/logout-confirmation.js"></script>
    <script src="assets/js/index.js?v=20260418c"></script>
</body>
</html>
