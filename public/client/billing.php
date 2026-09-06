<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';

$db = emarioh_db();
$currentUser = emarioh_require_page_role('client');
$currentUserId = (int) ($currentUser['id'] ?? 0);
$clientPortalState = emarioh_fetch_client_portal_state(
    $db,
    $currentUserId,
    (string) ($currentUser['full_name'] ?? '')
);
$paymentHistory = emarioh_fetch_client_payment_history($db, $currentUserId);
$totalReceiptsCount = 0;
foreach ($paymentHistory as $historyItemCheck) {
    $totalReceiptsCount += (int) ($historyItemCheck['receipt_count'] ?? 1);
}
$clientPortalStateJson = json_encode(
    $clientPortalState,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?: '{"clientName":"","bookingRequest":null,"billingDetails":null,"bookingNotification":null}';
$packageCatalogJson = json_encode(
    emarioh_fetch_service_package_catalog($db),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?: '[]';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emarioh Catering Services Client Billing</title>
    <?= emarioh_render_vendor_head_assets(); ?>
    <link rel="stylesheet" href="assets/css/client-sidebar-parity.css?v=20260710a">
    <link rel="stylesheet" href="assets/css/pages/client-billing.css?v=20260904z">
</head>
<body class="dashboard-page client-dashboard-page client-page--sticky-topbar client-billing-page" data-auth-guard="client">
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
                            <a class="nav-link" href="client-my-bookings.php"><span class="nav-link__icon"><i class="bi bi-calendar2-check"></i></span><span>My Bookings</span></a>
                            <?= emarioh_render_client_notification_nav_link($db, (int) $currentUser['id']) ?>
                            <a class="nav-link active" href="client-billing.php" aria-current="page"><span class="nav-link__icon"><i class="bi bi-receipt-cutoff"></i></span><span>Billing</span></a>
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
                        <div class="topbar-copy"><h1 class="topbar-copy__title">Billing</h1></div>
                    </div>
                    <?php if (!empty($paymentHistory)): ?>
                        <div class="topbar-trailing">
                            <button type="button" class="billing-history-topbar-btn" data-bs-toggle="modal" data-bs-target="#billingHistoryModal" aria-label="View Payment History">
                                <i class="bi bi-clock-history" aria-hidden="true"></i>
                                <span class="billing-history-topbar-btn__label-full">Payment History</span>
                                <span class="billing-history-topbar-btn__label-short">History</span>
                                <span class="billing-history-topbar-btn__badge" aria-label="<?= $totalReceiptsCount ?> past receipts"><?= $totalReceiptsCount ?></span>
                            </button>
                        </div>
                    <?php endif; ?>
                </header>

                <main class="dashboard-content client-dashboard-content billing-page">
                    <section class="billing-empty-state-wrapper" id="billingPendingState">
                        <article class="billing-unified-empty-card surface-card">
                            <!-- Top Hero: Status, Text, and Primary Action -->
                            <div class="billing-unified-empty-card__hero">
                                <div class="billing-unified-empty-card__icon-badge" aria-hidden="true">
                                    <i class="bi bi-receipt-cutoff" id="billingPendingHeroIcon"></i>
                                </div>
                                <div class="billing-unified-empty-card__content">
                                    <div class="billing-empty-state-hero__meta">
                                        <span class="panel-heading__eyebrow" id="billingPendingEyebrow"><i class="bi bi-shield-check text-warning me-1"></i> Billing Status</span>
                                        <h2 class="billing-unified-empty-card__title" id="billingPendingTitle">No Active Invoices Yet</h2>
                                    </div>
                                    <p class="billing-unified-empty-card__text" id="billingPendingText">
                                        You don't have an active booking or pending balance right now. Once you submit a booking request and our team approves your date and catering package, your official invoice, down payment options, and QRPh payment portal will appear here.
                                    </p>
                                    <div class="billing-unified-empty-card__actions" id="billingPendingActions">
                                        <a class="client-action-button client-action-button--primary" href="client-bookings.php" id="billingPendingPrimaryAction">
                                            <i class="bi bi-calendar2-plus me-1"></i> Book An Event Now
                                        </a>
                                        <?php if (!empty($paymentHistory)): ?>
                                            <button type="button" class="client-action-button client-action-button--secondary" data-bs-toggle="modal" data-bs-target="#billingHistoryModal" id="billingPendingHistoryBtn">
                                                <i class="bi bi-clock-history me-1"></i> View Past Receipts (<?= $totalReceiptsCount ?>)
                                            </button>
                                        <?php endif; ?>
                                        <a class="client-action-button client-action-button--secondary" href="client-my-bookings.php" id="billingPendingLink" hidden>
                                            <i class="bi bi-calendar2-check me-1"></i> View My Bookings
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Divider with Guide Badge -->
                            <div class="billing-unified-empty-card__divider" aria-hidden="true">
                                <span class="billing-unified-empty-card__divider-line"></span>
                                <span class="billing-unified-empty-card__divider-pill"><i class="bi bi-journal-bookmark me-1 text-primary"></i> 3-Step Payment Guide</span>
                                <span class="billing-unified-empty-card__divider-line"></span>
                            </div>

                            <!-- Integrated Stepper: 3 Connected Steps -->
                            <div class="billing-unified-stepper" aria-label="How Billing Works">
                                <div class="billing-unified-step">
                                    <div class="billing-unified-step__indicator">
                                        <span class="billing-unified-step__num">01</span>
                                        <span class="billing-unified-step__icon"><i class="bi bi-calendar2-heart"></i></span>
                                    </div>
                                    <div class="billing-unified-step__info">
                                        <h4 class="billing-unified-step__title">Book Your Event</h4>
                                        <p class="billing-unified-step__desc">Select your date, venue, guest count, and catering menu package.</p>
                                    </div>
                                </div>
                                <div class="billing-unified-step__connector" aria-hidden="true"><i class="bi bi-chevron-right"></i></div>
                                <div class="billing-unified-step">
                                    <div class="billing-unified-step__indicator">
                                        <span class="billing-unified-step__num">02</span>
                                        <span class="billing-unified-step__icon"><i class="bi bi-patch-check"></i></span>
                                    </div>
                                    <div class="billing-unified-step__info">
                                        <h4 class="billing-unified-step__title">Admin Review & Bill</h4>
                                        <p class="billing-unified-step__desc">Our coordinators verify your event date and open your itemized invoice.</p>
                                    </div>
                                </div>
                                <div class="billing-unified-step__connector" aria-hidden="true"><i class="bi bi-chevron-right"></i></div>
                                <div class="billing-unified-step">
                                    <div class="billing-unified-step__indicator">
                                        <span class="billing-unified-step__num">03</span>
                                        <span class="billing-unified-step__icon"><i class="bi bi-receipt"></i></span>
                                    </div>
                                    <div class="billing-unified-step__info">
                                        <h4 class="billing-unified-step__title">Pay & Get Receipt</h4>
                                        <p class="billing-unified-step__desc">Settle balance via QRPh and download your official system receipt.</p>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </section>

                    <section id="billingApprovedContent" hidden>
                        <section class="billing-payment-section" id="billingPaymentSection">
                            <div class="billing-payment-grid">
                                <div class="billing-payment-main">
                                    <article class="billing-payment-focus billing-payment-focus--fill">
                                        <div class="billing-payment-focus__head">
                                            <div class="billing-payment-focus__copy">
                                                <span class="billing-payment-focus__eyebrow">Amount Due</span>
                                                <strong class="billing-payment-focus__amount" id="billingPaymentFocusAmountValue">PHP 0.00</strong>
                                                <p class="billing-payment-focus__text" id="billingPaymentIntroText">Pay the remaining balance to complete your booking payment.</p>
                                            </div>
                                            <span class="billing-payment-focus__badge billing-payment-focus__badge--plain" id="billingPaymentFocusOptionValue">Remaining balance</span>
                                        </div>

                                        <div class="billing-financial-bar" aria-label="Financial summary">
                                            <div class="billing-financial-stat">
                                                <span class="billing-financial-stat__label">Total Contract</span>
                                                <strong class="billing-financial-stat__val" id="billingPaymentTotalContractValue">PHP 0.00</strong>
                                            </div>
                                            <div class="billing-financial-bar__sep" aria-hidden="true"></div>
                                            <div class="billing-financial-stat">
                                                <span class="billing-financial-stat__label">Paid So Far</span>
                                                <strong class="billing-financial-stat__val billing-financial-stat__val--paid" id="billingPaymentPaidSoFarValue">PHP 0.00</strong>
                                            </div>
                                            <div class="billing-financial-bar__sep" aria-hidden="true"></div>
                                            <div class="billing-financial-stat">
                                                <span class="billing-financial-stat__label">Remaining Due</span>
                                                <strong class="billing-financial-stat__val billing-financial-stat__val--due" id="billingPaymentDueTodayValue">PHP 0.00</strong>
                                            </div>
                                        </div>

                                        <div class="billing-payment-choice" id="billingPaymentChoiceGroup" hidden>
                                            <div class="billing-payment-choice__header">
                                                <span>Payment Option</span>
                                                <p id="billingPaymentChoiceNote">Choose what you want to pay today.</p>
                                            </div>
                                            <div class="billing-payment-choice__options" id="billingPaymentChoiceOptions"></div>
                                        </div>

                                        <div class="billing-payment-action-panel billing-payment-action-panel--embedded">
                                            <div class="billing-payment-actions__buttons">
                                                <button class="client-action-button client-action-button--primary" id="billingPayNowButton" type="button">Pay Now</button>
                                                <button class="client-action-button client-action-button--secondary" id="billingRefreshPaymentButton" type="button">
                                                    <i class="bi bi-arrow-clockwise me-1"></i> Refresh Status
                                                </button>
                                            </div>
                                            <div class="billing-payment-trust-badge">
                                                <span class="billing-payment-trust-badge__icon"><i class="bi bi-shield-fill-check"></i></span>
                                                <span class="billing-payment-trust-badge__text">100% Secure Checkout via <strong>PayMongo</strong> &bull; QRPh</span>
                                            </div>
                                            <p class="billing-payment-feedback" id="billingPaymentFeedback">Your payment status will update here after PayMongo confirms the transaction.</p>
                                        </div>
                                    </article>
                                </div>
                                <aside class="billing-payment-sidebar">
                                    <article class="billing-invoice-card">
                                        <div class="billing-invoice-card__head">
                                            <div>
                                                <p class="panel-heading__eyebrow">Invoice Snapshot</p>
                                                <h3>Booking details</h3>
                                            </div>
                                            <span class="status-pill status-pill--pending" id="billingInvoiceStatusPill">Open</span>
                                        </div>
                                        <div class="billing-invoice-list">
                                            <div class="billing-invoice-list__row">
                                                <span>Invoice No.</span>
                                                <strong id="billingInvoiceNumberValue">INV-TBA</strong>
                                            </div>
                                            <div class="billing-invoice-list__row">
                                                <span>Booking Ref.</span>
                                                <strong id="billingInvoiceBookingValue">REQ-TBA</strong>
                                            </div>
                                            <div class="billing-invoice-list__row">
                                                <span>Event</span>
                                                <strong id="billingInvoiceEventValue">Booking</strong>
                                            </div>
                                            <div class="billing-invoice-list__row">
                                                <span>Schedule</span>
                                                <strong id="billingInvoiceScheduleValue">Date pending</strong>
                                            </div>
                                            <div class="billing-invoice-list__row">
                                                <span>Venue</span>
                                                <strong id="billingInvoiceVenueValue">Venue pending</strong>
                                            </div>
                                            <div class="billing-invoice-list__row">
                                                <span>Payment</span>
                                                <strong id="billingInvoiceOptionValue">Payment</strong>
                                            </div>
                                        </div>
                                        <div class="billing-invoice-summary-block">
                                            <div class="billing-invoice-summary">
                                                <article class="billing-mini-stat billing-mini-stat--compact">
                                                    <span>Total Contract</span>
                                                    <strong id="billingInvoiceTotalContractValue">PHP 0.00</strong>
                                                </article>
                                                <article class="billing-mini-stat billing-mini-stat--compact">
                                                    <span>Paid So Far</span>
                                                    <strong class="text-success" id="billingInvoicePaidValue">PHP 0.00</strong>
                                                </article>
                                                <article class="billing-mini-stat billing-mini-stat--compact">
                                                    <span>Remaining</span>
                                                    <strong id="billingInvoiceBalanceValue">PHP 0.00</strong>
                                                </article>
                                            </div>
                                        </div>
                                    </article>
                                </aside>
                            </div>
                        </section>

                        <div class="billing-paid-view-layout" id="billingPaidViewLayout" hidden>
                            <article class="billing-paid-breakdown-card" id="billingPaidBreakdownCard" hidden>
                                <div class="billing-paid-breakdown__header">
                                    <div>
                                        <span class="panel-heading__eyebrow"><i class="bi bi-shield-check text-success me-1"></i> Payment Verified</span>
                                        <h2 class="billing-paid-breakdown__title">Event & Invoice Summary</h2>
                                    </div>
                                    <span class="status-pill status-pill--confirmed">Settled in Full</span>
                                </div>

                                <div class="billing-paid-breakdown__body">
                                    <div class="billing-paid-banner">
                                        <div class="billing-paid-banner__icon">
                                            <i class="bi bi-calendar2-check-fill"></i>
                                        </div>
                                        <div class="billing-paid-banner__content">
                                            <strong class="billing-paid-banner__title" id="billingPaidEventTitle">Catering Event</strong>
                                            <span class="billing-paid-banner__subtitle" id="billingPaidPackageTitle">Package to follow</span>
                                        </div>
                                    </div>

                                    <div class="billing-paid-grid">
                                        <div class="billing-paid-item">
                                            <span class="billing-paid-item__label"><i class="bi bi-calendar-event"></i> Event Schedule</span>
                                            <strong class="billing-paid-item__value" id="billingPaidSchedule">Schedule pending</strong>
                                        </div>
                                        <div class="billing-paid-item">
                                            <span class="billing-paid-item__label"><i class="bi bi-geo-alt"></i> Venue</span>
                                            <strong class="billing-paid-item__value" id="billingPaidVenue">Venue pending</strong>
                                        </div>
                                        <div class="billing-paid-item">
                                            <span class="billing-paid-item__label"><i class="bi bi-people"></i> Guests</span>
                                            <strong class="billing-paid-item__value" id="billingPaidGuests">0 pax</strong>
                                        </div>
                                        <div class="billing-paid-item">
                                            <span class="billing-paid-item__label"><i class="bi bi-hash"></i> Booking Reference</span>
                                            <strong class="billing-paid-item__value" id="billingPaidBookingRef">REQ-TBA</strong>
                                        </div>
                                    </div>

                                    <div class="billing-paid-ledger">
                                        <div class="billing-paid-ledger__row">
                                            <span>Total Contract Amount</span>
                                            <strong id="billingPaidTotalContract">PHP 0.00</strong>
                                        </div>
                                        <div class="billing-paid-ledger__row text-success">
                                            <span><i class="bi bi-check2-circle me-1"></i> Total Settled</span>
                                            <strong id="billingPaidTotalSettled">PHP 0.00</strong>
                                        </div>
                                        <div class="billing-paid-ledger__row billing-paid-ledger__row--highlight">
                                            <span>Remaining Balance</span>
                                            <span class="badge bg-success-subtle text-success fw-bold px-2 py-1">PHP 0.00 (Fully Settled)</span>
                                        </div>
                                    </div>
                                </div>
                            </article>

                            <section class="billing-receipt-voucher-card" id="billingReceiptSection" hidden>
                                <div class="billing-receipt-voucher__header">
                                    <div class="billing-receipt-voucher__badge-row">
                                        <span class="billing-receipt-voucher__seal">
                                            <i class="bi bi-check-circle-fill"></i>
                                        </span>
                                        <div class="billing-receipt-voucher__status-wrap">
                                            <span class="status-pill status-pill--confirmed" id="billingReceiptStatusPill">Paid</span>
                                            <span class="billing-receipt-voucher__status-label" id="billingReceiptStatusValue">Paid Successful</span>
                                        </div>
                                    </div>
                                    <div class="billing-receipt-voucher__hero">
                                        <span class="billing-receipt-voucher__amount-label">Total Amount Paid</span>
                                        <h2 class="billing-receipt-voucher__amount" id="billingReceiptAmountValue">PHP 0.00</h2>
                                        <p class="billing-receipt-voucher__intro" id="billingReceiptIntroText">Admin recorded your manual payment. Open your system receipt to download, print, or screenshot it.</p>
                                    </div>
                                </div>

                                <div class="billing-receipt-voucher__divider" aria-hidden="true">
                                    <span class="billing-receipt-voucher__notch billing-receipt-voucher__notch--left"></span>
                                    <span class="billing-receipt-voucher__line"></span>
                                    <span class="billing-receipt-voucher__notch billing-receipt-voucher__notch--right"></span>
                                </div>

                                <div class="billing-receipt-voucher__body">
                                    <div class="billing-receipt-voucher__list">
                                        <div class="billing-receipt-voucher__row">
                                            <span class="billing-receipt-voucher__key"><i class="bi bi-receipt"></i> Receipt No.</span>
                                            <strong class="billing-receipt-voucher__val" id="billingReceiptReferenceValue">RCT-TBA</strong>
                                        </div>
                                        <div class="billing-receipt-voucher__row">
                                            <span class="billing-receipt-voucher__key"><i class="bi bi-file-earmark-text"></i> Invoice No.</span>
                                            <strong class="billing-receipt-voucher__val" id="billingReceiptInvoiceValue">INV-TBA</strong>
                                        </div>
                                        <div class="billing-receipt-voucher__row">
                                            <span class="billing-receipt-voucher__key"><i class="bi bi-calendar-check"></i> Payment Date</span>
                                            <strong class="billing-receipt-voucher__val" id="billingReceiptPaidAtValue">Not available</strong>
                                        </div>
                                    </div>

                                    <p class="billing-receipt-voucher__note" id="billingReceiptNote">Keep this receipt for your records. You can always return to Billing to open it again.</p>

                                    <div class="billing-receipt-voucher__actions">
                                        <a class="client-action-button client-action-button--primary billing-receipt-voucher__btn" href="#" id="billingReceiptViewLink" target="_blank" rel="noopener">
                                            <i class="bi bi-file-earmark-arrow-down"></i>
                                            <span>View / Download Official Receipt</span>
                                        </a>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </section>

                </main>

                <?= emarioh_render_client_mobile_nav(basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'client-dashboard.php'))) ?>
            </div>
        </div>
    </div>

    <?php if (!empty($paymentHistory)): ?>
        <div class="modal fade booking-modal" id="billingHistoryModal" tabindex="-1" aria-labelledby="billingHistoryModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
                <div class="modal-content booking-modal__content">
                    <div class="modal-header booking-modal__header">
                        <div class="pe-2">
                            <p class="panel-heading__eyebrow d-none d-sm-block mb-1"><i class="bi bi-clock-history text-primary me-1"></i> Transaction Archive</p>
                            <h2 class="booking-modal__title" id="billingHistoryModalLabel">Payment & Receipts History</h2>
                        </div>
                        <div class="d-flex align-items-center gap-2 ms-auto flex-shrink-0">
                            <span class="badge bg-secondary-subtle text-secondary fw-semibold px-2 px-sm-3 py-1 py-sm-2 rounded-pill">
                                <?= count($paymentHistory) ?> Booking<?= count($paymentHistory) > 1 ? 's' : '' ?><?= $totalReceiptsCount > count($paymentHistory) ? ' • ' . $totalReceiptsCount . ' Receipts' : (count($paymentHistory) > 1 ? ' Records' : ' Record') ?>
                            </span>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                    </div>
                    <div class="modal-body booking-modal__body p-0">
                        <!-- Desktop Table View (>= 768px) -->
                        <div class="billing-history-desktop">
                            <div class="table-responsive dashboard-table-wrap billing-history-table-wrap m-0 border-0 rounded-0">
                                <table class="admin-table admin-table--billing-history">
                                    <thead>
                                        <tr>
                                            <th>Payment Date</th>
                                            <th>Invoice / Receipt</th>
                                            <th>Event & Package</th>
                                            <th>Amount Settled</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paymentHistory as $historyItem):
                                            $invoiceNum = (string) ($historyItem['invoice_number'] ?? '');
                                            $receiptRef = (string) ($historyItem['receipt_reference'] ?? $invoiceNum);
                                            $receiptId = (int) ($historyItem['receipt_id'] ?? 0);
                                            $eventDateStr = emarioh_format_booking_date_label((string) ($historyItem['event_date'] ?? ''));
                                            $paidDateStr = !empty($historyItem['paid_at']) ? date('M j, Y - g:i A', strtotime((string) $historyItem['paid_at'])) : 'Recently';
                                            $amountPaid = (float) ($historyItem['amount_paid'] ?? 0);
                                            $receiptUrl = 'client-payment-receipt.php?invoice=' . rawurlencode($invoiceNum) . ($receiptId > 0 ? '&receipt=' . $receiptId : '');
                                            $receiptCount = (int) ($historyItem['receipt_count'] ?? 1);
                                            $receipts = (array) ($historyItem['receipts'] ?? []);
                                        ?>
                                            <tr>
                                                <td>
                                                    <span class="d-block fw-semibold text-dark"><?= htmlspecialchars($paidDateStr, ENT_QUOTES, 'UTF-8') ?></span>
                                                    <span class="d-block text-secondary small"><?= htmlspecialchars((string) ($historyItem['payment_method'] ?? 'Manual Payment'), ENT_QUOTES, 'UTF-8') ?></span>
                                                </td>
                                                <td>
                                                    <span class="d-block fw-semibold text-dark"><?= htmlspecialchars($invoiceNum, ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php if ($receiptCount > 1): ?>
                                                        <span class="billing-receipt-multi-pill" title="<?= $receiptCount ?> payments completed">
                                                            <i class="bi bi-collection-fill"></i> <?= $receiptCount ?> Receipts Issued
                                                        </span>
                                                        <span class="d-block text-secondary small" style="font-size: 0.72rem; margin-top: 0.15rem;">Down Payment &amp; Balance</span>
                                                    <?php else: ?>
                                                        <span class="d-block text-secondary small font-monospace"><?= htmlspecialchars($receiptRef, ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="d-block fw-semibold text-dark"><?= htmlspecialchars((string) ($historyItem['event_type'] ?? 'Catering'), ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string) ($historyItem['booking_reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)</span>
                                                    <span class="d-block text-secondary small"><?= htmlspecialchars((string) ($historyItem['package_label'] ?? 'Standard Package'), ENT_QUOTES, 'UTF-8') ?> • Event Date: <?= htmlspecialchars($eventDateStr, ENT_QUOTES, 'UTF-8') ?></span>
                                                </td>
                                                <td>
                                                    <strong class="text-success fs-6">PHP <?= number_format($amountPaid, 2) ?></strong>
                                                    <?php if ($receiptCount > 1 && count($receipts) >= 2): 
                                                        $dpAmt = (float) ($receipts[0]['amount_reported'] ?? 0);
                                                        $balAmt = (float) ($receipts[1]['amount_reported'] ?? 0);
                                                    ?>
                                                        <div class="billing-history-settled-breakdown" title="Payment installment breakdown">
                                                            <span>₱<?= number_format($dpAmt, 0) ?> DP</span> + <span>₱<?= number_format($balAmt, 0) ?> Balance</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="status-pill status-pill--confirmed">Paid</span>
                                                </td>
                                                <td>
                                                    <div class="billing-history-actions">
                                                        <a href="<?= htmlspecialchars($receiptUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="booking-request-action booking-request-action--view" title="View Official Digital Receipts">
                                                            <i class="bi bi-eye"></i> <?= $receiptCount > 1 ? 'View Receipts (' . $receiptCount . ')' : 'View Receipt' ?>
                                                        </a>
                                                        <?php if ($receiptCount > 1 && !empty($receipts)): ?>
                                                            <div class="billing-history-direct-receipts">
                                                                <?php foreach ($receipts as $idx => $r):
                                                                    $rId = (int) ($r['id'] ?? 0);
                                                                    $rLabel = ($idx === 0) ? 'DP Receipt' : 'Balance Receipt';
                                                                    if ($receiptCount > 2) { $rLabel = 'Receipt #' . ($idx + 1); }
                                                                    $rSingleUrl = 'client-payment-receipt.php?invoice=' . rawurlencode($invoiceNum) . '&receipt=' . $rId;
                                                                ?>
                                                                    <a href="<?= htmlspecialchars($rSingleUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="billing-receipt-chip" title="Directly open <?= $rLabel ?>">
                                                                        <i class="bi bi-file-earmark-check"></i> <?= $rLabel ?>
                                                                    </a>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Mobile Modern Receipt Slips (< 768px) -->
                        <div class="billing-history-mobile">
                            <div class="billing-receipt-cards">
                                <?php foreach ($paymentHistory as $historyItem):
                                    $invoiceNum = (string) ($historyItem['invoice_number'] ?? '');
                                    $receiptRef = (string) ($historyItem['receipt_reference'] ?? $invoiceNum);
                                    $receiptId = (int) ($historyItem['receipt_id'] ?? 0);
                                    $eventDateStr = emarioh_format_booking_date_label((string) ($historyItem['event_date'] ?? ''));
                                    $paidDateStr = !empty($historyItem['paid_at']) ? date('M j, Y • g:i A', strtotime((string) $historyItem['paid_at'])) : 'Recently';
                                    $amountPaid = (float) ($historyItem['amount_paid'] ?? 0);
                                    $eventType = (string) ($historyItem['event_type'] ?? 'Catering');
                                    $bookingRef = (string) ($historyItem['booking_reference'] ?? '');
                                    $packageLabel = (string) ($historyItem['package_label'] ?? 'Standard Package');
                                    $paymentMethod = (string) ($historyItem['payment_method'] ?? 'Manual Payment');
                                    $receiptUrl = 'client-payment-receipt.php?invoice=' . rawurlencode($invoiceNum) . ($receiptId > 0 ? '&receipt=' . $receiptId : '');
                                    $receiptCount = (int) ($historyItem['receipt_count'] ?? 1);
                                    $receipts = (array) ($historyItem['receipts'] ?? []);
                                ?>
                                    <article class="billing-receipt-slip">
                                        <div class="billing-receipt-slip__top">
                                            <div class="billing-receipt-slip__event">
                                                <span class="billing-receipt-slip__icon" aria-hidden="true">
                                                    <i class="bi bi-receipt-cutoff"></i>
                                                </span>
                                                <div class="billing-receipt-slip__info">
                                                    <h4 class="billing-receipt-slip__title"><?= htmlspecialchars($eventType, ENT_QUOTES, 'UTF-8') ?></h4>
                                                    <p class="billing-receipt-slip__subtitle"><?= htmlspecialchars($packageLabel, ENT_QUOTES, 'UTF-8') ?></p>
                                                </div>
                                            </div>
                                            <span class="status-pill status-pill--confirmed">
                                                <i class="bi bi-check-circle-fill me-1"></i> Paid
                                            </span>
                                        </div>

                                        <div class="billing-receipt-slip__amount-card">
                                            <div class="billing-receipt-slip__amount-label">Amount Settled</div>
                                            <div class="billing-receipt-slip__amount-value">
                                                <span>PHP</span> <?= number_format($amountPaid, 2) ?>
                                            </div>
                                            <?php if ($receiptCount > 1 && count($receipts) >= 2): 
                                                $dpAmt = (float) ($receipts[0]['amount_reported'] ?? 0);
                                                $balAmt = (float) ($receipts[1]['amount_reported'] ?? 0);
                                            ?>
                                                <div class="billing-receipt-slip__breakdown-tag">
                                                    <i class="bi bi-layers-half me-1"></i> 2 Installments: ₱<?= number_format($dpAmt, 0) ?> DP + ₱<?= number_format($balAmt, 0) ?> Balance
                                                </div>
                                            <?php endif; ?>
                                            <div class="billing-receipt-slip__amount-meta">
                                                <span><i class="bi bi-clock-history"></i> <?= htmlspecialchars($paidDateStr, ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="bullet-dot">•</span>
                                                <span><i class="bi bi-credit-card-2-front"></i> <?= htmlspecialchars($paymentMethod, ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                        </div>

                                        <div class="billing-receipt-slip__meta-list">
                                            <div class="billing-receipt-slip__meta-row">
                                                <span class="meta-label">Invoice Number</span>
                                                <span class="meta-value font-monospace text-dark fw-semibold"><?= htmlspecialchars($invoiceNum, ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                            <?php if ($receiptCount > 1): ?>
                                                <div class="billing-receipt-slip__meta-row">
                                                    <span class="meta-label">Receipts</span>
                                                    <span class="meta-value text-success fw-semibold"><i class="bi bi-collection me-1"></i> <?= $receiptCount ?> Receipts (DP &amp; Balance)</span>
                                                </div>
                                            <?php elseif ($receiptRef !== $invoiceNum): ?>
                                                <div class="billing-receipt-slip__meta-row">
                                                    <span class="meta-label">Receipt Ref</span>
                                                    <span class="meta-value font-monospace"><?= htmlspecialchars($receiptRef, ENT_QUOTES, 'UTF-8') ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="billing-receipt-slip__meta-row">
                                                <span class="meta-label">Event Booking</span>
                                                <span class="meta-value"><?= htmlspecialchars($bookingRef ?: 'N/A', ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($eventDateStr, ENT_QUOTES, 'UTF-8') ?>)</span>
                                            </div>
                                        </div>

                                        <div class="billing-receipt-slip__actions">
                                            <a href="<?= htmlspecialchars($receiptUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="billing-receipt-slip__action-btn billing-receipt-slip__action-btn--view">
                                                <i class="bi bi-eye"></i> <?= $receiptCount > 1 ? 'View All Receipts (' . $receiptCount . ')' : 'View Digital Receipt' ?>
                                            </a>
                                            <?php if ($receiptCount > 1 && !empty($receipts)): ?>
                                                <div class="billing-receipt-slip__sub-actions">
                                                    <?php foreach ($receipts as $idx => $r):
                                                        $rId = (int) ($r['id'] ?? 0);
                                                        $rLabel = ($idx === 0) ? 'Down Payment Receipt' : 'Remaining Balance Receipt';
                                                        if ($receiptCount > 2) { $rLabel = 'Receipt #' . ($idx + 1); }
                                                        $rAmt = (float) ($r['amount_reported'] ?? 0);
                                                        $rUrl = 'client-payment-receipt.php?invoice=' . rawurlencode($invoiceNum) . '&receipt=' . $rId;
                                                    ?>
                                                        <a href="<?= htmlspecialchars($rUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="billing-receipt-slip__sub-btn">
                                                            <i class="bi bi-file-earmark-check me-1"></i> <?= $rLabel ?> (₱<?= number_format($rAmt, 0) ?>)
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?= emarioh_render_vendor_runtime_assets(true); ?>
    <script src="assets/js/auth-api.js?v=20260419c"></script>
    <script src="assets/js/logout-confirmation.js?v=20260706c"></script>
    <script>
        window.EmariohServerClientPortalState = <?= $clientPortalStateJson ?>;
    </script>
    <script>
        window.EmariohServerPackageCatalog = <?= $packageCatalogJson ?>;
    </script>
    <script src="assets/js/package-catalog.js?v=20260413a"></script>
    <script src="assets/js/payment-settings-store.js?v=20260412a"></script>
    <script src="assets/js/client-portal-state.js?v=20260904e"></script>
    <script src="assets/js/pages/client-dashboard.js?v=20260709a"></script>
</body>
</html>

