<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';

$db = emarioh_db();
$currentUser = emarioh_require_page_role('admin');

$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

function emarioh_dashboard_fetch_period_totals(PDO $db, DateTimeImmutable $start, DateTimeImmutable $next): array
{
    $statement = $db->prepare("
        SELECT
            COALESCE(SUM(pi.amount_due), 0) AS booked_sales,
            COALESCE(SUM(
                CASE
                    WHEN pi.status IN ('approved', 'review')
                        THEN LEAST(pi.amount_due, GREATEST(0, pi.amount_paid))
                    ELSE 0
                END
            ), 0) AS collected_sales,
            COALESCE(SUM(
                GREATEST(0, COALESCE(pi.balance_due, pi.amount_due - pi.amount_paid))
            ), 0) AS remaining_balance,
            COUNT(DISTINCT br.id) AS confirmed_bookings
        FROM booking_requests br
        LEFT JOIN payment_invoices pi
            ON pi.booking_id = br.id
            AND pi.status <> 'cancelled'
        WHERE br.event_date >= :start_date
          AND br.event_date < :next_date
          AND br.status IN ('approved', 'completed')
    ");
    $statement->execute([
        ':start_date' => $start->format('Y-m-d'),
        ':next_date' => $next->format('Y-m-d'),
    ]);
    $row = $statement->fetch() ?: [];

    return [
        'booked_sales' => (float) ($row['booked_sales'] ?? 0),
        'collected_sales' => (float) ($row['collected_sales'] ?? 0),
        'remaining_balance' => (float) ($row['remaining_balance'] ?? 0),
        'confirmed_bookings' => (int) ($row['confirmed_bookings'] ?? 0),
    ];
}

function emarioh_dashboard_fetch_celebrations(PDO $db, DateTimeImmutable $start, DateTimeImmutable $next): array
{
    $statement = $db->prepare("
        SELECT
            br.event_type,
            COUNT(DISTINCT br.id) AS booking_count,
            COALESCE(SUM(pi.amount_due), 0) AS booked_sales
        FROM booking_requests br
        LEFT JOIN payment_invoices pi
            ON pi.booking_id = br.id
            AND pi.status <> 'cancelled'
        WHERE br.event_date >= :start_date
          AND br.event_date < :next_date
          AND br.status IN ('approved', 'completed')
        GROUP BY br.event_type
        ORDER BY booking_count DESC, booked_sales DESC, br.event_type ASC
        LIMIT 5
    ");
    $statement->execute([
        ':start_date' => $start->format('Y-m-d'),
        ':next_date' => $next->format('Y-m-d'),
    ]);

    $rows = [];
    foreach ($statement->fetchAll() as $row) {
        $eventType = trim((string) ($row['event_type'] ?? ''));
        $bookingCount = (int) ($row['booking_count'] ?? 0);

        if ($eventType !== '' && $bookingCount > 0) {
            $rows[] = [
                'eventType' => $eventType,
                'bookingCount' => $bookingCount,
                'bookedSales' => (float) ($row['booked_sales'] ?? 0),
            ];
        }
    }

    return $rows;
}

function emarioh_dashboard_fetch_monthly_map(PDO $db, DateTimeImmutable $start, DateTimeImmutable $next): array
{
    $statement = $db->prepare("
        SELECT
            DATE_FORMAT(br.event_date, '%Y-%m') AS month_key,
            COALESCE(SUM(pi.amount_due), 0) AS booked_sales,
            COALESCE(SUM(
                CASE
                    WHEN pi.status IN ('approved', 'review')
                        THEN LEAST(pi.amount_due, GREATEST(0, pi.amount_paid))
                    ELSE 0
                END
            ), 0) AS collected_sales,
            COALESCE(SUM(
                GREATEST(0, COALESCE(pi.balance_due, pi.amount_due - pi.amount_paid))
            ), 0) AS remaining_balance,
            COUNT(DISTINCT br.id) AS confirmed_bookings
        FROM booking_requests br
        LEFT JOIN payment_invoices pi
            ON pi.booking_id = br.id
            AND pi.status <> 'cancelled'
        WHERE br.event_date >= :start_date
          AND br.event_date < :next_date
          AND br.status IN ('approved', 'completed')
        GROUP BY DATE_FORMAT(br.event_date, '%Y-%m')
    ");
    $statement->execute([
        ':start_date' => $start->format('Y-m-d'),
        ':next_date' => $next->format('Y-m-d'),
    ]);

    $map = [];
    foreach ($statement->fetchAll() as $row) {
        $key = (string) ($row['month_key'] ?? '');
        if ($key !== '') {
            $map[$key] = [
                'booked' => (float) ($row['booked_sales'] ?? 0),
                'collected' => (float) ($row['collected_sales'] ?? 0),
                'remaining' => (float) ($row['remaining_balance'] ?? 0),
                'bookings' => (int) ($row['confirmed_bookings'] ?? 0),
            ];
        }
    }

    return $map;
}

function emarioh_dashboard_fetch_weekly_map(PDO $db, DateTimeImmutable $monthStart, DateTimeImmutable $nextMonthStart): array
{
    $statement = $db->prepare("
        SELECT
            LEAST(5, FLOOR((DAY(br.event_date) - 1) / 7) + 1) AS week_no,
            COALESCE(SUM(pi.amount_due), 0) AS booked_sales,
            COALESCE(SUM(
                CASE
                    WHEN pi.status IN ('approved', 'review')
                        THEN LEAST(pi.amount_due, GREATEST(0, pi.amount_paid))
                    ELSE 0
                END
            ), 0) AS collected_sales,
            COALESCE(SUM(
                GREATEST(0, COALESCE(pi.balance_due, pi.amount_due - pi.amount_paid))
            ), 0) AS remaining_balance,
            COUNT(DISTINCT br.id) AS confirmed_bookings
        FROM booking_requests br
        LEFT JOIN payment_invoices pi
            ON pi.booking_id = br.id
            AND pi.status <> 'cancelled'
        WHERE br.event_date >= :start_date
          AND br.event_date < :next_date
          AND br.status IN ('approved', 'completed')
        GROUP BY LEAST(5, FLOOR((DAY(br.event_date) - 1) / 7) + 1)
    ");
    $statement->execute([
        ':start_date' => $monthStart->format('Y-m-d'),
        ':next_date' => $nextMonthStart->format('Y-m-d'),
    ]);

    $map = [];
    foreach ($statement->fetchAll() as $row) {
        $weekNo = (int) ($row['week_no'] ?? 0);
        if ($weekNo >= 1 && $weekNo <= 5) {
            $map[$weekNo] = [
                'booked' => (float) ($row['booked_sales'] ?? 0),
                'collected' => (float) ($row['collected_sales'] ?? 0),
                'remaining' => (float) ($row['remaining_balance'] ?? 0),
                'bookings' => (int) ($row['confirmed_bookings'] ?? 0),
            ];
        }
    }

    return $map;
}

function emarioh_dashboard_compute_sales_delta(float $current, float $previous, string $detailText): array
{
    if ($previous > 0.005) {
        $pct = (($current - $previous) / $previous) * 100;

        if (abs($pct) < 0.05) {
            return [
                'direction' => 'flat',
                'badgeText' => '0%',
                'detailText' => $detailText,
            ];
        }

        return [
            'direction' => $pct > 0 ? 'up' : 'down',
            'badgeText' => ($pct > 0 ? '+' : '') . number_format($pct, 1) . '%',
            'detailText' => $detailText,
        ];
    }

    if ($current > 0.005) {
        return [
            'direction' => 'up',
            'badgeText' => 'New',
            'detailText' => $detailText,
        ];
    }

    return [
        'direction' => 'flat',
        'badgeText' => '0%',
        'detailText' => $detailText,
    ];
}

function emarioh_dashboard_compute_count_delta(int $current, int $previous, string $detailText): array
{
    $diff = $current - $previous;

    if ($diff > 0) {
        return [
            'direction' => 'up',
            'badgeText' => '+' . $diff,
            'detailText' => $detailText,
        ];
    }

    if ($diff < 0) {
        return [
            'direction' => 'down',
            'badgeText' => (string) $diff,
            'detailText' => $detailText,
        ];
    }

    return [
        'direction' => 'flat',
        'badgeText' => 'Same',
        'detailText' => $detailText,
    ];
}

function emarioh_dashboard_build_monthly_series(
    DateTimeImmutable $firstMonth,
    int $monthCount,
    array $monthlyMap,
    string $currentMonthKey
): array {
    $series = [];

    for ($i = 0; $i < $monthCount; $i++) {
        $cursor = $firstMonth->modify('+' . $i . ' months');
        $prevCursor = $cursor->modify('-1 month');
        $key = $cursor->format('Y-m');
        $prevKey = $prevCursor->format('Y-m');

        $bucket = $monthlyMap[$key] ?? ['booked' => 0.0, 'collected' => 0.0, 'remaining' => 0.0, 'bookings' => 0];
        $prevBucket = $monthlyMap[$prevKey] ?? ['booked' => 0.0, 'collected' => 0.0, 'remaining' => 0.0, 'bookings' => 0];
        $delta = emarioh_dashboard_compute_sales_delta(
            (float) $bucket['booked'],
            (float) $prevBucket['booked'],
            'vs ' . $prevCursor->format('M')
        );

        $series[] = [
            'key' => $key,
            'shortLabel' => $cursor->format('M'),
            'fullLabel' => $cursor->format('F Y'),
            'booked' => (float) $bucket['booked'],
            'collected' => (float) $bucket['collected'],
            'remaining' => (float) $bucket['remaining'],
            'bookings' => (int) $bucket['bookings'],
            'deltaDirection' => $delta['direction'],
            'deltaText' => $delta['badgeText'],
            'deltaRefLabel' => $delta['detailText'],
            'isCurrent' => $key === $currentMonthKey,
        ];
    }

    return $series;
}

function emarioh_dashboard_summarize_series(array $series, string $avgLabel): array
{
    $peakLabel = 'No sales yet';
    $peakValue = 0.0;
    $sum = 0.0;

    foreach ($series as $item) {
        $booked = (float) ($item['booked'] ?? 0);
        $sum += $booked;

        if ($booked > $peakValue) {
            $peakValue = $booked;
            $peakLabel = (string) ($item['fullLabel'] ?? $item['shortLabel'] ?? '');
        }
    }

    $count = max(1, count($series));

    return [
        'peakLabel' => $peakLabel,
        'peakValue' => $peakValue,
        'avgLabel' => $avgLabel,
        'avgValue' => $sum / $count,
    ];
}

// Date windows
$today = new DateTimeImmutable('today');
$monthStart = $today->modify('first day of this month')->setTime(0, 0);
$nextMonthStart = $monthStart->modify('first day of next month');
$prevMonthStart = $monthStart->modify('-1 month');
$currentMonthKey = $monthStart->format('Y-m');

$sixMonthsStart = $monthStart->modify('-5 months');
$sixMonthsPrevStart = $sixMonthsStart->modify('-6 months');

$yearStart = (new DateTimeImmutable('first day of January ' . $today->format('Y')))->setTime(0, 0);
$nextYearStart = $yearStart->modify('+1 year');
$prevYearStart = $yearStart->modify('-1 year');

// Fetch range covering previous year's December through end of current year (and prior month before 6-month window)
$earliestSeriesFetch = $sixMonthsStart->modify('-1 month');
$yearSeriesFetch = $yearStart->modify('-1 month');
$mapStart = $earliestSeriesFetch < $yearSeriesFetch ? $earliestSeriesFetch : $yearSeriesFetch;
$monthlyMap = emarioh_dashboard_fetch_monthly_map($db, $mapStart, $nextYearStart);

// Period KPI totals
$monthTotals = emarioh_dashboard_fetch_period_totals($db, $monthStart, $nextMonthStart);
$prevMonthTotals = emarioh_dashboard_fetch_period_totals($db, $prevMonthStart, $monthStart);

$sixMonthTotals = emarioh_dashboard_fetch_period_totals($db, $sixMonthsStart, $nextMonthStart);
$prevSixMonthTotals = emarioh_dashboard_fetch_period_totals($db, $sixMonthsPrevStart, $sixMonthsStart);

$yearTotals = emarioh_dashboard_fetch_period_totals($db, $yearStart, $nextYearStart);
$prevYearTotals = emarioh_dashboard_fetch_period_totals($db, $prevYearStart, $yearStart);

// Latest Month MoM trend (used on status callout and as smart fallback if prior multi-month window has no history)
$latestMonthTrendDelta = emarioh_dashboard_compute_sales_delta(
    $monthTotals['booked_sales'],
    $prevMonthTotals['booked_sales'],
    'vs ' . $prevMonthStart->format('M Y')
);
$latestMonthTrend = [
    'direction' => $latestMonthTrendDelta['direction'],
    'badgeText' => $latestMonthTrendDelta['badgeText'],
    'detailText' => $latestMonthTrendDelta['detailText'],
    'currentLabel' => $monthStart->format('M Y'),
    'previousLabel' => $prevMonthStart->format('M Y'),
    'currentSales' => $monthTotals['booked_sales'],
    'previousSales' => $prevMonthTotals['booked_sales'],
];

// Weekly series for Current Month
$weeklyMap = emarioh_dashboard_fetch_weekly_map($db, $monthStart, $nextMonthStart);
$daysInMonth = (int) $monthStart->format('t');
$weekCount = $daysInMonth > 28 ? 5 : 4;
$todayWeekNo = (int) min(5, floor(((int) $today->format('j') - 1) / 7) + 1);
$monthWeeklySeries = [];
$prevWeekBooked = null;
$prevWeekShort = '';

for ($w = 1; $w <= $weekCount; $w++) {
    $dayStart = (($w - 1) * 7) + 1;
    $dayEnd = min($daysInMonth, $w * 7);
    $bucket = $weeklyMap[$w] ?? ['booked' => 0.0, 'collected' => 0.0, 'remaining' => 0.0, 'bookings' => 0];

    if ($prevWeekBooked === null) {
        $weekDelta = ['direction' => 'flat', 'badgeText' => 'Week 1', 'detailText' => 'Start of month'];
    } else {
        $weekDelta = emarioh_dashboard_compute_sales_delta(
            (float) $bucket['booked'],
            $prevWeekBooked,
            'vs ' . $prevWeekShort
        );
    }

    $shortLabel = 'Wk ' . $w;
    $monthWeeklySeries[] = [
        'key' => 'W' . $w,
        'shortLabel' => $shortLabel,
        'fullLabel' => 'Week ' . $w . ' (' . $monthStart->format('M') . ' ' . $dayStart . '–' . $dayEnd . ')',
        'booked' => (float) $bucket['booked'],
        'collected' => (float) $bucket['collected'],
        'remaining' => (float) $bucket['remaining'],
        'bookings' => (int) $bucket['bookings'],
        'deltaDirection' => $weekDelta['direction'],
        'deltaText' => $weekDelta['badgeText'],
        'deltaRefLabel' => $weekDelta['detailText'],
        'isCurrent' => $w === $todayWeekNo,
    ];

    $prevWeekBooked = (float) $bucket['booked'];
    $prevWeekShort = $shortLabel;
}

// Monthly series for Last 6 Months and This Year
$sixMonthsSeries = emarioh_dashboard_build_monthly_series($sixMonthsStart, 6, $monthlyMap, $currentMonthKey);
$yearSeries = emarioh_dashboard_build_monthly_series($yearStart, 12, $monthlyMap, $currentMonthKey);

// Period KPI trend badges
$monthSalesTrend = emarioh_dashboard_compute_sales_delta(
    $monthTotals['booked_sales'],
    $prevMonthTotals['booked_sales'],
    'vs ' . $prevMonthStart->format('M Y')
);
$monthBookingsTrend = emarioh_dashboard_compute_count_delta(
    $monthTotals['confirmed_bookings'],
    $prevMonthTotals['confirmed_bookings'],
    'vs ' . $prevMonthStart->format('M')
);

$sixMonthSalesTrend = $prevSixMonthTotals['booked_sales'] > 0.005
    ? emarioh_dashboard_compute_sales_delta($sixMonthTotals['booked_sales'], $prevSixMonthTotals['booked_sales'], 'vs prior 6 mos')
    : [
        'direction' => $latestMonthTrend['direction'],
        'badgeText' => $latestMonthTrend['badgeText'],
        'detailText' => $monthStart->format('M') . ' vs ' . $prevMonthStart->format('M'),
    ];
$sixMonthBookingsTrend = $prevSixMonthTotals['confirmed_bookings'] > 0
    ? emarioh_dashboard_compute_count_delta($sixMonthTotals['confirmed_bookings'], $prevSixMonthTotals['confirmed_bookings'], 'vs prior 6 mos')
    : emarioh_dashboard_compute_count_delta($monthTotals['confirmed_bookings'], $prevMonthTotals['confirmed_bookings'], $monthStart->format('M') . ' vs ' . $prevMonthStart->format('M'));

$yearSalesTrend = $prevYearTotals['booked_sales'] > 0.005
    ? emarioh_dashboard_compute_sales_delta($yearTotals['booked_sales'], $prevYearTotals['booked_sales'], 'vs ' . $prevYearStart->format('Y'))
    : [
        'direction' => $latestMonthTrend['direction'],
        'badgeText' => $latestMonthTrend['badgeText'],
        'detailText' => $monthStart->format('M') . ' vs ' . $prevMonthStart->format('M'),
    ];
$yearBookingsTrend = $prevYearTotals['confirmed_bookings'] > 0
    ? emarioh_dashboard_compute_count_delta($yearTotals['confirmed_bookings'], $prevYearTotals['confirmed_bookings'], 'vs ' . $prevYearStart->format('Y'))
    : emarioh_dashboard_compute_count_delta($monthTotals['confirmed_bookings'], $prevMonthTotals['confirmed_bookings'], $monthStart->format('M') . ' vs ' . $prevMonthStart->format('M'));

// Celebrations per period
$monthCelebrations = emarioh_dashboard_fetch_celebrations($db, $monthStart, $nextMonthStart);
$sixMonthsCelebrations = emarioh_dashboard_fetch_celebrations($db, $sixMonthsStart, $nextMonthStart);
$yearCelebrations = emarioh_dashboard_fetch_celebrations($db, $yearStart, $nextYearStart);

$calcCollectionRate = static fn (array $totals): float => $totals['booked_sales'] > 0
    ? min(100.0, max(0.0, ($totals['collected_sales'] / $totals['booked_sales']) * 100))
    : 0.0;

$dashboardPayload = [
    'defaultPeriod' => 'six_months',
    'ytdSummary' => [
        'yearLabel' => $yearStart->format('Y'),
        'bookedSales' => $yearTotals['booked_sales'],
        'collectedSales' => $yearTotals['collected_sales'],
        'confirmedBookings' => $yearTotals['confirmed_bookings'],
    ],
    'latestMonthTrend' => $latestMonthTrend,
    'periods' => [
        'month' => [
            'title' => $monthStart->format('F Y') . ' Performance',
            'subtitle' => 'Weekly sales, collections, and active celebrations for ' . $monthStart->format('F Y') . '.',
            'chartTitle' => 'Weekly Sales Movement',
            'chartSubtitle' => 'Booked event value vs payments collected per week in ' . $monthStart->format('F Y') . '.',
            'celebrationsSubtitle' => 'Most availed events in ' . $monthStart->format('F Y'),
            'kpis' => [
                'bookedSales' => $monthTotals['booked_sales'],
                'collectedSales' => $monthTotals['collected_sales'],
                'remainingBalance' => $monthTotals['remaining_balance'],
                'confirmedBookings' => $monthTotals['confirmed_bookings'],
                'collectionRate' => $calcCollectionRate($monthTotals),
                'bookedNote' => 'Confirmed event value in ' . $monthStart->format('F'),
                'salesTrend' => $monthSalesTrend,
                'bookingsTrend' => $monthBookingsTrend,
            ],
            'series' => $monthWeeklySeries,
            'summary' => emarioh_dashboard_summarize_series($monthWeeklySeries, 'Average per week (' . $weekCount . ' wks)'),
            'celebrations' => $monthCelebrations,
        ],
        'six_months' => [
            'title' => 'Last 6 Months Sales Overview',
            'subtitle' => $sixMonthsStart->format('M Y') . ' – ' . $monthStart->format('M Y') . ' · Monthly sales growth, collections, and balances.',
            'chartTitle' => 'Monthly Sales Trend',
            'chartSubtitle' => 'Hover or tap any month to see if sales moved up or down.',
            'celebrationsSubtitle' => $sixMonthsStart->format('M Y') . ' – ' . $monthStart->format('M Y') . ' top events',
            'kpis' => [
                'bookedSales' => $sixMonthTotals['booked_sales'],
                'collectedSales' => $sixMonthTotals['collected_sales'],
                'remainingBalance' => $sixMonthTotals['remaining_balance'],
                'confirmedBookings' => $sixMonthTotals['confirmed_bookings'],
                'collectionRate' => $calcCollectionRate($sixMonthTotals),
                'bookedNote' => 'Confirmed event value (' . $sixMonthsStart->format('M') . '–' . $monthStart->format('M Y') . ')',
                'salesTrend' => $sixMonthSalesTrend,
                'bookingsTrend' => $sixMonthBookingsTrend,
            ],
            'series' => $sixMonthsSeries,
            'summary' => emarioh_dashboard_summarize_series($sixMonthsSeries, 'Average per month (6 mos)'),
            'celebrations' => $sixMonthsCelebrations,
        ],
        'year' => [
            'title' => $yearStart->format('Y') . ' Annual Sales Overview',
            'subtitle' => 'Jan – Dec ' . $yearStart->format('Y') . ' sales performance, collections, and celebration demand.',
            'chartTitle' => $yearStart->format('Y') . ' Monthly Sales Trend',
            'chartSubtitle' => 'Compare booked sales and collected payments across all 12 months.',
            'celebrationsSubtitle' => 'Most availed celebrations in ' . $yearStart->format('Y'),
            'kpis' => [
                'bookedSales' => $yearTotals['booked_sales'],
                'collectedSales' => $yearTotals['collected_sales'],
                'remainingBalance' => $yearTotals['remaining_balance'],
                'confirmedBookings' => $yearTotals['confirmed_bookings'],
                'collectionRate' => $calcCollectionRate($yearTotals),
                'bookedNote' => 'Total confirmed event value in ' . $yearStart->format('Y'),
                'salesTrend' => $yearSalesTrend,
                'bookingsTrend' => $yearBookingsTrend,
            ],
            'series' => $yearSeries,
            'summary' => emarioh_dashboard_summarize_series($yearSeries, 'Average per month (12 mos)'),
            'celebrations' => $yearCelebrations,
        ],
    ],
];

$defaultPeriodData = $dashboardPayload['periods'][$dashboardPayload['defaultPeriod']];
$defaultKpis = $defaultPeriodData['kpis'];
$defaultSummary = $defaultPeriodData['summary'];
$defaultCelebrations = $defaultPeriodData['celebrations'];
$maxDefaultCelebration = 1;
foreach ($defaultCelebrations as $cItem) {
    $maxDefaultCelebration = max($maxDefaultCelebration, (int) ($cItem['bookingCount'] ?? 1));
}

$renderTrendBadgeHtml = static function (array $trend, bool $includeDetail = true, string $id = '') use ($escape): string {
    $dir = in_array($trend['direction'] ?? 'flat', ['up', 'down'], true) ? $trend['direction'] : 'flat';
    $icon = $dir === 'up' ? 'bi-arrow-up-right' : ($dir === 'down' ? 'bi-arrow-down-right' : 'bi-dash');
    $text = (string) ($trend['badgeText'] ?? '0%');
    if ($includeDetail && trim((string) ($trend['detailText'] ?? '')) !== '') {
        $text .= ' ' . $trend['detailText'];
    }
    $idAttr = $id !== '' ? ' id="' . $escape($id) . '"' : '';

    return '<span' . $idAttr . ' class="trend-badge trend-badge--' . $escape($dir) . '"><i class="bi ' . $icon . '" aria-hidden="true"></i><span>' . $escape($text) . '</span></span>';
};

$payloadJson = json_encode(
    $dashboardPayload,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
) ?: '{}';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emarioh Catering Services Admin Dashboard</title>
    <?= emarioh_render_vendor_head_assets(); ?>
    <link rel="stylesheet" href="assets/css/index.css?v=20260903c">
    <link rel="stylesheet" href="assets/css/pages/admin-dashboard.css?v=20261119b">
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
                            <?= emarioh_render_admin_booking_nav_link($db, false) ?>
                            <a class="nav-link" href="admin-clients.php"><span class="nav-link__icon"><i class="bi bi-people"></i></span><span>Clients</span></a>
                            <a class="nav-link" href="admin-payments.php"><span class="nav-link__icon"><i class="bi bi-wallet2"></i></span><span>Payment</span></a>
                            <?= emarioh_render_admin_inquiry_nav_link($db, false) ?>
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
                    <section class="sales-dashboard" aria-label="Sales analytics overview">
                        <div class="sales-dashboard__header">
                            <div class="sales-dashboard__title-group">
                                <span class="sales-dashboard__eyebrow"><i class="bi bi-bar-chart-line"></i> Sales Analytics</span>
                                <h2 class="sales-dashboard__title" id="salesDashboardTitle"><?= $escape((string) $defaultPeriodData['title']) ?></h2>
                                <p class="sales-dashboard__subtitle" id="salesDashboardSubtitle"><?= $escape((string) $defaultPeriodData['subtitle']) ?></p>
                            </div>

                            <div class="sales-dashboard__controls">
                                <div class="sales-period-tabs" role="group" aria-label="Select dashboard timeframe">
                                    <button class="sales-period-tab" type="button" data-sales-period="month" aria-pressed="false">This Month</button>
                                    <button class="sales-period-tab is-active" type="button" data-sales-period="six_months" aria-pressed="true">Last 6 Months</button>
                                    <button class="sales-period-tab" type="button" data-sales-period="year" aria-pressed="false">This Year (<?= $escape($yearStart->format('Y')) ?>)</button>
                                </div>
                            </div>
                        </div>

                        <div class="sales-kpi-grid">
                            <article class="sales-kpi-card sales-kpi-card--featured">
                                <div class="sales-kpi-card__top">
                                    <div class="sales-kpi-card__label-wrap">
                                        <span class="sales-kpi-card__icon" aria-hidden="true"><i class="bi bi-graph-up-arrow"></i></span>
                                        <span class="sales-kpi-card__label">Total Sales</span>
                                    </div>
                                </div>
                                <div class="sales-kpi-card__body">
                                    <strong class="sales-kpi-card__value" id="kpiBookedSalesValue"><?= $escape(emarioh_format_money_amount((float) $defaultKpis['bookedSales'])) ?></strong>
                                    <div class="sales-kpi-card__meta">
                                        <?= $renderTrendBadgeHtml($defaultKpis['salesTrend'], true, 'kpiBookedSalesTrend') ?>
                                    </div>
                                </div>
                                <p class="sales-kpi-card__note" id="kpiBookedSalesNote"><?= $escape((string) $defaultKpis['bookedNote']) ?></p>
                            </article>

                            <article class="sales-kpi-card">
                                <div class="sales-kpi-card__top">
                                    <div class="sales-kpi-card__label-wrap">
                                        <span class="sales-kpi-card__icon" aria-hidden="true"><i class="bi bi-cash-coin"></i></span>
                                        <span class="sales-kpi-card__label">Collected Payments</span>
                                    </div>
                                </div>
                                <div class="sales-kpi-card__body">
                                    <strong class="sales-kpi-card__value" id="kpiCollectedSalesValue"><?= $escape(emarioh_format_money_amount((float) $defaultKpis['collectedSales'])) ?></strong>
                                </div>
                                <div class="kpi-progress">
                                    <div class="kpi-progress__row">
                                        <span>Collection Rate</span>
                                        <strong id="kpiCollectionRateLabel"><?= $escape((string) round((float) $defaultKpis['collectionRate'])) ?>% collected</strong>
                                    </div>
                                    <div class="kpi-progress__track" aria-hidden="true">
                                        <span id="kpiCollectionRateBar" style="width: <?= $escape(number_format((float) $defaultKpis['collectionRate'], 1, '.', '')) ?>%;"></span>
                                    </div>
                                </div>
                            </article>

                            <article class="sales-kpi-card">
                                <div class="sales-kpi-card__top">
                                    <div class="sales-kpi-card__label-wrap">
                                        <span class="sales-kpi-card__icon" aria-hidden="true"><i class="bi bi-hourglass-split"></i></span>
                                        <span class="sales-kpi-card__label">Remaining Balance</span>
                                    </div>
                                </div>
                                <div class="sales-kpi-card__body">
                                    <strong class="sales-kpi-card__value" id="kpiRemainingBalanceValue"><?= $escape(emarioh_format_money_amount((float) $defaultKpis['remainingBalance'])) ?></strong>
                                </div>
                                <p class="sales-kpi-card__note" id="kpiRemainingBalanceNote"><?= $defaultKpis['remainingBalance'] > 0 ? 'Unpaid balance from confirmed events' : 'All confirmed events are fully settled' ?></p>
                            </article>

                            <article class="sales-kpi-card">
                                <div class="sales-kpi-card__top">
                                    <div class="sales-kpi-card__label-wrap">
                                        <span class="sales-kpi-card__icon" aria-hidden="true"><i class="bi bi-clipboard2-check"></i></span>
                                        <span class="sales-kpi-card__label">Confirmed Bookings</span>
                                    </div>
                                </div>
                                <div class="sales-kpi-card__body">
                                    <strong class="sales-kpi-card__value" id="kpiConfirmedBookingsValue"><?= $escape((string) $defaultKpis['confirmedBookings']) ?></strong>
                                    <div class="sales-kpi-card__meta">
                                        <?= $renderTrendBadgeHtml($defaultKpis['bookingsTrend'], true, 'kpiConfirmedBookingsTrend') ?>
                                    </div>
                                </div>
                                <p class="sales-kpi-card__note" id="kpiConfirmedBookingsNote"><?= $defaultKpis['confirmedBookings'] === 1 ? 'Approved or completed event' : 'Approved or completed events' ?></p>
                            </article>
                        </div>

                        <div class="sales-workspace">
                            <article class="sales-panel">
                                <div class="sales-panel__head">
                                    <div>
                                        <span class="sales-panel__eyebrow">Sales Performance</span>
                                        <h3 class="sales-panel__title" id="salesChartTitle"><?= $escape((string) $defaultPeriodData['chartTitle']) ?></h3>
                                        <p class="sales-panel__subtitle" id="salesChartSubtitle"><?= $escape((string) $defaultPeriodData['chartSubtitle']) ?></p>
                                    </div>

                                    <div class="sales-chart-toolbar">
                                        <div class="chart-series-pills" role="group" aria-label="Filter chart series">
                                            <button class="chart-series-pill is-active" type="button" data-chart-series="both" aria-pressed="true">All</button>
                                            <button class="chart-series-pill" type="button" data-chart-series="booked" aria-pressed="false"><i class="chart-dot chart-dot--booked" aria-hidden="true"></i> Booked</button>
                                            <button class="chart-series-pill" type="button" data-chart-series="collected" aria-pressed="false"><i class="chart-dot chart-dot--collected" aria-hidden="true"></i> Collected</button>
                                        </div>

                                        <div class="chart-mode-toggle" role="group" aria-label="Chart visualization style">
                                            <button class="chart-mode-btn is-active" type="button" data-chart-mode="line" aria-pressed="true" title="Line graph">
                                                <i class="bi bi-graph-up" aria-hidden="true"></i><span>Line</span>
                                            </button>
                                            <button class="chart-mode-btn" type="button" data-chart-mode="bar" aria-pressed="false" title="Bar graph">
                                                <i class="bi bi-bar-chart" aria-hidden="true"></i><span>Bar</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="sales-chart-status">
                                    <div class="sales-chart-status__lead">
                                        <?= $renderTrendBadgeHtml($latestMonthTrend, false, 'salesChartLatestTrendBadge') ?>
                                        <span id="salesChartLatestTrendText">Latest month: <strong><?= $escape($latestMonthTrend['currentLabel']) ?></strong> (<?= $escape(emarioh_format_money_amount((float) $latestMonthTrend['currentSales'])) ?>) vs <?= $escape($latestMonthTrend['previousLabel']) ?></span>
                                    </div>
                                    <div class="sales-chart-status__legend" aria-hidden="true">
                                        <span><i class="chart-dot chart-dot--booked"></i> Booked Sales</span>
                                        <span><i class="chart-dot chart-dot--collected"></i> Collected</span>
                                    </div>
                                </div>

                                <div class="sales-chart-stage" id="salesChartStage">
                                    <svg class="sales-chart-svg" id="salesChartSvg" role="img" aria-label="Sales trend chart"></svg>
                                    <div class="sales-chart-empty" id="salesChartEmpty" hidden>
                                        <span class="sales-chart-empty__badge"><i class="bi bi-info-circle" aria-hidden="true"></i> No confirmed sales recorded for this period yet.</span>
                                    </div>
                                    <div class="sales-chart-tooltip" id="salesChartTooltip" aria-hidden="true"></div>
                                </div>

                                <div class="sales-chart-summary">
                                    <div class="sales-chart-stat">
                                        <span>Highest Period</span>
                                        <strong id="salesStatPeakValue"><?= $defaultSummary['peakValue'] > 0 ? $escape(emarioh_format_money_amount((float) $defaultSummary['peakValue'])) : 'No sales yet' ?></strong>
                                        <small id="salesStatPeakSub"><?= $escape((string) ($defaultSummary['peakValue'] > 0 ? $defaultSummary['peakLabel'] : 'Waiting for bookings')) ?></small>
                                    </div>
                                    <div class="sales-chart-stat">
                                        <span>Period Average</span>
                                        <strong id="salesStatAvgValue"><?= $escape(emarioh_format_money_amount((float) $defaultSummary['avgValue'])) ?></strong>
                                        <small id="salesStatAvgSub"><?= $escape((string) $defaultSummary['avgLabel']) ?></small>
                                    </div>
                                    <div class="sales-chart-stat">
                                        <span><?= $escape($yearStart->format('Y')) ?> Total Sales</span>
                                        <strong id="salesStatYtdValue"><?= $escape(emarioh_format_money_amount((float) $yearTotals['booked_sales'])) ?></strong>
                                        <small id="salesStatYtdSub"><?= $escape($yearStart->format('Y') . ' · ' . $yearTotals['confirmed_bookings'] . ' confirmed booking' . ($yearTotals['confirmed_bookings'] === 1 ? '' : 's')) ?></small>
                                    </div>
                                </div>
                            </article>

                            <aside class="sales-panel celebrations-panel" aria-label="Top celebrations">
                                <div class="sales-panel__head">
                                    <div>
                                        <span class="sales-panel__eyebrow">Celebration Demand</span>
                                        <h3 class="sales-panel__title">Top Celebrations</h3>
                                        <p class="sales-panel__subtitle" id="salesCelebrationsSubtitle"><?= $escape((string) $defaultPeriodData['celebrationsSubtitle']) ?></p>
                                    </div>
                                </div>

                                <div class="celebrations-list" id="salesCelebrationsList">
                                    <?php if ($defaultCelebrations === []): ?>
                                        <div class="celebrations-empty">
                                            <i class="bi bi-stars" aria-hidden="true"></i>
                                            <p>No confirmed celebration bookings recorded for this period yet.</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($defaultCelebrations as $idx => $celebration): ?>
                                            <?php
                                            $cCount = (int) ($celebration['bookingCount'] ?? 0);
                                            $cSales = (float) ($celebration['bookedSales'] ?? 0);
                                            $cWidth = min(100, max(10, ($cCount / $maxDefaultCelebration) * 100));
                                            ?>
                                            <div class="celebration-row<?= $idx === 0 ? ' celebration-row--top' : '' ?>">
                                                <span class="celebration-row__rank"><?= $escape((string) ($idx + 1)) ?></span>
                                                <div class="celebration-row__body">
                                                    <div class="celebration-row__head">
                                                        <span class="celebration-row__name" title="<?= $escape((string) $celebration['eventType']) ?>"><?= $escape((string) $celebration['eventType']) ?></span>
                                                        <span class="celebration-row__count"><?= $escape((string) $cCount) ?> booking<?= $cCount === 1 ? '' : 's' ?></span>
                                                    </div>
                                                    <div class="celebration-row__bar" aria-hidden="true">
                                                        <span style="width: <?= $escape(number_format($cWidth, 1, '.', '')) ?>%;"></span>
                                                    </div>
                                                    <span class="celebration-row__sales"><?= $escape(emarioh_format_money_amount($cSales)) ?> total sales</span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="sales-quick-links">
                                    <a class="sales-quick-link" href="admin-bookings.php">
                                        <span>Bookings</span>
                                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                    </a>
                                    <a class="sales-quick-link" href="admin-payments.php">
                                        <span>Payments</span>
                                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </aside>
                        </div>
                    </section>
                </main>

                <?= emarioh_render_admin_mobile_nav(basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'))) ?>
            </div>
        </div>
    </div>

    <script type="application/json" id="adminDashboardAnalyticsData"><?= $payloadJson ?></script>
    <?= emarioh_render_vendor_runtime_assets(true); ?>
    <script src="assets/js/auth-api.js"></script>
    <script src="assets/js/logout-confirmation.js"></script>
    <script src="assets/js/pages/index.js?v=20260417c"></script>
    <script src="assets/js/pages/admin-dashboard.js?v=20261119b"></script>
</body>
</html>
