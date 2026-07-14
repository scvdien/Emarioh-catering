<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';

$db = emarioh_db();
$currentUser = emarioh_require_page_role('admin');

$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

function emarioh_dashboard_month_window(): array
{
    $start = (new DateTimeImmutable('first day of this month'))->setTime(0, 0);

    return [
        'start' => $start,
        'next' => $start->modify('first day of next month'),
        'label' => $start->format('F Y'),
    ];
}

function emarioh_dashboard_year_window(): array
{
    $start = (new DateTimeImmutable('first day of January this year'))->setTime(0, 0);

    return [
        'start' => $start,
        'next' => $start->modify('first day of January next year'),
        'label' => $start->format('Y'),
    ];
}

function emarioh_fetch_dashboard_period_analytics(PDO $db, DateTimeImmutable $periodStart, DateTimeImmutable $nextPeriodStart): array
{
    $params = [
        ':period_start' => $periodStart->format('Y-m-d H:i:s'),
        ':next_period_start' => $nextPeriodStart->format('Y-m-d H:i:s'),
        ':event_period_start' => $periodStart->format('Y-m-d'),
        ':next_event_period_start' => $nextPeriodStart->format('Y-m-d'),
    ];

    $salesStatement = $db->prepare("
        SELECT
            COALESCE(SUM(pi.amount_due), 0) AS booked_sales,
            COUNT(DISTINCT br.id) AS confirmed_bookings
        FROM booking_requests br
        LEFT JOIN payment_invoices pi
            ON pi.booking_id = br.id
            AND pi.status <> 'cancelled'
        WHERE br.event_date >= :event_period_start
          AND br.event_date < :next_event_period_start
          AND br.status IN ('approved', 'completed')
    ");
    $salesStatement->execute([
        ':event_period_start' => $params[':event_period_start'],
        ':next_event_period_start' => $params[':next_event_period_start'],
    ]);
    $salesRow = $salesStatement->fetch() ?: [];

    $collectionStatement = $db->prepare("
        SELECT COALESCE(SUM(amount_paid), 0) AS collected_sales
        FROM payment_invoices
        WHERE amount_paid > 0
          AND COALESCE(last_payment_at, updated_at, created_at) >= :period_start
          AND COALESCE(last_payment_at, updated_at, created_at) < :next_period_start
          AND status IN ('approved', 'review')
    ");
    $collectionStatement->execute([
        ':period_start' => $params[':period_start'],
        ':next_period_start' => $params[':next_period_start'],
    ]);
    $collectionRow = $collectionStatement->fetch() ?: [];

    $celebrationStatement = $db->prepare("
        SELECT
            br.event_type,
            COUNT(*) AS booking_count
        FROM booking_requests br
        WHERE br.event_date >= :event_period_start
          AND br.event_date < :next_event_period_start
          AND br.status IN ('approved', 'completed')
        GROUP BY br.event_type
        ORDER BY booking_count DESC, br.event_type ASC
        LIMIT 5
    ");
    $celebrationStatement->execute([
        ':event_period_start' => $params[':event_period_start'],
        ':next_event_period_start' => $params[':next_event_period_start'],
    ]);

    $celebrationRows = [];
    foreach ($celebrationStatement->fetchAll() as $row) {
        $eventType = trim((string) ($row['event_type'] ?? ''));
        $bookingCount = (int) ($row['booking_count'] ?? 0);

        if ($eventType !== '' && $bookingCount > 0) {
            $celebrationRows[] = [
                'event_type' => $eventType,
                'booking_count' => $bookingCount,
            ];
        }
    }

    return [
        'booked_sales' => (float) ($salesRow['booked_sales'] ?? 0),
        'collected_sales' => (float) ($collectionRow['collected_sales'] ?? 0),
        'confirmed_bookings' => (int) ($salesRow['confirmed_bookings'] ?? 0),
        'celebrations' => $celebrationRows,
    ];
}

$dashboardMonth = emarioh_dashboard_month_window();
$dashboardYear = emarioh_dashboard_year_window();
$dashboardAnalytics = emarioh_fetch_dashboard_period_analytics($db, $dashboardMonth['start'], $dashboardMonth['next']);
$dashboardYearAnalytics = emarioh_fetch_dashboard_period_analytics($db, $dashboardYear['start'], $dashboardYear['next']);
$topCelebration = $dashboardAnalytics['celebrations'][0] ?? null;
$topCelebrationCount = (int) ($topCelebration['booking_count'] ?? 0);
$maxCelebrationCount = max(1, $topCelebrationCount);
$bookedSalesValue = (float) $dashboardAnalytics['booked_sales'];
$collectedSalesValue = (float) $dashboardAnalytics['collected_sales'];
$collectionRate = $bookedSalesValue > 0 ? min(100, max(0, ($collectedSalesValue / $bookedSalesValue) * 100)) : 0;
$collectionRateLabel = number_format($collectionRate, 0);
$topYearCelebration = $dashboardYearAnalytics['celebrations'][0] ?? null;
$topYearCelebrationCount = (int) ($topYearCelebration['booking_count'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emarioh Catering Services Admin Dashboard</title>
    <?= emarioh_render_vendor_head_assets(); ?>
    <link rel="stylesheet" href="assets/css/index.css?v=20260709b">
    <link rel="stylesheet" href="assets/css/pages/admin-events.css?v=20260709n">
    <link rel="stylesheet" href="assets/css/admin-mobile-notification.css?v=20260710d">
</head>
<body class="admin-dashboard-page admin-dashboard-home" data-auth-guard="admin">
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
                            <a class="nav-link active" href="index.php" aria-current="page"><span class="nav-link__icon"><i class="bi bi-grid-1x2-fill"></i></span><span>Dashboard</span></a>
                            <?= emarioh_render_admin_notification_nav_link($db) ?>
                            <a class="nav-link" href="admin-events.php"><span class="nav-link__icon"><i class="bi bi-calendar-event"></i></span><span>Booking Calendar</span></a>
                            <a class="nav-link" href="admin-bookings.php"><span class="nav-link__icon"><i class="bi bi-journal-check"></i></span><span>Booking Management</span></a>
                            <a class="nav-link" href="admin-clients.php"><span class="nav-link__icon"><i class="bi bi-people"></i></span><span>Clients</span></a>
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
                            <h1 class="topbar-copy__title">Admin Dashboard</h1>
                        </div>
                    </div>
                    <?= emarioh_render_admin_mobile_notification_button($db) ?>
                </header>

                <main class="dashboard-content dashboard-content--analytics">
                    <section class="dashboard-analytics" aria-label="Monthly sales analytics">
                        <div class="dashboard-analytics__hero">
                            <div class="dashboard-analytics__copy">
                                <span class="dashboard-analytics__eyebrow"><i class="bi bi-bar-chart-line"></i> Data Analytics</span>
                                <h2><?= $escape((string) $dashboardMonth['label']) ?> Performance</h2>
                                <p>Sales, collections, and most availed celebrations for the current month.</p>
                            </div>
                            <div class="dashboard-analytics__hero-actions">
                                <button class="analytics-card__action analytics-card__action--hero" type="button" data-bs-toggle="modal" data-bs-target="#celebrationTrendModal">
                                    Trend by Celebration
                                </button>
                                <div class="dashboard-analytics__month">
                                    <span>Current Month</span>
                                    <strong><?= $escape((string) $dashboardMonth['label']) ?></strong>
                                </div>
                            </div>
                        </div>

                        <div class="dashboard-analytics__layout">
                            <div class="analytics-sales-panel">
                                <div class="analytics-sales-panel__top">
                                    <span class="analytics-card__icon analytics-card__icon--large" aria-hidden="true"><i class="bi bi-graph-up-arrow"></i></span>
                                    <div>
                                        <p class="analytics-card__label">Booked Sales</p>
                                        <strong><?= $escape(emarioh_format_money_amount($bookedSalesValue)) ?></strong>
                                        <span>Confirmed event value this month</span>
                                    </div>
                                </div>

                                <div class="analytics-progress">
                                    <div class="analytics-progress__row">
                                        <span>Collection Progress</span>
                                        <strong><?= $escape($collectionRateLabel) ?>%</strong>
                                    </div>
                                    <div class="analytics-progress__track" aria-hidden="true">
                                        <span style="width: <?= $escape(number_format($collectionRate, 2, '.', '')) ?>%;"></span>
                                    </div>
                                    <p><?= $escape(emarioh_format_money_amount($collectedSalesValue)) ?> collected from posted payments.</p>
                                </div>
                            </div>

                            <div class="dashboard-analytics__grid">
                                <article class="analytics-card">
                                    <span class="analytics-card__icon" aria-hidden="true"><i class="bi bi-cash-coin"></i></span>
                                    <div>
                                        <p class="analytics-card__label">Collected Payments</p>
                                        <strong><?= $escape(emarioh_format_money_amount($collectedSalesValue)) ?></strong>
                                        <span>Payments posted this month</span>
                                    </div>
                                </article>

                                <article class="analytics-card">
                                    <span class="analytics-card__icon" aria-hidden="true"><i class="bi bi-stars"></i></span>
                                    <div>
                                        <p class="analytics-card__label">Top Celebration</p>
                                        <strong><?= $escape((string) ($topCelebration['event_type'] ?? 'No data yet')) ?></strong>
                                        <span><?= $topCelebrationCount > 0 ? $escape($topCelebrationCount . ' confirmed booking' . ($topCelebrationCount === 1 ? '' : 's')) : 'No confirmed bookings yet' ?></span>
                                    </div>
                                </article>

                                <article class="analytics-card">
                                    <span class="analytics-card__icon" aria-hidden="true"><i class="bi bi-clipboard2-check"></i></span>
                                    <div>
                                        <p class="analytics-card__label">Confirmed Bookings</p>
                                        <strong><?= $escape((string) $dashboardAnalytics['confirmed_bookings']) ?></strong>
                                        <span>Approved or completed events</span>
                                    </div>
                                </article>
                            </div>
                        </div>

                        <div class="year-analytics">
                            <div class="year-analytics__header">
                                <span>Year Overview</span>
                                <strong><?= $escape((string) $dashboardYear['label']) ?> Year-to-Date</strong>
                            </div>
                            <div class="year-analytics__grid">
                                <article>
                                    <span class="year-analytics__icon" aria-hidden="true"><i class="bi bi-graph-up-arrow"></i></span>
                                    <div class="year-analytics__body">
                                        <span>YTD Booked Sales</span>
                                        <strong><?= $escape(emarioh_format_money_amount((float) $dashboardYearAnalytics['booked_sales'])) ?></strong>
                                    </div>
                                </article>
                                <article>
                                    <span class="year-analytics__icon" aria-hidden="true"><i class="bi bi-cash-coin"></i></span>
                                    <div class="year-analytics__body">
                                        <span>YTD Collected</span>
                                        <strong><?= $escape(emarioh_format_money_amount((float) $dashboardYearAnalytics['collected_sales'])) ?></strong>
                                    </div>
                                </article>
                                <article>
                                    <span class="year-analytics__icon" aria-hidden="true"><i class="bi bi-clipboard2-check"></i></span>
                                    <div class="year-analytics__body">
                                        <span>YTD Confirmed</span>
                                        <strong><?= $escape((string) $dashboardYearAnalytics['confirmed_bookings']) ?></strong>
                                    </div>
                                </article>
                                <article>
                                    <span class="year-analytics__icon" aria-hidden="true"><i class="bi bi-stars"></i></span>
                                    <div class="year-analytics__body">
                                        <span>Top This Year</span>
                                        <strong><?= $escape((string) ($topYearCelebration['event_type'] ?? 'No data yet')) ?></strong>
                                        <small><?= $topYearCelebrationCount > 0 ? $escape($topYearCelebrationCount . ' booking' . ($topYearCelebrationCount === 1 ? '' : 's')) : 'No confirmed bookings yet' ?></small>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </section>

                </main>

                <?= emarioh_render_admin_mobile_nav(basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'))) ?>
            </div>
        </div>
    </div>

    <div class="modal fade celebration-trend-modal" id="celebrationTrendModal" tabindex="-1" aria-labelledby="celebrationTrendModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <span class="celebration-modal__eyebrow">Trend by Celebration</span>
                        <h2 class="modal-title" id="celebrationTrendModalLabel">Most Availed Celebrations</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="celebration-analytics celebration-analytics--modal">
                        <div class="celebration-analytics__header">
                            <div>
                                <span><?= $escape((string) $dashboardMonth['label']) ?></span>
                                <h3>Confirmed bookings this month</h3>
                            </div>
                        </div>

                        <?php if ($dashboardAnalytics['celebrations'] === []): ?>
                            <p class="celebration-analytics__empty">No confirmed celebration bookings yet for <?= $escape((string) $dashboardMonth['label']) ?>.</p>
                        <?php else: ?>
                            <div class="celebration-analytics__list">
                                <?php foreach ($dashboardAnalytics['celebrations'] as $index => $celebration): ?>
                                    <?php
                                    $celebrationCount = (int) ($celebration['booking_count'] ?? 0);
                                    $barWidth = min(100, max(8, ($celebrationCount / $maxCelebrationCount) * 100));
                                    ?>
                                    <div class="celebration-analytics__item">
                                        <span class="celebration-analytics__rank"><?= $escape((string) ($index + 1)) ?></span>
                                        <div class="celebration-analytics__body">
                                            <div class="celebration-analytics__row">
                                                <strong><?= $escape((string) ($celebration['event_type'] ?? 'Event')) ?></strong>
                                                <span><?= $escape((string) $celebrationCount) ?> booking<?= $celebrationCount === 1 ? '' : 's' ?></span>
                                            </div>
                                            <div class="celebration-analytics__bar" aria-hidden="true">
                                                <span style="width: <?= $escape(number_format($barWidth, 2, '.', '')) ?>%;"></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?= emarioh_render_vendor_runtime_assets(true); ?>
    <script src="assets/js/auth-api.js"></script>
    <script src="assets/js/logout-confirmation.js"></script>
    <script src="assets/js/pages/index.js?v=20260417c"></script>
</body>
</html>













