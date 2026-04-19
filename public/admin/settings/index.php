<?php
declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';

$db = emarioh_db();
$currentUser = emarioh_require_page_role('admin');
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$adminDisplayName = trim((string) ($currentUser['full_name'] ?? '')) ?: 'Admin Profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emarioh Catering Services Settings Menu</title>
    <?= emarioh_render_vendor_head_assets(); ?>
    <link rel="stylesheet" href="assets/css/index.css?v=20260418o">
    <link rel="stylesheet" href="assets/css/package-admin.css">
    <link rel="stylesheet" href="assets/css/pages/admin-settings.css?v=20260418z">
</head>
<body class="admin-dashboard-page admin-settings-page admin-settings-menu-page" data-auth-guard="admin">
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
                            <a class="nav-link active" href="admin-settings-menu.php" aria-current="page"><span class="nav-link__icon"><i class="bi bi-gear"></i></span><span>Settings</span></a>
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
                            <p class="settings-mobile-topbar__eyebrow">Settings</p>
                            <h1 class="topbar-copy__title">Settings Menu</h1>
                            <p class="settings-mobile-topbar__text">Choose which part of the admin settings you want to manage.</p>
                        </div>
                    </div>
                </header>

                <main class="dashboard-content settings-dashboard-content">
                    <section class="surface-card settings-profile-hub settings-menu-page__hub" aria-labelledby="settingsMenuPageTitle">
                        <div class="settings-profile-hub__header">
                            <span class="settings-profile-hub__icon" aria-hidden="true"><i class="bi bi-person-circle"></i></span>
                            <div class="settings-profile-hub__copy">
                                <p class="settings-profile-hub__eyebrow">Administrator</p>
                                <h2 id="settingsMenuPageTitle"><?= $escape($adminDisplayName) ?></h2>
                                <p class="settings-profile-hub__summary">Review account details, client updates, and admin tools from one place.</p>
                            </div>
                        </div>

                        <div class="settings-profile-hub__grid">
                            <a class="settings-profile-shortcut" href="admin-clients.php">
                                <span class="settings-profile-shortcut__content">
                                    <span class="settings-profile-shortcut__icon" aria-hidden="true"><i class="bi bi-people"></i></span>
                                    <span class="settings-profile-shortcut__copy">
                                        <strong>Clients</strong>
                                        <span>View client records and bookings.</span>
                                    </span>
                                </span>
                                <span class="settings-profile-shortcut__chevron" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
                            </a>

                            <a class="settings-profile-shortcut" href="admin-inquiries.php">
                                <span class="settings-profile-shortcut__content">
                                    <span class="settings-profile-shortcut__icon" aria-hidden="true"><i class="bi bi-envelope-paper"></i></span>
                                    <span class="settings-profile-shortcut__copy">
                                        <strong>Inbox</strong>
                                        <span>Check inquiries and follow-ups.</span>
                                    </span>
                                </span>
                                <span class="settings-profile-shortcut__chevron" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
                            </a>

                            <a class="settings-profile-shortcut" href="admin-settings.php">
                                <span class="settings-profile-shortcut__content">
                                    <span class="settings-profile-shortcut__icon" aria-hidden="true"><i class="bi bi-gear"></i></span>
                                    <span class="settings-profile-shortcut__copy">
                                        <strong>Settings</strong>
                                        <span>Manage account, payments, and SMS.</span>
                                    </span>
                                </span>
                                <span class="settings-profile-shortcut__chevron" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
                            </a>
                        </div>

                        <a class="settings-profile-logout" href="logout.php">
                            <span class="settings-profile-logout__content">
                                <span class="settings-profile-shortcut__icon" aria-hidden="true"><i class="bi bi-box-arrow-right"></i></span>
                                <span class="settings-profile-shortcut__copy">
                                    <strong>Log Out</strong>
                                    <span>Sign out of the admin account.</span>
                                </span>
                            </span>
                            <span class="settings-profile-shortcut__chevron" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
                        </a>
                    </section>
                </main>
            </div>
        </div>
    </div>

    <?= emarioh_render_vendor_runtime_assets(true); ?>
    <script src="assets/js/auth-api.js"></script>
    <script src="assets/js/logout-confirmation.js?v=20260418a"></script>
</body>
</html>
