<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';

$db = emarioh_db();
$currentUser = emarioh_require_page_role('admin');
$notifications = emarioh_fetch_admin_notifications($db);
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

function emarioh_render_admin_notification_items(array $notifications, callable $escape): string
{
    if ($notifications === []) {
        return '
            <article class="admin-notification-empty">
                <span class="admin-notification-empty__icon" aria-hidden="true"><i class="bi bi-bell"></i></span>
                <div>
                    <h2>No notifications yet</h2>
                </div>
            </article>
        ';
    }

    $items = [];

    foreach ($notifications as $notification) {
        $isUnread = (string) ($notification['readStatus'] ?? 'unread') === 'unread';
        $title = (string) ($notification['title'] ?? 'Notification');
        $message = (string) ($notification['message'] ?? '');
        $reference = (string) ($notification['reference'] ?? 'No reference');
        $timeLabel = (string) ($notification['timeLabel'] ?? 'Just now');
        $href = (string) ($notification['href'] ?? 'admin-notifications.php');
        $icon = (string) ($notification['icon'] ?? 'bi-bell');
        $notificationId = (int) ($notification['id'] ?? 0);
        $type = (string) ($notification['type'] ?? 'system');
        $typeLabel = match ($type) {
            'new_booking' => 'Pending Review',
            'payment_received' => 'Payment Received',
            'manual_payment' => 'Manual Payment',
            'overdue_payment' => 'Payment Overdue',
            default => 'System Alert',
        };

        $items[] = '
            <article
                class="admin-notification-item ' . ($isUnread ? 'is-unread' : 'is-read') . '"
                role="group"
                tabindex="0"
                data-admin-notification-item
                data-read-status="' . $escape($isUnread ? 'unread' : 'read') . '"
                data-notification-id="' . $escape((string) $notificationId) . '"
                data-notification-title="' . $escape($title) . '"
                data-notification-message="' . $escape($message !== '' ? $message : 'Open this alert for more details.') . '"
                data-notification-reference="' . $escape($reference) . '"
                data-notification-time="' . $escape($timeLabel) . '"
                data-notification-href="' . $escape($href) . '"
                data-notification-icon="' . $escape($icon) . '"
                data-notification-status-label="' . $escape($typeLabel) . '"
                aria-label="Open notification: ' . $escape($title) . '"
            >
                <span class="admin-notification-item__icon" aria-hidden="true"><i class="bi ' . $escape($icon) . '"></i></span>
                <div class="admin-notification-item__body">
                    <div class="admin-notification-item__head">
                        <div>
                            <h2>' . $escape($title) . '</h2>
                        </div>
                    </div>
                    <p class="admin-notification-item__message">' . $escape($message !== '' ? $message : 'Open this alert for more details.') . '</p>
                    <div class="admin-notification-item__meta">
                        <span>' . $escape($reference) . '</span>
                        <span>' . $escape($timeLabel) . '</span>
                        <span>' . $escape($typeLabel) . '</span>
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
    <title>Emarioh Catering Services Admin Notifications</title>
    <?= emarioh_render_vendor_head_assets(); ?>
    <link rel="stylesheet" href="assets/css/index.css?v=20260714f">
    <link rel="stylesheet" href="assets/css/admin-mobile-notification.css?v=20260714d">
</head>
<body class="admin-dashboard-page admin-notifications-page" data-auth-guard="admin">
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
                            <?= emarioh_render_admin_notification_nav_link($db, true) ?>
                            <a class="nav-link" href="admin-events.php"><span class="nav-link__icon"><i class="bi bi-calendar-event"></i></span><span>Booking Calendar</span></a>
                            <a class="nav-link" href="admin-bookings.php"><span class="nav-link__icon"><i class="bi bi-journal-check"></i></span><span>Booking Management</span></a>
                            <a class="nav-link" href="admin-clients.php"><span class="nav-link__icon"><i class="bi bi-people"></i></span><span>Clients</span></a>
                            <a class="nav-link" href="admin-payments.php"><span class="nav-link__icon"><i class="bi bi-wallet2"></i></span><span>Payment</span></a>
                            <a class="nav-link" href="admin-inquiries.php"><span class="nav-link__icon"><i class="bi bi-envelope-paper"></i></span><span>Website Inquiries</span></a>
                            <a class="nav-link" href="admin-settings.php"><span class="nav-link__icon"><i class="bi bi-gear"></i></span><span>Settings</span></a>
                        </nav>

                        <div class="sidebar-footer mt-auto">
                            <a class="sidebar-logout" href="logout.php">
                                <span class="sidebar-logout__icon"><i class="bi bi-box-arrow-right"></i></span>
                                <span class="sidebar-logout__label">Log out</span>
                            </a>
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
                    <?= emarioh_render_admin_mobile_notification_button($db, true) ?>
                </header>

                <main class="dashboard-content admin-notification-content">
                    <section class="surface-card admin-notification-panel" aria-label="Admin notifications">
                        <div class="admin-notification-list" data-notification-list>
                            <?= emarioh_render_admin_notification_items($notifications, $escape) ?>
                        </div>
                    </section>
                </main>

                <?= emarioh_render_admin_mobile_nav('admin-notifications.php') ?>
            </div>
        </div>
    </div>

    <div class="modal fade booking-modal admin-notification-modal" id="adminNotificationModal" tabindex="-1" aria-labelledby="adminNotificationModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content booking-modal__content">
                <div class="modal-header booking-modal__header admin-notification-modal__header">
                    <span class="admin-notification-modal__icon" aria-hidden="true"><i class="bi bi-bell" id="adminNotificationModalIcon"></i></span>
                    <div>
                        <h2 class="booking-modal__title" id="adminNotificationModalTitle">Notification</h2>
                        <p class="admin-notification-modal__time" id="adminNotificationModalTime">Just now</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body booking-modal__body admin-notification-modal__body">
                    <p id="adminNotificationModalMessage">Notification details</p>
                    <div class="admin-notification-modal__meta">
                        <span>Reference</span>
                        <strong id="adminNotificationModalReference">No reference</strong>
                    </div>
                </div>
                <div class="modal-footer booking-modal__footer">
                    <a class="action-btn action-btn--primary text-decoration-none" id="adminNotificationModalOpenLink" href="admin-notifications.php">Open record</a>
                    <button class="action-btn action-btn--ghost" type="button" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="notificationDeleteModal" tabindex="-1" aria-labelledby="notificationDeleteModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="notificationDeleteModalTitle">Delete notification?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">This notification will be removed from your list.</div>
                <div class="modal-footer">
                    <button class="action-btn action-btn--ghost" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="action-btn btn-danger" type="button" data-confirm-notification-delete>Delete</button>
                </div>
            </div>
        </div>
    </div>

    <?= emarioh_render_vendor_runtime_assets(true); ?>
    <script src="assets/js/auth-api.js"></script>
    <script src="assets/js/logout-confirmation.js"></script>
    <script src="assets/js/pages/admin-notifications.js?v=20260714g"></script>
</body>
</html>
