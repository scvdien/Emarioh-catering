<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

emarioh_require_method('POST');

$db = emarioh_db();
emarioh_require_role('admin');
$data = emarioh_request_data();
$bookingId = (int) ($data['booking_id'] ?? 0);
$templateKey = strtolower(trim((string) ($data['template_key'] ?? '')));

if ($bookingId <= 0) {
    emarioh_fail('Choose a valid booking first.');
}

if (!in_array($templateKey, ['downpayment_reminder', 'final_event_reminder'], true)) {
    emarioh_fail('Choose a valid booking message first.');
}

$booking = emarioh_find_booking_by_id($db, $bookingId);

if ($booking === null) {
    emarioh_fail('Booking request not found.', 404);
}

if ($templateKey === 'downpayment_reminder') {
    $invoice = emarioh_find_payment_invoice_by_booking($db, $bookingId);

    if (!emarioh_can_send_downpayment_reminder($booking, $invoice)) {
        emarioh_fail('Down payment reminder is only available for approved bookings without a posted payment yet.');
    }

    $smsResult = emarioh_send_booking_sms_template(
        $db,
        $booking,
        'downpayment_reminder',
        [
            'trigger_label' => 'Down payment reminder',
            'source_label' => 'Payment Management',
        ]
    );
} else {
    if (!emarioh_can_send_final_event_reminder($booking)) {
        emarioh_fail('Final event reminder is only available for upcoming approved bookings.');
    }

    $smsResult = emarioh_send_booking_sms_template(
        $db,
        $booking,
        'final_event_reminder',
        [
            'trigger_label' => 'Final event reminder',
            'source_label' => 'Booking Management',
        ]
    );
}

if (!(bool) ($smsResult['ok'] ?? false)) {
    emarioh_fail((string) ($smsResult['message'] ?? 'Booking message could not be sent right now.'), 409);
}

emarioh_success([
    'message' => $templateKey === 'downpayment_reminder'
        ? 'Down payment reminder sent successfully.'
        : 'Final event reminder sent successfully.',
    'booking_id' => $bookingId,
    'template_key' => $templateKey,
]);
