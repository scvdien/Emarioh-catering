<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

emarioh_require_method('POST');

$db = emarioh_db();
$currentUser = emarioh_require_authenticated_user();
$data = emarioh_request_data();
$bookingId = (int) ($data['booking_id'] ?? 0);
$nextStatus = strtolower(trim((string) ($data['status'] ?? '')));
$notes = trim((string) ($data['notes'] ?? ''));

if (!in_array((string) ($currentUser['role'] ?? ''), ['admin', 'client'], true)) {
    emarioh_fail('You do not have access to this action.', 403);
}

if ($bookingId <= 0) {
    emarioh_fail('Booking reference is invalid.');
}

$booking = emarioh_find_booking_by_id($db, $bookingId);

if ($booking === null) {
    emarioh_fail('Booking request not found.', 404);
}

$currentStatus = (string) ($booking['status'] ?? 'pending_review');
$package = emarioh_find_service_package_for_booking($db, $booking);
$role = (string) $currentUser['role'];
$smsNotification = [
    'ok' => false,
    'skipped' => true,
    'message' => 'SMS notification not attempted.',
];

if ($role === 'client') {
    if ((int) ($booking['user_id'] ?? 0) !== (int) $currentUser['id']) {
        emarioh_fail('You do not have access to this booking request.', 403);
    }

    if ($nextStatus !== 'cancelled') {
        emarioh_fail('Clients can only cancel their own booking requests.', 422);
    }

    if (!in_array($currentStatus, ['pending_review', 'approved'], true)) {
        emarioh_fail('This booking request can no longer be cancelled.', 422);
    }
} else {
    if (!in_array($nextStatus, ['approved', 'rejected'], true)) {
        emarioh_fail('Admins can only approve or reject pending bookings.', 422);
    }

    if ($currentStatus !== 'pending_review') {
        emarioh_fail('Only pending booking requests can be updated from the admin dashboard.', 422);
    }

    if ($nextStatus === 'approved' && emarioh_booking_date_has_conflict($db, (string) ($booking['event_date'] ?? ''), $bookingId)) {
        emarioh_fail('This event date is already booked by another approved reservation.', 422);
    }
}

try {
    $db->beginTransaction();

    if ($role === 'client') {
        $db->prepare('
            UPDATE booking_requests
            SET status = :status,
                cancelled_at = NOW()
            WHERE id = :id
        ')->execute([
            ':status' => 'cancelled',
            ':id' => $bookingId,
        ]);

        emarioh_log_booking_status(
            $db,
            $bookingId,
            (int) $currentUser['id'],
            $currentStatus,
            'cancelled',
            'Booking cancelled by client',
            'Client cancelled this booking request from the portal.',
            $notes === '' ? null : $notes
        );

        emarioh_cancel_payment_invoice_for_booking($db, $bookingId, (int) $currentUser['id']);
    } else {
        $db->prepare('
            UPDATE booking_requests
            SET status = :status,
                reviewed_at = NOW(),
                reviewed_by_user_id = :reviewed_by_user_id,
                approved_at = CASE WHEN :status = "approved" THEN NOW() ELSE NULL END,
                rejected_at = CASE WHEN :status = "rejected" THEN NOW() ELSE NULL END
            WHERE id = :id
        ')->execute([
            ':status' => $nextStatus,
            ':reviewed_by_user_id' => (int) $currentUser['id'],
            ':id' => $bookingId,
        ]);

        emarioh_log_booking_status(
            $db,
            $bookingId,
            (int) $currentUser['id'],
            $currentStatus,
            $nextStatus,
            $nextStatus === 'approved' ? 'Booking approved' : 'Booking rejected',
            $nextStatus === 'approved'
                ? 'Admin approved this booking request and moved it forward for coordination.'
                : 'Admin rejected this booking request during review.',
            $notes === '' ? null : $notes
        );

        if ($nextStatus === 'approved') {
            $updatedBooking = $booking;
            $updatedBooking['id'] = $bookingId;
            $updatedBooking['status'] = 'approved';

            $invoice = emarioh_ensure_payment_invoice_for_booking(
                $db,
                $updatedBooking,
                $package,
                (int) $currentUser['id']
            );

            if ($invoice === null) {
                throw new RuntimeException('The booking was approved, but the billing invoice could not be prepared.');
            }
        }
    }

    $db->commit();
} catch (Throwable $throwable) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    emarioh_fail('Booking status could not be updated right now. Please try again in a moment.', 500);
}

if ($role === 'admin') {
    try {
        $booking['id'] = $bookingId;
        $booking['status'] = $nextStatus;
        $smsNotification = emarioh_send_booking_status_sms($db, $booking, $nextStatus);
    } catch (Throwable $throwable) {
        $smsNotification = [
            'ok' => false,
            'skipped' => false,
            'message' => 'Booking status updated, but the SMS notification failed.',
        ];
    }
}

emarioh_success([
    'message' => 'Booking status updated successfully.',
    'booking_id' => $bookingId,
    'status' => $nextStatus,
    'status_label' => emarioh_booking_status_label($nextStatus),
    'admin_status_class' => emarioh_booking_admin_status_class($nextStatus),
    'client_status_class' => emarioh_booking_client_status_class($nextStatus),
    'filter_key' => emarioh_booking_filter_key($nextStatus),
    'sms_notification_attempted' => !($smsNotification['skipped'] ?? false),
    'sms_notification_sent' => (bool) ($smsNotification['ok'] ?? false),
    'sms_notification_message' => (string) ($smsNotification['message'] ?? ''),
]);
