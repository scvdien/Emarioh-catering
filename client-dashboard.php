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

$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$clientHeroImagePath = '';

try {
    $publicSiteSettings = emarioh_fetch_public_site_settings($db);
    $clientHeroImagePath = emarioh_normalize_public_asset_path(
        (string) ($publicSiteSettings['hero_image_path'] ?? ''),
        ''
    );
} catch (Throwable $throwable) {
    $clientHeroImagePath = '';
}

$clientHeroImageUrl = $clientHeroImagePath !== '' ? emarioh_public_asset_url($clientHeroImagePath) : '';
$clientHeroImageAbsolutePath = $clientHeroImagePath !== '' ? emarioh_public_asset_absolute_path($clientHeroImagePath) : null;

if ($clientHeroImageAbsolutePath === null || !is_file($clientHeroImageAbsolutePath)) {
    $clientHeroImageUrl = '';
}

$clientHeroCardStyle = $clientHeroImageUrl !== ''
    ? "--client-hero-card-image: url('" . $clientHeroImageUrl . "');"
    : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emarioh Catering Services Client Dashboard</title>
    <?= emarioh_render_vendor_head_assets(); ?>
    <link rel="stylesheet" href="assets/css/index.css?v=20260410f">
    <link rel="stylesheet" href="assets/css/client-dashboard.css?v=20260418d">
    <link rel="stylesheet" href="assets/css/client-sidebar-parity.css?v=20260418e">
</head>
<body class="dashboard-page client-dashboard-page client-dashboard-home" data-auth-guard="client">
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
                                <span class="sidebar-brand__frame">
                                    <img src="assets/images/logo.jpg" alt="Emarioh Catering Services" class="sidebar-brand__logo">
                                </span>
                                <span class="sidebar-brand__copy">
                                    <span class="sidebar-brand__name">Emarioh</span>
                                    <span class="sidebar-brand__sub">Client Portal</span>
                                </span>
                            </a>
                        </div>

                        <div class="sidebar-divider" aria-hidden="true"></div>
                        <nav class="dashboard-nav nav flex-column" aria-label="Client portal navigation">
                            <a class="nav-link active" href="client-dashboard.php" aria-current="page">
                                <span class="nav-link__icon"><i class="bi bi-grid-1x2-fill"></i></span>
                                <span>Dashboard</span>
                            </a>
                            <a class="nav-link" href="client-bookings.php">
                                <span class="nav-link__icon"><i class="bi bi-calendar2-plus"></i></span>
                                <span>Book Event</span>
                            </a>
                            <a class="nav-link" href="client-my-bookings.php">
                                <span class="nav-link__icon"><i class="bi bi-calendar2-check"></i></span>
                                <span>My Bookings</span>
                            </a>
                            <a class="nav-link" href="client-billing.php">
                                <span class="nav-link__icon"><i class="bi bi-receipt-cutoff"></i></span>
                                <span>Billing</span>
                            </a>
                            <a class="nav-link" href="client-preferences.php">
                                <span class="nav-link__icon"><i class="bi bi-gear"></i></span>
                                <span>Account Settings</span>
                            </a>
                        </nav>

                        <div class="sidebar-footer mt-auto">
                            <a class="sidebar-logout" href="logout.php" data-logout-link>
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
                <header class="dashboard-topbar client-dashboard-topbar">
                    <div class="topbar-leading">
                        <div class="topbar-copy">
                            <h1 class="topbar-copy__title" id="dashboardHeaderTitle">Welcome back, Client</h1>
                            <p class="topbar-copy__text" id="dashboardHeaderSubtitle">Plan, manage, and track your catering events in one place.</p>
                        </div>
                    </div>
                </header>

                <main class="dashboard-content client-dashboard-content">
                    <section class="summary-grid client-summary-grid" aria-label="Client portal overview">
                        <article class="summary-card">
                            <span class="summary-card__icon" aria-hidden="true"><i class="bi bi-clipboard-check"></i></span>
                            <div class="summary-card__body">
                                <p class="summary-card__label">Booking Status</p>
                                <p class="summary-card__value" id="dashboardBookingStatusValueSummary">No active booking yet</p>
                                <p class="summary-card__note" id="dashboardBookingStatusNoteSummary">Start by creating your event request.</p>
                            </div>
                        </article>
                        <article class="summary-card">
                            <span class="summary-card__icon" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
                            <div class="summary-card__body">
                                <p class="summary-card__label">Payment Status</p>
                                <p class="summary-card__value" id="dashboardPaymentStatusValueSummary">No invoice available</p>
                                <p class="summary-card__note" id="dashboardPaymentStatusNoteSummary">Invoices will appear after booking confirmation.</p>
                            </div>
                        </article>
                        <article class="summary-card">
                            <span class="summary-card__icon" aria-hidden="true"><i class="bi bi-calendar-event"></i></span>
                            <div class="summary-card__body">
                                <p class="summary-card__label">Event Schedule</p>
                                <p class="summary-card__value" id="dashboardEventScheduleValueSummary">No schedule set</p>
                                <p class="summary-card__note" id="dashboardEventScheduleNoteSummary">Choose your preferred date and time.</p>
                            </div>
                        </article>
                    </section>

                    <section class="dashboard-primary">
                        <section class="surface-card client-hero-card"<?= $clientHeroCardStyle !== '' ? ' style="' . $escape($clientHeroCardStyle) . '"' : '' ?>>
                            <div class="client-hero-card__content">
                                <p class="panel-heading__eyebrow">Current Booking</p>
                                <h2 id="dashboardOverviewTitle">Ready to plan your event?</h2>
                                <p class="client-hero-card__intro" id="dashboardOverviewIntro">Submit your event details and let our team handle the rest, from preparation to execution.</p>

                                <div class="client-hero-card__actions">
                                    <a class="action-btn action-btn--primary" href="client-bookings.php" id="dashboardPrimaryAction">Book New Event</a>
                                    <span class="status-pill status-pill--pending" id="dashboardOverviewStatusPill">Ready to start</span>
                                </div>

                                <p class="client-hero-card__note" id="dashboardOverviewFootnote">You can track every update from My Bookings after submission.</p>
                            </div>

                            <div class="client-hero-card__media" aria-hidden="true"></div>
                        </section>

                        <section class="surface-card client-quick-actions-card" aria-labelledby="dashboardQuickActionsTitle">
                            <div class="client-quick-actions-card__heading">
                                <p class="panel-heading__eyebrow">Quick Actions</p>
                                <h2 id="dashboardQuickActionsTitle">Everything you need, right away</h2>
                            </div>
                            <div class="client-quick-actions-list">
                                <a class="client-quick-action" href="client-bookings.php">
                                    <span class="client-quick-action__icon" aria-hidden="true"><i class="bi bi-calendar2-plus"></i></span>
                                    <span class="client-quick-action__content">
                                        <strong>Book Event</strong>
                                        <span>Start a new request in minutes.</span>
                                    </span>
                                </a>
                                <a class="client-quick-action" href="client-my-bookings.php">
                                    <span class="client-quick-action__icon" aria-hidden="true"><i class="bi bi-journal-check"></i></span>
                                    <span class="client-quick-action__content">
                                        <strong>My Bookings</strong>
                                        <span>Track updates and approval status.</span>
                                    </span>
                                </a>
                                <a class="client-quick-action" href="client-billing.php">
                                    <span class="client-quick-action__icon" aria-hidden="true"><i class="bi bi-receipt"></i></span>
                                    <span class="client-quick-action__content">
                                        <strong>Billing</strong>
                                        <span>Review invoices and payments.</span>
                                    </span>
                                </a>
                                <a class="client-quick-action" href="client-preferences.php">
                                    <span class="client-quick-action__icon" aria-hidden="true"><i class="bi bi-person-gear"></i></span>
                                    <span class="client-quick-action__content">
                                        <strong>Profile</strong>
                                        <span>Update your account details.</span>
                                    </span>
                                </a>
                            </div>
                        </section>

                        <section class="client-dashboard-secondary-grid">
                            <section class="surface-card client-details-card" aria-labelledby="dashboardSnapshotTitle">
                                <div class="client-details-card__heading">
                                    <p class="panel-heading__eyebrow">Upcoming Event</p>
                                    <h2 id="dashboardSnapshotTitle">Booking snapshot</h2>
                                </div>

                                <div class="client-details-list">
                                    <article class="client-details-item">
                                        <span class="client-details-item__icon" aria-hidden="true"><i class="bi bi-stars"></i></span>
                                        <div class="client-details-item__text">
                                            <span class="client-details-item__label">Event</span>
                                            <strong id="dashboardOverviewEvent">Not selected</strong>
                                        </div>
                                    </article>
                                    <article class="client-details-item">
                                        <span class="client-details-item__icon" aria-hidden="true"><i class="bi bi-calendar-week"></i></span>
                                        <div class="client-details-item__text">
                                            <span class="client-details-item__label">Schedule</span>
                                            <strong id="dashboardOverviewSchedule">Not scheduled</strong>
                                        </div>
                                    </article>
                                    <article class="client-details-item">
                                        <span class="client-details-item__icon" aria-hidden="true"><i class="bi bi-geo-alt"></i></span>
                                        <div class="client-details-item__text">
                                            <span class="client-details-item__label">Venue</span>
                                            <strong id="dashboardOverviewVenue">To be decided</strong>
                                        </div>
                                    </article>
                                </div>

                                <div class="client-details-meta">
                                    <article class="client-details-meta__item">
                                        <span>Guests</span>
                                        <strong id="dashboardOverviewGuests">Not specified</strong>
                                    </article>
                                    <article class="client-details-meta__item">
                                        <span>Reference</span>
                                        <strong id="dashboardOverviewReference">Not available yet</strong>
                                    </article>
                                    <article class="client-details-meta__item client-details-meta__item--full">
                                        <span>Stage</span>
                                        <strong id="dashboardOverviewStage">Pre-booking</strong>
                                    </article>
                                </div>

                                <p class="client-details-card__note" id="dashboardOverviewFootnoteSecondary">You can track every update from My Bookings after submission.</p>
                            </section>

                            <aside class="surface-card client-mobile-status-card" aria-labelledby="dashboardMobileStatusTitle">
                                <div class="client-mobile-status-card__heading">
                                    <p class="panel-heading__eyebrow">At A Glance</p>
                                    <h2 id="dashboardMobileStatusTitle">Current status</h2>
                                </div>

                                <div class="client-mobile-status-list">
                                    <article class="client-mobile-status-item">
                                        <span class="client-mobile-status-item__label">Booking</span>
                                        <strong id="dashboardBookingStatusValue">No active booking yet</strong>
                                        <p id="dashboardBookingStatusNote">Start by creating your event request.</p>
                                    </article>
                                    <article class="client-mobile-status-item">
                                        <span class="client-mobile-status-item__label">Payment</span>
                                        <strong id="dashboardPaymentStatusValue">No invoice available</strong>
                                        <p id="dashboardPaymentStatusNote">Invoices will appear after booking confirmation.</p>
                                    </article>
                                    <article class="client-mobile-status-item">
                                        <span class="client-mobile-status-item__label">Schedule</span>
                                        <strong id="dashboardEventScheduleValue">No schedule set</strong>
                                        <p id="dashboardEventScheduleNote">Choose your preferred date and time.</p>
                                    </article>
                                </div>
                            </aside>
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
    <script src="assets/js/package-catalog.js?v=20260412c"></script>
    <script src="assets/js/payment-settings-store.js?v=20260412a"></script>
    <script src="assets/js/client-portal-state.js?v=20260412g"></script>
    <script src="assets/js/client-dashboard.js"></script>
</body>
</html>
