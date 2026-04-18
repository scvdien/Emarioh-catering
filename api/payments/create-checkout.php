<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

emarioh_require_method('POST');

$db = emarioh_db();
$currentUser = emarioh_require_role('client');
$data = emarioh_request_data();
$bookingId = (int) ($data['booking_id'] ?? 0);
$paymentOption = strtolower(trim((string) ($data['payment_option'] ?? '')));

if ($bookingId <= 0) {
    emarioh_fail('Choose a valid approved booking first.');
}

$booking = emarioh_find_booking_by_id($db, $bookingId);

if ($booking === null) {
    emarioh_fail('Booking request not found.', 404);
}

if ((int) ($booking['user_id'] ?? 0) !== (int) $currentUser['id']) {
    emarioh_fail('You do not have access to this booking invoice.', 403);
}

if (!in_array((string) ($booking['status'] ?? ''), ['approved', 'completed'], true)) {
    emarioh_fail('Billing opens only after your booking is approved.', 422);
}

if (!emarioh_paymongo_is_ready()) {
    emarioh_fail('PayMongo QRPh checkout is not configured yet. Please contact the admin first.', 409);
}

$package = emarioh_find_service_package_for_booking($db, $booking);
$paymentSettings = emarioh_fetch_payment_settings($db);

try {
    $db->beginTransaction();
    $invoice = emarioh_ensure_payment_invoice_for_booking($db, $booking, $package);

    if ($invoice === null) {
        throw new RuntimeException('Billing invoice could not be prepared for this booking.');
    }

    $db->commit();
} catch (Throwable $throwable) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    emarioh_fail('The billing invoice could not be prepared right now. Please try again in a moment.', 500);
}

$paymentPlan = emarioh_resolve_booking_payment_plan(
    $booking,
    $package,
    $invoice,
    (bool) ($paymentSettings['allow_full_payment'] ?? true),
    $paymentOption
);

if ($paymentOption !== '' && $paymentPlan['selected_option'] !== $paymentOption) {
    emarioh_fail('The selected payment option is not available anymore. Please refresh the page and try again.', 422);
}

if ((string) ($invoice['status'] ?? '') === 'approved' && (float) ($invoice['balance_due'] ?? 0) <= 0.00001) {
    emarioh_success([
        'message' => 'This invoice is already marked as paid.',
        'already_paid' => true,
        'invoice_number' => (string) ($invoice['invoice_number'] ?? ''),
    ]);
}

try {
    $syncedInvoice = emarioh_sync_paymongo_invoice_status($db, $invoice);
    $invoice = is_array($syncedInvoice) ? $syncedInvoice : $invoice;

    if ((string) ($invoice['status'] ?? '') === 'approved' && (float) ($invoice['balance_due'] ?? 0) <= 0.00001) {
        emarioh_success([
            'message' => 'This invoice is already marked as paid.',
            'already_paid' => true,
            'invoice_number' => (string) ($invoice['invoice_number'] ?? ''),
        ]);
    }

    $paymentPlan = emarioh_resolve_booking_payment_plan(
        $booking,
        $package,
        $invoice,
        (bool) ($paymentSettings['allow_full_payment'] ?? true),
        $paymentOption
    );

    if ($paymentOption !== '' && $paymentPlan['selected_option'] !== $paymentOption) {
        emarioh_fail('The selected payment option is not available anymore. Please refresh the page and try again.', 422);
    }

    $checkout = emarioh_create_paymongo_checkout_session($db, $invoice, $booking, $paymentPlan);
} catch (RuntimeException $exception) {
    emarioh_fail($exception->getMessage(), 502);
} catch (Throwable $throwable) {
    emarioh_fail('The PayMongo QRPh checkout could not be opened right now. Please try again in a moment.', 500);
}

emarioh_success([
    'message' => 'PayMongo QRPh checkout is ready.',
    'invoice_number' => (string) ($invoice['invoice_number'] ?? ''),
    'checkout_session_id' => (string) ($checkout['checkout_session_id'] ?? ''),
    'checkout_url' => (string) ($checkout['checkout_url'] ?? ''),
    'checkout_reference' => (string) ($checkout['reference_number'] ?? ''),
    'payment_option' => (string) ($paymentPlan['selected_option'] ?? 'full_payment'),
    'amount_to_pay' => (float) ($paymentPlan['charge_amount_value'] ?? 0),
    'already_paid' => false,
]);
