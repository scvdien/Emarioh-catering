<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';

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
) ?: '{"clientName":"","bookingRequest":null,"billingDetails":null,"bookingNotification":null}';
$packageCatalogJson = json_encode(
    emarioh_fetch_service_package_catalog($db),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?: '[]';

$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$clientDisplayName = trim((string) ($clientPortalState['clientName'] ?? ''));
if ($clientDisplayName === '') {
    $clientDisplayName = trim((string) ($currentUser['full_name'] ?? ''));
}
if ($clientDisplayName === '') {
    $clientDisplayName = 'Client';
}
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
    <link rel="stylesheet" href="assets/css/pages/client-dashboard.css?v=20260706d">
    <link rel="stylesheet" href="assets/css/client-sidebar-parity.css?v=20260418i">
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
                            <a class="nav-link" href="client-notifications.php">
                                <span class="nav-link__icon"><i class="bi bi-bell"></i></span>
                                <span>Notifications</span>
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
                <main class="dashboard-content client-dashboard-content">
                    <section class="dashboard-primary">
                        <section class="surface-card client-hero-card"<?= $clientHeroCardStyle !== '' ? ' style="' . $escape($clientHeroCardStyle) . '"' : '' ?>>
                            <div class="client-hero-card__content">
                                <p class="panel-heading__eyebrow" id="dashboardOverviewEyebrow">Event Planning</p>
                                <p class="client-hero-card__client" id="dashboardClientName"><?= $escape($clientDisplayName) ?></p>
                                <h2 id="dashboardOverviewTitle">Ready to plan your event?</h2>
                                <p class="client-hero-card__intro" id="dashboardOverviewIntro">Start a booking request and choose your preferred event date.</p>

                                <div class="client-hero-card__actions">
                                    <span class="status-pill status-pill--pending" id="dashboardOverviewStatusPill">Ready to start</span>
                                    <a class="client-hero-card__action" href="client-bookings.php" id="dashboardPrimaryAction">Book Event</a>
                                </div>

                                <p class="client-hero-card__next-step">
                                    <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
                                    <span id="dashboardOverviewNextStep">Next step: Choose your date and package</span>
                                </p>

                                <p class="client-hero-card__note" id="dashboardOverviewFootnote">Next step: Book Event.</p>
                            </div>

                            <div class="client-hero-card__media" aria-hidden="true"></div>
                        </section>
                    </section>
                </main>

                <?= emarioh_render_client_mobile_nav(basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'client-dashboard.php'))) ?>
            </div>
        </div>
    </div>

    <?= emarioh_render_vendor_runtime_assets(true); ?>
    <script src="assets/js/auth-api.js?v=20260419c"></script>
    <script src="assets/js/logout-confirmation.js?v=20260706c"></script>
    <script>
        window.EmariohServerClientPortalState = <?= $clientPortalStateJson ?>;
    </script>
    <script>
        window.EmariohServerPackageCatalog = <?= $packageCatalogJson ?>;
    </script>
    <script src="assets/js/package-catalog.js?v=20260412c"></script>
    <script src="assets/js/payment-settings-store.js?v=20260412a"></script>
    <script src="assets/js/client-portal-state.js?v=20260706b"></script>
    <script src="assets/js/pages/client-dashboard.js?v=20260419a"></script>
</body>
</html>
