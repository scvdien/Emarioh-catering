<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$db = emarioh_db();
$currentUser = emarioh_require_page_role('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emarioh Catering Services Website Inquiries</title>
    <?= emarioh_render_vendor_head_assets(); ?>
    <link rel="stylesheet" href="assets/css/messages.css?v=20260410e">
    <link rel="stylesheet" href="assets/css/admin-messages.css?v=20260418y">
</head>
<body class="admin-dashboard-page admin-messages-page admin-inquiries-page" data-auth-guard="admin">
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
                            <a class="nav-link active" href="admin-inquiries.php" aria-current="page"><span class="nav-link__icon"><i class="bi bi-envelope-paper"></i></span><span>Website Inquiries</span></a>
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
                        <button class="btn mobile-menu-button d-xl-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#dashboardSidebar" aria-controls="dashboardSidebar" aria-label="Open navigation">
                            <i class="bi bi-list"></i>
                        </button>
                        <div class="topbar-copy">
                            <p class="topbar-copy__eyebrow">Inbox</p>
                            <div class="admin-inquiries-title-row">
                                <h1 class="topbar-copy__title">Website Inquiries</h1>
                                <span class="admin-message-count admin-message-count--topbar" id="adminMessagesInboxCount">0 inquiries</span>
                            </div>
                            <p class="admin-message-topbar-summary" id="adminMessagesTopbarStatus">0 unread inquiries to review</p>
                        </div>
                    </div>
                </header>

                <main class="dashboard-content">
                    <section class="content-grid">
                        <div class="content-main">
                            <section class="surface-card surface-card--inbox">
                                <div class="panel-heading panel-heading--stacked">
                                    <div>
                                        <p class="panel-heading__eyebrow">Inbox</p>
                                        <h2>Customer inquiries</h2>
                                        <p class="admin-message-section-note">Open a message to view the inquiry details and email the customer.</p>
                                    </div>
                                </div>

                                <div class="admin-message-inbox-list" id="adminMessageInboxList"></div>

                                <div class="admin-message-empty" id="adminMessagesEmpty" hidden>
                                    <h3>No website inquiries yet</h3>
                                    <p>Messages submitted from the public contact form will appear here.</p>
                                </div>
                            </section>
                        </div>
                    </section>
                </main>

                <?= emarioh_render_admin_mobile_nav(basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'))) ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="adminInquiryModal" tabindex="-1" aria-labelledby="adminMessageDetailTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable admin-inquiry-modal__dialog">
            <div class="modal-content admin-inquiry-modal">
                <div class="modal-header admin-inquiry-modal__header">
                    <div>
                        <h2 class="modal-title" id="adminMessageDetailTitle">No inquiry selected</h2>
                        <p class="admin-message-detail-contact" id="adminMessageDetailContact">Choose an inquiry from the inbox to view the customer email and full message.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body admin-inquiry-modal__body">
                    <div class="admin-message-detail-meta admin-message-detail-meta--simple" id="adminMessageDetailMeta"></div>
                    <div class="screen-message-list" id="adminMessageDetailList"></div>
                </div>
                <div class="modal-footer admin-inquiry-modal__footer">
                    <button class="admin-message-action admin-message-action--danger" type="button" id="adminMessageDeleteButton">Delete Inquiry</button>
                    <a class="admin-message-action admin-message-action--primary" href="#" id="adminMessageEmailLink">Reply by Email</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade admin-delete-modal" id="adminInquiryDeleteModal" tabindex="-1" aria-labelledby="adminInquiryDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered admin-delete-modal__dialog">
            <div class="modal-content admin-delete-modal__content">
                <div class="modal-header admin-delete-modal__header">
                    <div>
                        <h2 class="admin-delete-modal__title" id="adminInquiryDeleteModalLabel">Delete Inquiry?</h2>
                    </div>
                </div>
                <div class="modal-body admin-delete-modal__body">
                    <p class="admin-delete-modal__text" id="adminInquiryDeleteModalText">Delete this inquiry? This action cannot be undone.</p>
                </div>
                <div class="modal-footer admin-delete-modal__footer">
                    <button class="admin-message-action admin-message-action--ghost" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="admin-message-action admin-message-action--danger" type="button" id="adminInquiryDeleteConfirmButton">Delete Inquiry</button>
                </div>
            </div>
        </div>
    </div>

    <?= emarioh_render_vendor_runtime_assets(true); ?>
    <script src="assets/js/auth-api.js"></script>
    <script src="assets/js/logout-confirmation.js"></script>
    <script src="assets/js/admin-inquiries.js?v=20260412k"></script>
</body>
</html>
