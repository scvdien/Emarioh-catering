<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$db = emarioh_db();
$currentUser = emarioh_require_page_role('client');
$clientPortalState = emarioh_fetch_client_portal_state(
    $db,
    (int) $currentUser['id'],
    (string) ($currentUser['full_name'] ?? '')
);
$clientPortalStateJson = json_encode(
    $clientPortalState,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?: '{"clientName":"","bookingRequest":null,"billingDetails":null}';
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
    <link rel="stylesheet" href="assets/css/client-billing.css?v=20260418i">
    <link rel="stylesheet" href="assets/css/client-sidebar-parity.css?v=20260418e">
</head>
<body class="dashboard-page client-dashboard-page client-page--sticky-topbar" data-auth-guard="client">
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
                </header>

                <main class="dashboard-content client-dashboard-content billing-page">
                    <section class="surface-card billing-empty-state" id="billingPendingState">
                        <div class="panel-heading">
                            <div class="panel-heading__copy">
                                <h2>Billing</h2>
                                <p class="billing-empty-state__status" id="billingPendingTitle">Payment opens after approval</p>
                            </div>
                            <a href="client-my-bookings.php" id="billingPendingLink">View booking</a>
                        </div>
                        <p class="billing-empty-state__text" id="billingPendingText">Your booking request is still being reviewed. Once approved, this page will show your invoice, payment method, and payment instructions.</p>
                        <div class="billing-empty-state__actions" id="billingPendingActions">
                            <a class="client-action-button client-action-button--primary" href="client-my-bookings.php" id="billingPendingPrimaryAction">Go To My Bookings</a>
                        </div>
                        <div class="billing-empty-state__preview" aria-hidden="true">
                            <article class="billing-preview-card">
                                <span class="billing-preview-card__icon"><i class="bi bi-hourglass-split"></i></span>
                                <h3>Booking review first</h3>
                                <p>Billing stays locked while the team checks your date, venue, and package availability.</p>
                            </article>
                            <article class="billing-preview-card">
                                <span class="billing-preview-card__icon"><i class="bi bi-receipt-cutoff"></i></span>
                                <h3>Invoice opens after approval</h3>
                                <p>Once approved, you will see the exact amount to settle, your due date, and the invoice number here.</p>
                            </article>
                            <article class="billing-preview-card">
                                <span class="billing-preview-card__icon"><i class="bi bi-wallet2"></i></span>
                                <h3>Pay what is allowed</h3>
                                <p>If your package allows down payment, this page will show that option first so you can pay only the reservation amount.</p>
                            </article>
                        </div>
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
                                        <div class="billing-payment-focus__meta">
                                            <article class="billing-mini-stat">
                                                <span>After payment</span>
                                                <strong id="billingPaymentAfterValue">PHP 0.00 left</strong>
                                            </article>
                                            <article class="billing-mini-stat">
                                                <span>Due date</span>
                                                <strong id="billingPaymentDueInlineValue">See invoice</strong>
                                            </article>
                                        </div>
                                        <div class="billing-payment-choice" id="billingPaymentChoiceGroup" hidden>
                                            <div class="billing-payment-choice__header">
                                                <span>Payment Option</span>
                                                <p id="billingPaymentChoiceNote">Choose what you want to pay today.</p>
                                            </div>
                                            <div class="billing-payment-choice__options" id="billingPaymentChoiceOptions"></div>
                                        </div>
                                        <div class="billing-payment-guide" aria-label="Payment checklist">
                                            <div class="billing-payment-guide__header">
                                                <span>Payment Checklist</span>
                                                <p>Use these quick reminders before opening the QRPh checkout.</p>
                                            </div>
                                            <div class="billing-payment-guide__steps">
                                                <article class="billing-payment-guide__step">
                                                    <span class="billing-payment-guide__step-icon" aria-hidden="true"><i class="bi bi-receipt"></i></span>
                                                    <div class="billing-payment-guide__step-copy">
                                                        <strong>Match your invoice</strong>
                                                        <p>Review the amount shown here and confirm it matches your booking invoice.</p>
                                                    </div>
                                                </article>
                                                <article class="billing-payment-guide__step">
                                                    <span class="billing-payment-guide__step-icon" aria-hidden="true"><i class="bi bi-qr-code"></i></span>
                                                    <div class="billing-payment-guide__step-copy">
                                                        <strong>Finish the QRPh payment</strong>
                                                        <p>Use your bank app or e-wallet to complete the secure checkout.</p>
                                                    </div>
                                                </article>
                                                <article class="billing-payment-guide__step">
                                                    <span class="billing-payment-guide__step-icon" aria-hidden="true"><i class="bi bi-arrow-clockwise"></i></span>
                                                    <div class="billing-payment-guide__step-copy">
                                                        <strong>Refresh for the latest status</strong>
                                                        <p>Return to this page after payment so the system can show the updated result.</p>
                                                    </div>
                                                </article>
                                            </div>
                                        </div>
                                        <div class="billing-payment-action-panel billing-payment-action-panel--embedded">
                                            <div class="billing-payment-actions__buttons">
                                                <button class="client-action-button client-action-button--primary" id="billingPayNowButton" type="button">Pay Now</button>
                                                <button class="client-action-button client-action-button--secondary" id="billingRefreshPaymentButton" type="button">Refresh Status</button>
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
                                                    <span>Paid So Far</span>
                                                    <strong id="billingInvoicePaidValue">PHP 0.00</strong>
                                                </article>
                                                <article class="billing-mini-stat billing-mini-stat--compact">
                                                    <span>Remaining Balance</span>
                                                    <strong id="billingInvoiceBalanceValue">PHP 0.00</strong>
                                                </article>
                                            </div>
                                        </div>
                                    </article>
                                </aside>
                            </div>
                        </section>

                        <section class="surface-card billing-payment-card" id="billingReceiptSection" hidden>
                            <div class="panel-heading">
                                <div>
                                    <p class="panel-heading__eyebrow">Payment Successful</p>
                                    <h2>Your receipt is ready</h2>
                                </div>
                            </div>
                            <div class="billing-receipt-grid">
                                <div class="billing-receipt-form">
                                    <p class="billing-receipt-form__text" id="billingReceiptIntroText">Your payment has been confirmed. You can now view, download, or screenshot your system receipt for your records.</p>
                                    <div class="billing-payment-actions__buttons">
                                        <a class="client-action-button client-action-button--primary" href="#" id="billingReceiptViewLink" target="_blank" rel="noopener">View Receipt</a>
                                        <a class="client-action-button client-action-button--secondary" href="#" id="billingReceiptDownloadLink" target="_blank" rel="noopener">Download Receipt</a>
                                    </div>
                                    <p class="billing-receipt-feedback is-success" id="billingReceiptFeedback">Payment posted successfully. Your downloadable receipt is now available in the system.</p>
                                </div>
                                <div class="billing-receipt-preview-card">
                                    <div class="billing-receipt-preview-card__status">
                                        <div>
                                            <span>Receipt Status</span>
                                            <strong id="billingReceiptStatusValue">Paid Successful</strong>
                                        </div>
                                        <span class="status-pill status-pill--confirmed" id="billingReceiptStatusPill">Paid</span>
                                    </div>
                                    <div class="billing-receipt-preview-card__frame">
                                        <p id="billingReceiptFrameText">Your receipt screen is ready. Open it in a new tab, screenshot it, or save it as PDF.</p>
                                    </div>
                                    <div class="billing-receipt-preview-card__meta">
                                        <div class="billing-receipt-preview-card__item">
                                            <span>Receipt Number</span>
                                            <strong id="billingReceiptReferenceValue">RCT-TBA</strong>
                                        </div>
                                        <div class="billing-receipt-preview-card__item">
                                            <span>Invoice Number</span>
                                            <strong id="billingReceiptInvoiceValue">INV-TBA</strong>
                                        </div>
                                        <div class="billing-receipt-preview-card__item">
                                            <span>Amount Paid</span>
                                            <strong id="billingReceiptAmountValue">PHP 0.00</strong>
                                        </div>
                                        <div class="billing-receipt-preview-card__item">
                                            <span>Payment Date</span>
                                            <strong id="billingReceiptPaidAtValue">Not available</strong>
                                        </div>
                                    </div>
                                    <p class="billing-receipt-preview-card__note" id="billingReceiptNote">Keep this receipt for your records. You can always return to Billing to open it again.</p>
                                </div>
                            </div>
                        </section>
                    </section>
                </main>

                <?= emarioh_render_client_mobile_nav(basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'client-dashboard.php'))) ?>
            </div>
        </div>
    </div>

    <?= emarioh_render_vendor_runtime_assets(true); ?>
    <script src="assets/js/auth-api.js"></script>
    <script src="assets/js/logout-confirmation.js?v=20260418a"></script>
    <script>
        window.EmariohServerClientPortalState = <?= $clientPortalStateJson ?>;
    </script>
    <script>
        window.EmariohServerPackageCatalog = <?= $packageCatalogJson ?>;
    </script>
    <script src="assets/js/package-catalog.js?v=20260413a"></script>
    <script src="assets/js/payment-settings-store.js?v=20260412a"></script>
    <script src="assets/js/client-portal-state.js?v=20260417d"></script>
    <script src="assets/js/client-dashboard.js"></script>
</body>
</html>

