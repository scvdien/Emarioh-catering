<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$db = emarioh_db();
$currentUser = emarioh_current_user();

if ($currentUser === null) {
    emarioh_redirect('login.php?reason=session');
}

$currentUserRole = (string) ($currentUser['role'] ?? 'client');

if (!in_array($currentUserRole, ['admin', 'client'], true)) {
    emarioh_redirect(emarioh_role_landing_url($currentUserRole));
}

$invoiceNumber = trim((string) ($_GET['invoice'] ?? ''));
$autoPrint = isset($_GET['print']) && (string) $_GET['print'] === '1';
$downloadPdf = isset($_GET['download']) && (string) $_GET['download'] === '1';

if ($invoiceNumber === '') {
    http_response_code(404);
    exit('Receipt not found.');
}

$invoice = emarioh_find_payment_invoice_by_number($db, $invoiceNumber);

if ($invoice === null) {
    http_response_code(404);
    exit('Receipt not found.');
}

$booking = emarioh_find_booking_by_id($db, (int) ($invoice['booking_id'] ?? 0));

if ($booking === null) {
    http_response_code(404);
    exit('Receipt not found.');
}

$invoiceUserId = (int) ($invoice['user_id'] ?? 0);
$bookingUserId = (int) ($booking['user_id'] ?? 0);
$currentUserId = (int) ($currentUser['id'] ?? 0);

if (
    $currentUserRole === 'client'
    && (
        ($invoiceUserId > 0 && $invoiceUserId !== $currentUserId)
        || ($bookingUserId > 0 && $bookingUserId !== $currentUserId)
    )
) {
    http_response_code(403);
    exit('You do not have access to this receipt.');
}

$amountPaidValue = max(0, (float) ($invoice['amount_paid'] ?? 0));

if ($amountPaidValue <= 0) {
    http_response_code(404);
    exit('Receipt is not available yet.');
}

$receipt = emarioh_upsert_system_payment_receipt($db, $invoice, $booking);

if ($receipt === null) {
    http_response_code(500);
    exit('Receipt could not be prepared right now.');
}

$escape = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$formatMoney = static fn (float $amount): string => 'PHP ' . number_format(max(0, $amount), 2);
$formatDateTime = static function (?string $value): string {
    $normalizedValue = trim((string) $value);

    if ($normalizedValue === '') {
        return 'Not available';
    }

    try {
        return (new DateTimeImmutable($normalizedValue))->format('F j, Y \\a\\t g:i A');
    } catch (Throwable $exception) {
        return 'Not available';
    }
};
$formatDate = static function (?string $value): string {
    $normalizedValue = trim((string) $value);

    if ($normalizedValue === '') {
        return 'TBA';
    }

    try {
        return (new DateTimeImmutable($normalizedValue))->format('F j, Y');
    } catch (Throwable $exception) {
        return 'TBA';
    }
};
$formatTime = static function (?string $value): string {
    $normalizedValue = trim((string) $value);

    if ($normalizedValue === '') {
        return 'TBA';
    }

    try {
        return (new DateTimeImmutable('1970-01-01 ' . $normalizedValue))->format('g:i A');
    } catch (Throwable $exception) {
        return 'TBA';
    }
};

$receiptReference = (string) ($receipt['receipt_reference'] ?? emarioh_payment_receipt_reference((string) ($invoice['invoice_number'] ?? '')));
$invoiceStatus = strtolower(trim((string) ($invoice['status'] ?? 'pending')));
$statusLabel = $invoiceStatus === 'approved' ? 'Paid Successful' : 'Payment Posted';
$balanceDueValue = max(0, (float) ($invoice['balance_due'] ?? 0));
$paymentReference = trim((string) ($invoice['gateway_payment_id'] ?? '')) ?: trim((string) ($invoice['gateway_checkout_reference'] ?? ''));
$paymentDate = (string) ($invoice['gateway_paid_at'] ?? $invoice['last_payment_at'] ?? '');
$clientName = trim((string) ($booking['primary_contact'] ?? $currentUser['full_name'] ?? 'Client'));
$clientMobile = trim((string) ($booking['primary_mobile'] ?? ''));
$description = trim((string) ($invoice['title'] ?? $booking['package_label'] ?? 'Booking Payment'));

function emarioh_pdf_sanitize_text(string $value): string
{
    $normalized = trim(preg_replace('/\s+/', ' ', $value) ?? '');

    if ($normalized === '') {
        return '';
    }

    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $normalized);

        if ($converted !== false) {
            return $converted;
        }
    }

    return preg_replace('/[^\x20-\x7E]/', '?', $normalized) ?? '';
}

function emarioh_pdf_escape_text(string $value): string
{
    return str_replace(
        ['\\', '(', ')', "\r", "\n"],
        ['\\\\', '\\(', '\\)', '', ''],
        $value
    );
}

function emarioh_pdf_wrap_text(string $text, int $maxChars = 90): array
{
    $sanitized = emarioh_pdf_sanitize_text($text);

    if ($sanitized === '') {
        return [''];
    }

    $words = preg_split('/\s+/', $sanitized) ?: [];
    $lines = [];
    $currentLine = '';

    foreach ($words as $word) {
        $word = trim($word);

        if ($word === '') {
            continue;
        }

        $candidate = $currentLine === '' ? $word : $currentLine . ' ' . $word;

        if (strlen($candidate) <= $maxChars) {
            $currentLine = $candidate;
            continue;
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        if (strlen($word) <= $maxChars) {
            $currentLine = $word;
            continue;
        }

        $chunks = str_split($word, $maxChars);
        $lastChunkIndex = array_key_last($chunks);

        foreach ($chunks as $index => $chunk) {
            if ($index === $lastChunkIndex) {
                $currentLine = $chunk;
                continue;
            }

            $lines[] = $chunk;
        }
    }

    if ($currentLine !== '') {
        $lines[] = $currentLine;
    }

    return $lines === [] ? [''] : $lines;
}

function emarioh_pdf_limit_wrapped_text(string $text, int $maxChars = 90, int $maxLines = 2): array
{
    $lines = emarioh_pdf_wrap_text($text, $maxChars);

    if ($maxLines < 1 || count($lines) <= $maxLines) {
        return $lines;
    }

    $limitedLines = array_slice($lines, 0, $maxLines);
    $lastIndex = $maxLines - 1;
    $lastLine = rtrim($limitedLines[$lastIndex], " .,-");

    if (strlen($lastLine) > max(0, $maxChars - 3)) {
        $lastLine = substr($lastLine, 0, max(0, $maxChars - 3));
        $lastLine = rtrim($lastLine, " .,-");
    }

    $limitedLines[$lastIndex] = $lastLine . '...';

    return $limitedLines;
}

function emarioh_pdf_y_from_top(float $top): float
{
    return 842 - $top;
}

function emarioh_pdf_text(float $x, float $top, string $text, string $font = 'F1', float $size = 12): string
{
    $sanitized = emarioh_pdf_sanitize_text($text);

    if ($sanitized === '') {
        return '';
    }

    return sprintf(
        "BT /%s %.2F Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET\n",
        $font,
        $size,
        $x,
        emarioh_pdf_y_from_top($top),
        emarioh_pdf_escape_text($sanitized)
    );
}

function emarioh_pdf_line(float $x1, float $top1, float $x2, float $top2): string
{
    return sprintf(
        "%.2F %.2F m %.2F %.2F l S\n",
        $x1,
        emarioh_pdf_y_from_top($top1),
        $x2,
        emarioh_pdf_y_from_top($top2)
    );
}

function emarioh_pdf_page_preamble(): string
{
    return "0.18 0.13 0.10 rg\n0.75 w\n";
}

function emarioh_pdf_render_section(float $x, float $top, string $title, array $entries, int $maxChars = 34, int $maxLines = 2): array
{
    $content = '';
    $cursorTop = $top;

    $content .= emarioh_pdf_text($x, $cursorTop, $title, 'F2', 13);
    $cursorTop += 18.0;

    foreach ($entries as $entry) {
        $label = trim((string) ($entry['label'] ?? ''));
        $value = trim((string) ($entry['value'] ?? ''));

        if ($label !== '') {
            $content .= emarioh_pdf_text($x, $cursorTop, $label, 'F2', 8.5);
            $cursorTop += 12.0;
        }

        $valueLines = emarioh_pdf_limit_wrapped_text($value !== '' ? $value : 'Not provided', $maxChars, $maxLines);

        foreach ($valueLines as $line) {
            $content .= emarioh_pdf_text($x, $cursorTop, $line, 'F1', 11);
            $cursorTop += 12.0;
        }

        $cursorTop += 7.0;
    }

    return [
        'content' => $content,
        'height' => $cursorTop - $top,
    ];
}

function emarioh_build_pdf_document(array $pages): string
{
    $normalizedPages = array_values(array_filter(
        array_map(
            static fn ($page): string => is_string($page) ? $page : '',
            $pages
        ),
        static fn (string $page): bool => $page !== ''
    ));

    if ($normalizedPages === []) {
        $normalizedPages = [emarioh_pdf_page_preamble()];
    }

    $pageCount = count($normalizedPages);
    $pageObjectStart = 6;
    $contentObjectStart = $pageObjectStart + $pageCount;
    $objects = [
        1 => '<< /Type /Catalog /Pages 2 0 R >>',
        2 => '<< /Type /Pages /Kids [' . implode(' ', array_map(
            static fn (int $index): string => ($pageObjectStart + $index) . ' 0 R',
            array_keys($normalizedPages)
        )) . '] /Count ' . $pageCount . ' >>',
        3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
        5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-BoldOblique >>',
    ];

    foreach ($normalizedPages as $index => $pageContent) {
        $pageObjectNumber = $pageObjectStart + $index;
        $contentObjectNumber = $contentObjectStart + $index;

        $objects[$pageObjectNumber] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R /F3 5 0 R >> >> /Contents ' . $contentObjectNumber . ' 0 R >>';
        $objects[$contentObjectNumber] = "<< /Length " . strlen($pageContent) . " >>\nstream\n" . $pageContent . "\nendstream";
    }

    ksort($objects);

    $pdf = "%PDF-1.4\n";
    $offsets = [0];

    foreach ($objects as $index => $objectBody) {
        $offsets[$index] = strlen($pdf);
        $pdf .= $index . " 0 obj\n" . $objectBody . "\nendobj\n";
    }

    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";

    foreach (array_keys($objects) as $index) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$index]);
    }

    $pdf .= "trailer\n";
    $pdf .= "<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

    return $pdf;
}

function emarioh_payment_receipt_pdf_filename(string $receiptReference): string
{
    $safeName = strtolower(trim(preg_replace('/[^A-Za-z0-9_-]+/', '-', $receiptReference) ?? 'payment-receipt'));
    $safeName = trim($safeName, '-');

    return ($safeName !== '' ? $safeName : 'payment-receipt') . '.pdf';
}

function emarioh_stream_payment_receipt_pdf(array $payload): never
{
    $left = 40.0;
    $right = 555.0;
    $page = emarioh_pdf_page_preamble();
    $cursorTop = 44.0;

    $page .= emarioh_pdf_text($left, $cursorTop, 'EMARIOH CATERING SERVICES', 'F2', 10.5);
    $page .= emarioh_pdf_text($left, $cursorTop + 20, 'Payment Receipt', 'F3', 25);
    $page .= emarioh_pdf_text(405.0, $cursorTop + 6, (string) ($payload['status_label'] ?? 'Payment Posted'), 'F2', 12);

    foreach (emarioh_pdf_limit_wrapped_text((string) ($payload['subtext'] ?? ''), 84, 2) as $index => $line) {
        $page .= emarioh_pdf_text($left, $cursorTop + 54 + ($index * 14), $line, 'F1', 11);
    }

    $cursorTop = 112.0;
    $page .= emarioh_pdf_line($left, $cursorTop, $right, $cursorTop);
    $cursorTop += 18.0;

    $summaryEntries = [
        ['Receipt Number', (string) ($payload['receipt_reference'] ?? '')],
        ['Invoice Number', (string) ($payload['invoice_number'] ?? '')],
        ['Amount Paid', (string) ($payload['amount_paid'] ?? '')],
        ['Payment Date', (string) ($payload['payment_date'] ?? '')],
    ];
    $summaryPositions = [
        [40.0, $cursorTop],
        [300.0, $cursorTop],
        [40.0, $cursorTop + 44.0],
        [300.0, $cursorTop + 44.0],
    ];

    foreach ($summaryEntries as $index => $entry) {
        [$x, $top] = $summaryPositions[$index];
        $page .= emarioh_pdf_text($x, $top, (string) $entry[0], 'F2', 8.5);

        $valueLines = emarioh_pdf_limit_wrapped_text((string) $entry[1], 28, 2);

        foreach ($valueLines as $lineIndex => $line) {
            $page .= emarioh_pdf_text($x, $top + 16 + ($lineIndex * 12), $line, 'F2', 11.5);
        }
    }

    $cursorTop += 92.0;

    $clientSection = emarioh_pdf_render_section(
        40.0,
        $cursorTop,
        'Client Details',
        is_array($payload['client_entries'] ?? null) ? $payload['client_entries'] : [],
        32,
        2
    );
    $paymentSection = emarioh_pdf_render_section(
        300.0,
        $cursorTop,
        'Payment Details',
        is_array($payload['payment_entries'] ?? null) ? $payload['payment_entries'] : [],
        32,
        2
    );

    $page .= $clientSection['content'];
    $page .= $paymentSection['content'];

    $cursorTop += max((float) $clientSection['height'], (float) $paymentSection['height']) + 10.0;
    $page .= emarioh_pdf_line($left, $cursorTop, $right, $cursorTop);
    $cursorTop += 18.0;

    $eventSection = emarioh_pdf_render_section(
        40.0,
        $cursorTop,
        'Event Details',
        is_array($payload['event_entries'] ?? null) ? $payload['event_entries'] : [],
        78,
        2
    );
    $page .= $eventSection['content'];

    $footerTop = 776.0;
    $page .= emarioh_pdf_line($left, $footerTop - 14.0, $right, $footerTop - 14.0);
    $page .= emarioh_pdf_text($left, $footerTop, (string) ($payload['generated_label'] ?? 'Generated On'), 'F2', 8.5);
    $page .= emarioh_pdf_text($left, $footerTop + 12.0, (string) ($payload['generated_on'] ?? ''), 'F1', 10.5);
    $page .= emarioh_pdf_text(300.0, $footerTop, 'Receipt Status', 'F2', 8.5);
    $page .= emarioh_pdf_text(300.0, $footerTop + 12.0, (string) ($payload['receipt_status'] ?? ''), 'F1', 10.5);

    $pdf = emarioh_build_pdf_document([$page]);
    $fileName = emarioh_payment_receipt_pdf_filename((string) ($payload['receipt_reference'] ?? 'payment-receipt'));

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . strlen($pdf));
    header('Cache-Control: private, max-age=0, must-revalidate');

    echo $pdf;
    exit;
}

if ($downloadPdf) {
    emarioh_stream_payment_receipt_pdf([
        'status_label' => $statusLabel,
        'subtext' => 'This receipt was generated automatically after PayMongo confirmed your payment.',
        'receipt_reference' => $receiptReference,
        'invoice_number' => (string) ($invoice['invoice_number'] ?? ''),
        'amount_paid' => $formatMoney($amountPaidValue),
        'payment_date' => $formatDateTime($paymentDate),
        'client_entries' => [
            ['label' => 'Client Name', 'value' => $clientName],
            ['label' => 'Mobile Number', 'value' => $clientMobile !== '' ? emarioh_format_mobile($clientMobile) : 'Not provided'],
            ['label' => 'Booking Reference', 'value' => (string) ($booking['reference'] ?? '')],
        ],
        'payment_entries' => [
            ['label' => 'Description', 'value' => $description],
            ['label' => 'Payment Method', 'value' => (string) ($invoice['payment_method'] ?? 'PayMongo QRPh')],
            ['label' => 'PayMongo Reference', 'value' => $paymentReference !== '' ? $paymentReference : 'Will appear after settlement sync'],
            ['label' => 'Remaining Balance', 'value' => $formatMoney($balanceDueValue)],
        ],
        'event_entries' => [
            ['label' => 'Event Type', 'value' => (string) ($booking['event_type'] ?? 'Booking')],
            ['label' => 'Event Schedule', 'value' => $formatDate((string) ($booking['event_date'] ?? '')) . ' at ' . $formatTime((string) ($booking['event_time'] ?? ''))],
            ['label' => 'Venue', 'value' => (string) ($booking['venue_name'] ?? 'Venue to follow')],
            ['label' => 'Guest Count', 'value' => (string) ((int) ($booking['guest_count'] ?? 0)) . ' guests'],
        ],
        'generated_label' => 'Generated On',
        'generated_on' => $formatDateTime((string) ($receipt['updated_at'] ?? $receipt['created_at'] ?? '')),
        'receipt_status' => ucfirst((string) ($receipt['status'] ?? 'verified')),
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt <?= $escape($receiptReference) ?></title>
    <?= emarioh_render_vendor_head_assets(false, false); ?>
    <link rel="stylesheet" href="assets/css/client-billing.css?v=20260413a">
    <style>
        body {
            margin: 0;
            background: #f3ede3;
            color: #2d211b;
            font-family: "Manrope", sans-serif;
        }

        .receipt-shell {
            max-width: 980px;
            margin: 0 auto;
            padding: 2rem 1rem 3rem;
        }

        .receipt-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .receipt-card {
            display: grid;
            gap: 1.2rem;
            padding: 1.4rem;
            border: 1px solid rgba(95, 71, 53, 0.08);
            border-radius: 1.4rem;
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 18px 40px rgba(58, 41, 35, 0.1);
        }

        .receipt-card__head,
        .receipt-card__summary,
        .receipt-card__details {
            display: grid;
            gap: 1rem;
        }

        .receipt-card__head {
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: start;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(95, 71, 53, 0.08);
        }

        .receipt-card__eyebrow,
        .receipt-grid__label {
            margin: 0;
            color: #8b7866;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .receipt-card__title {
            margin: 0.2rem 0 0;
            font-family: "Cormorant Garamond", serif;
            font-size: clamp(2rem, 4vw, 3rem);
            line-height: 1;
        }

        .receipt-card__subtext {
            margin: 0.55rem 0 0;
            color: #67584c;
            font-size: 0.92rem;
            line-height: 1.7;
        }

        .receipt-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.45rem 0.85rem;
            border-radius: 999px;
            background: rgba(227, 234, 223, 0.9);
            color: #31553f;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .receipt-card__summary {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .receipt-grid__item {
            display: grid;
            gap: 0.28rem;
            padding: 0.95rem 1rem;
            border: 1px solid rgba(95, 71, 53, 0.08);
            border-radius: 1rem;
            background: rgba(249, 246, 240, 0.8);
        }

        .receipt-grid__value {
            color: #2d211b;
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .receipt-card__details {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .receipt-detail-card--wide {
            grid-column: 1 / -1;
        }

        .receipt-detail-card {
            display: grid;
            gap: 0.8rem;
            padding: 1rem;
            border: 1px solid rgba(95, 71, 53, 0.08);
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.88);
        }

        .receipt-detail-card h2 {
            margin: 0;
            font-size: 1rem;
            font-weight: 800;
        }

        .receipt-detail-list {
            display: grid;
            gap: 0.7rem;
            margin: 0;
        }

        .receipt-detail-list div {
            display: grid;
            gap: 0.2rem;
        }

        .receipt-detail-list dt {
            color: #8b7866;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .receipt-detail-list dd {
            margin: 0;
            color: #2d211b;
            font-size: 0.94rem;
            line-height: 1.65;
        }

        .receipt-card__footnote {
            margin: 0;
            padding-top: 0.2rem;
            color: #67584c;
            font-size: 0.86rem;
            line-height: 1.7;
        }

        .receipt-card__meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.9rem;
        }

        @media (max-width: 767.98px) {
            .receipt-card__head,
            .receipt-card__summary,
            .receipt-card__details,
            .receipt-card__meta {
                grid-template-columns: 1fr;
            }

            .receipt-shell {
                padding: 1rem 0.8rem 2rem;
            }
        }

        @media print {
            body {
                background: #fff;
            }

            .receipt-shell {
                max-width: none;
                padding: 0;
            }

            .receipt-actions {
                display: none;
            }

            .receipt-card {
                box-shadow: none;
                border-color: rgba(95, 71, 53, 0.12);
            }
        }
    </style>
</head>
<body>
    <main class="receipt-shell">
        <div class="receipt-actions">
            <a class="client-action-button client-action-button--secondary" href="client-billing.php">Back To Billing</a>
            <a class="client-action-button client-action-button--primary" href="client-payment-receipt.php?invoice=<?= rawurlencode($invoiceNumber) ?>&amp;download=1">Download PDF</a>
        </div>

        <section class="receipt-card">
            <header class="receipt-card__head">
                <div>
                    <p class="receipt-card__eyebrow">Emarioh Catering Services</p>
                    <h1 class="receipt-card__title">Payment Receipt</h1>
                    <p class="receipt-card__subtext">This receipt was generated automatically after PayMongo confirmed your payment.</p>
                </div>
                <span class="receipt-badge"><?= $escape($statusLabel) ?></span>
            </header>

            <section class="receipt-card__summary">
                <article class="receipt-grid__item">
                    <p class="receipt-grid__label">Receipt Number</p>
                    <strong class="receipt-grid__value"><?= $escape($receiptReference) ?></strong>
                </article>
                <article class="receipt-grid__item">
                    <p class="receipt-grid__label">Invoice Number</p>
                    <strong class="receipt-grid__value"><?= $escape((string) ($invoice['invoice_number'] ?? '')) ?></strong>
                </article>
                <article class="receipt-grid__item">
                    <p class="receipt-grid__label">Amount Paid</p>
                    <strong class="receipt-grid__value"><?= $escape($formatMoney($amountPaidValue)) ?></strong>
                </article>
                <article class="receipt-grid__item">
                    <p class="receipt-grid__label">Payment Date</p>
                    <strong class="receipt-grid__value"><?= $escape($formatDateTime($paymentDate)) ?></strong>
                </article>
            </section>

            <section class="receipt-card__details">
                <article class="receipt-detail-card">
                    <h2>Client Details</h2>
                    <dl class="receipt-detail-list">
                        <div>
                            <dt>Client Name</dt>
                            <dd><?= $escape($clientName) ?></dd>
                        </div>
                        <div>
                            <dt>Mobile Number</dt>
                            <dd><?= $escape($clientMobile !== '' ? emarioh_format_mobile($clientMobile) : 'Not provided') ?></dd>
                        </div>
                        <div>
                            <dt>Booking Reference</dt>
                            <dd><?= $escape((string) ($booking['reference'] ?? '')) ?></dd>
                        </div>
                    </dl>
                </article>

                <article class="receipt-detail-card">
                    <h2>Payment Details</h2>
                    <dl class="receipt-detail-list">
                        <div>
                            <dt>Description</dt>
                            <dd><?= $escape($description) ?></dd>
                        </div>
                        <div>
                            <dt>Payment Method</dt>
                            <dd><?= $escape((string) ($invoice['payment_method'] ?? 'PayMongo QRPh')) ?></dd>
                        </div>
                        <div>
                            <dt>PayMongo Reference</dt>
                            <dd><?= $escape($paymentReference !== '' ? $paymentReference : 'Will appear after settlement sync') ?></dd>
                        </div>
                        <div>
                            <dt>Remaining Balance</dt>
                            <dd><?= $escape($formatMoney($balanceDueValue)) ?></dd>
                        </div>
                    </dl>
                </article>

                <article class="receipt-detail-card receipt-detail-card--wide">
                    <h2>Event Details</h2>
                    <dl class="receipt-detail-list">
                        <div>
                            <dt>Event Type</dt>
                            <dd><?= $escape((string) ($booking['event_type'] ?? 'Booking')) ?></dd>
                        </div>
                        <div>
                            <dt>Event Schedule</dt>
                            <dd><?= $escape($formatDate((string) ($booking['event_date'] ?? '')) . ' at ' . $formatTime((string) ($booking['event_time'] ?? ''))) ?></dd>
                        </div>
                        <div>
                            <dt>Venue</dt>
                            <dd><?= $escape((string) ($booking['venue_name'] ?? 'Venue to follow')) ?></dd>
                        </div>
                        <div>
                            <dt>Guest Count</dt>
                            <dd><?= $escape((string) ((int) ($booking['guest_count'] ?? 0)) . ' guests') ?></dd>
                        </div>
                    </dl>
                </article>
            </section>

            <section class="receipt-card__meta">
                <article class="receipt-grid__item">
                    <p class="receipt-grid__label">Generated On</p>
                    <strong class="receipt-grid__value"><?= $escape($formatDateTime((string) ($receipt['updated_at'] ?? $receipt['created_at'] ?? ''))) ?></strong>
                </article>
                <article class="receipt-grid__item">
                    <p class="receipt-grid__label">Receipt Status</p>
                    <strong class="receipt-grid__value"><?= $escape(ucfirst((string) ($receipt['status'] ?? 'verified'))) ?></strong>
                </article>
            </section>

            <p class="receipt-card__footnote">Keep this receipt for your records. Use Download PDF if you want a clean one-page copy.</p>
        </section>
    </main>

    <?php if ($autoPrint): ?>
        <script>
            window.addEventListener("load", () => {
                window.setTimeout(() => window.print(), 250);
            });
        </script>
    <?php endif; ?>
</body>
</html>
