<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

emarioh_require_method('POST');

$db = emarioh_db();
$currentUser = emarioh_require_role('client');
$data = emarioh_request_data();
$bookingId = (int) ($data['booking_id'] ?? 0);

if ($bookingId <= 0) {
    emarioh_fail('Choose a valid booking first.');
}

$booking = emarioh_find_booking_by_id($db, $bookingId);

if ($booking === null) {
    emarioh_fail('Booking request not found.', 404);
}

if ((int) ($booking['user_id'] ?? 0) !== (int) $currentUser['id']) {
    emarioh_fail('You do not have access to this billing record.', 403);
}

$package = emarioh_find_service_package_for_booking($db, $booking);
$invoice = emarioh_find_payment_invoice_by_booking($db, $bookingId);

if ($invoice === null) {
    $invoice = emarioh_ensure_payment_invoice_for_booking($db, $booking, $package);
}

if ($invoice === null) {
    emarioh_fail('No active invoice was found for this booking.', 404);
}

try {
    if (!emarioh_payment_invoice_should_skip_checkout_sync($invoice)) {
        $invoice = emarioh_sync_paymongo_invoice_status($db, $invoice) ?? $invoice;
    }
} catch (RuntimeException $exception) {
    emarioh_fail($exception->getMessage(), 502);
} catch (Throwable $throwable) {
    emarioh_fail('The latest PayMongo payment status could not be fetched right now.', 500);
}

$billingDetails = emarioh_build_client_portal_billing_details($db, $booking, $package);

emarioh_success([
    'message' => 'Billing status refreshed successfully.',
    'invoice_number' => (string) ($invoice['invoice_number'] ?? ''),
    'billing_details' => $billingDetails,
    'paid' => (bool) ($billingDetails && (($billingDetails['pendingBalanceValue'] ?? 1) <= 0.00001)
        && (($billingDetails['statusPillClass'] ?? '') === 'approved')),
    'checkout_status' => (string) ($invoice['gateway_checkout_status'] ?? ''),
]);
