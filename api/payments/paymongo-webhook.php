<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

emarioh_require_method('POST');

$db = emarioh_db();
$rawBody = file_get_contents('php://input');
$rawBody = is_string($rawBody) ? $rawBody : '';

try {
    $payload = $rawBody !== ''
        ? json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR)
        : [];
} catch (Throwable $throwable) {
    emarioh_json_response([
        'ok' => false,
        'message' => 'Invalid webhook payload.',
    ], 400);
}

$eventData = is_array($payload['data'] ?? null) ? $payload['data'] : [];
$eventAttributes = is_array($eventData['attributes'] ?? null) ? $eventData['attributes'] : [];
$eventType = trim((string) ($eventAttributes['type'] ?? ''));
$isLiveMode = (bool) ($eventAttributes['livemode'] ?? false);
$signatureHeader = trim((string) (
    $_SERVER['HTTP_PAYMONGO_SIGNATURE']
    ?? $_SERVER['REDIRECT_HTTP_PAYMONGO_SIGNATURE']
    ?? ''
));

if ($signatureHeader !== '' && !emarioh_verify_paymongo_signature($rawBody, $signatureHeader, $isLiveMode)) {
    emarioh_json_response([
        'ok' => false,
        'message' => 'Invalid PayMongo signature.',
    ], 401);
}

if ($eventType === 'checkout_session.payment.paid') {
    $checkoutSession = [
        'data' => $eventAttributes['data'] ?? [],
    ];
    $paymentSummary = emarioh_extract_paymongo_payment_summary($checkoutSession);
    $metadata = is_array($paymentSummary['metadata'] ?? null) ? $paymentSummary['metadata'] : [];
    $invoiceNumber = trim((string) ($metadata['invoice_number'] ?? ''));
    $invoice = $invoiceNumber !== ''
        ? emarioh_find_payment_invoice_by_number($db, $invoiceNumber)
        : null;

    if ($invoice === null && trim((string) ($paymentSummary['checkout_session_id'] ?? '')) !== '') {
        $invoice = emarioh_find_payment_invoice_by_checkout_session($db, (string) $paymentSummary['checkout_session_id']);
    }

    if ($invoice !== null && trim((string) ($paymentSummary['payment_status'] ?? '')) === 'paid') {
        emarioh_mark_payment_invoice_paid($db, $invoice, $paymentSummary);
    }
}

emarioh_json_response([
    'ok' => true,
    'message' => 'SUCCESS',
]);
