<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';

$db = emarioh_db();
$currentUser = emarioh_require_page_role('client');
$notifications = emarioh_fetch_client_notifications($db, (int) $currentUser['id']);
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

function emarioh_render_client_notification_items(array $notifications, callable $escape): string
{
    if ($notifications === []) {
        return '
            <article class="client-notification-empty">
                <span class="client-notification-empty__icon" aria-hidden="true"><i class="bi bi-bell"></i></span>
                <div>
                    <h2>No notifications yet</h2>
                    <p>Your booking and reservation updates will appear here after you submit a request.</p>
                </div>
            </article>
        ';
    }

    $items = [];

    foreach ($notifications as $notification) {
        $statusClass = (string) ($notification['statusClass'] ?? 'pending');
        $icon = (string) ($notification['icon'] ?? 'bi-bell-fill');
        $readStatus = (string) ($notification['readStatus'] ?? 'unread');
        $isUnread = $readStatus === 'unread';
        $title = (string) ($notification['title'] ?? 'Booking notification');
        $message = (string) ($notification['message'] ?? 'Your booking was updated.');
        $reference = (string) ($notification['reference'] ?? 'Reference pending');
        $timeLabel = (string) ($notification['timeLabel'] ?? 'Just now');
        $statusLabel = (string) ($notification['statusLabel'] ?? 'Update');
        $eventLabel = (string) ($notification['eventLabel'] ?? 'Booking');

        $items[] = '
            <article class="client-notification-item client-notification-item--' . $escape($statusClass) . ($isUnread ? ' is-unread' : ' is-read') . '" role="group" tabindex="0" data-notification-item data-notification-open data-read-status="' . $escape($readStatus) . '" data-notification-id="' . $escape((string) ($notification['id'] ?? 0)) . '" data-notification-title="' . $escape($title) . '" data-notification-message="' . $escape($message) . '" data-notification-reference="' . $escape($reference) . '" data-notification-time="' . $escape($timeLabel) . '" data-notification-status-label="' . $escape($statusLabel) . '" data-notification-event="' . $escape($eventLabel) . '" aria-label="Open notification: ' . $escape($title) . '">
                <span class="client-notification-item__icon" aria-hidden="true"><i class="bi ' . $escape($icon) . '"></i></span>
                <div class="client-notification-item__body">
                    <div class="client-notification-item__head">
                        <div>
                            <h2>' . ($isUnread ? '<span class="client-notification-dot" aria-hidden="true"></span>' : '') . $escape($title) . '</h2>
                        </div>
                    </div>
                    <p class="client-notification-item__message">' . $escape($message) . '</p>
                    <div class="client-notification-item__meta">
                        <span>' . $escape($reference) . '</span>
                        <span>' . $escape($timeLabel) . '</span>
                        <span>' . $escape($statusLabel) . '</span>
                    </div>
                </div>
                <button class="notification-delete-button" type="button" data-delete-notification aria-label="Delete notification: ' . $escape($title) . '" title="Delete notification"><i class="bi bi-trash3" aria-hidden="true"></i></button>
            </article>
        ';
    }

    return implode("\n", $items);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emarioh Catering Services Client Notifications</title>
    <?= emarioh_render_vendor_head_assets(); ?>
    <link rel="stylesheet" href="assets/css/index.css?v=20260714f">
    <link rel="stylesheet" href="assets/css/pages/client-dashboard.css?v=20260418d">
    <link rel="stylesheet" href="assets/css/client-sidebar-parity.css?v=20260710a">
    <link rel="stylesheet" href="assets/css/pages/client-notifications.css?v=20260714e">
</head>
<body class="dashboard-page client-dashboard-page client-notifications-page client-page--sticky-topbar" data-auth-guard="client">
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
                            <?= emarioh_render_client_notification_nav_link($db, (int) $currentUser['id'], true) ?>
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
                        <div class="topbar-copy">
                            <h1 class="topbar-copy__title">Notifications</h1>
                        </div>
                    </div>
                </header>

                <main class="dashboard-content client-dashboard-content">
                    <section class="client-notification-panel" aria-label="Notifications">
                        <div class="client-notification-list" data-notification-list>
                            <?= emarioh_render_client_notification_items($notifications, $escape) ?>
                        </div>
                    </section>
                </main>

                <?= emarioh_render_client_mobile_nav(basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'client-notifications.php'))) ?>
            </div>
        </div>
    </div>

    <div class="modal fade client-notification-modal" id="clientNotificationModal" tabindex="-1" aria-labelledby="clientNotificationModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content client-notification-modal__content">
                <div class="modal-header client-notification-modal__header">
                    <div>
                        <span class="client-notification-modal__eyebrow" data-notification-modal-status>Booking Update</span>
                        <h2 class="modal-title client-notification-modal__title" id="clientNotificationModalTitle" data-notification-modal-title>Notification</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body client-notification-modal__body">
                    <p data-notification-modal-message>Your booking was updated.</p>
                    <div class="client-notification-modal__meta">
                        <div>
                            <span>Reference</span>
                            <strong data-notification-modal-reference>Reference pending</strong>
                        </div>
                        <div>
                            <span>Date</span>
                            <strong data-notification-modal-time>Just now</strong>
                        </div>
                        <div>
                            <span>Event</span>
                            <strong data-notification-modal-event>Booking</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="notificationDeleteModal" tabindex="-1" aria-labelledby="notificationDeleteModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content client-notification-modal__content">
                <div class="modal-header client-notification-modal__header">
                    <h2 class="modal-title client-notification-modal__title" id="notificationDeleteModalTitle">Delete notification?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body client-notification-modal__body"><p>This notification will be removed from your list.</p></div>
                <div class="modal-footer">
                    <button class="action-btn action-btn--ghost" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="action-btn btn-danger" type="button" data-confirm-notification-delete>Delete</button>
                </div>
            </div>
        </div>
    </div>

    <?= emarioh_render_vendor_runtime_assets(true); ?>
    <script src="assets/js/auth-api.js?v=20260419c"></script>
    <script src="assets/js/logout-confirmation.js?v=20260706c"></script>
    <script src="assets/js/pages/client-notifications.js?v=20260714g"></script>
</body>
</html>
