<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

emarioh_require_method('POST');

$db = emarioh_db();
emarioh_require_role('admin');
$data = emarioh_request_data();
$invoiceNumber = trim((string) ($data['invoice_number'] ?? ''));

if ($invoiceNumber === '') {
    emarioh_fail('Choose a valid invoice first.');
}

$invoice = emarioh_find_payment_invoice_by_number($db, $invoiceNumber);

if ($invoice === null) {
    emarioh_fail('Payment invoice not found.', 404);
}

$booking = emarioh_find_booking_by_id($db, (int) ($invoice['booking_id'] ?? 0));

if ($booking === null) {
    emarioh_fail('The related booking request could not be found.', 404);
}

$checkoutSessionId = trim((string) ($invoice['gateway_checkout_session_id'] ?? ''));

if ($checkoutSessionId === '') {
    emarioh_success([
        'message' => 'This invoice has no active PayMongo checkout yet.',
        'invoice_number' => $invoiceNumber,
        'status' => (string) ($invoice['status'] ?? 'pending'),
        'amount_paid' => (float) ($invoice['amount_paid'] ?? 0),
        'balance_due' => (float) ($invoice['balance_due'] ?? 0),
    ]);
}

if (!emarioh_paymongo_has_secret_key()) {
    emarioh_fail('PayMongo is not configured yet.', 409);
}

try {
    $updatedInvoice = emarioh_payment_invoice_should_skip_checkout_sync($invoice)
        ? $invoice
        : (emarioh_sync_paymongo_invoice_status($db, $invoice) ?? $invoice);
} catch (RuntimeException $exception) {
    emarioh_fail($exception->getMessage(), 502);
} catch (Throwable $throwable) {
    emarioh_fail('The latest PayMongo payment status could not be fetched right now.', 500);
}

$amountPaidValue = max(0, (float) ($updatedInvoice['amount_paid'] ?? 0));
$balanceDueValue = max(0, (float) ($updatedInvoice['balance_due'] ?? 0));
$status = strtolower(trim((string) ($updatedInvoice['status'] ?? 'pending')));
$message = 'Payment status refreshed. No new payment has been posted yet.';

if ($status === 'approved' && $balanceDueValue <= 0.00001) {
    $message = 'Payment status refreshed. This invoice is now marked as paid.';
} elseif ($amountPaidValue > 0) {
    $message = 'Payment status refreshed. A posted payment is already recorded for this invoice.';
}

emarioh_success([
    'message' => $message,
    'invoice_number' => (string) ($updatedInvoice['invoice_number'] ?? $invoiceNumber),
    'status' => $status,
    'amount_paid' => $amountPaidValue,
    'balance_due' => $balanceDueValue,
    'last_payment_at' => (string) ($updatedInvoice['last_payment_at'] ?? ''),
]);
