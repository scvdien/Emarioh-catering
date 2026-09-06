<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

function check_result(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function notification_rows(PDO $db, int $bookingId, string $keyPrefix): array
{
    $statement = $db->prepare('
        SELECT *
        FROM booking_status_logs
        WHERE booking_id = :booking_id
          AND notes LIKE :key_prefix
        ORDER BY id ASC
    ');
    $statement->execute([
        ':booking_id' => $bookingId,
        ':key_prefix' => $keyPrefix . '%',
    ]);

    return $statement->fetchAll();
}

$db = emarioh_db();
$fixture = $db->query('
    SELECT pi.invoice_number, pi.booking_id
    FROM payment_invoices pi
    INNER JOIN booking_requests br ON br.id = pi.booking_id
    WHERE br.user_id IS NOT NULL
      AND br.status IN (\'approved\', \'completed\')
    ORDER BY pi.id DESC
    LIMIT 1
')->fetch();
$adminId = (int) ($db->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 0);

if (!is_array($fixture) || $adminId < 1) {
    fwrite(STDOUT, "SKIP: an approved client invoice and admin fixture are required.\n");
    exit(0);
}

$booking = emarioh_find_booking_by_id($db, (int) $fixture['booking_id']);
$baseInvoice = emarioh_find_payment_invoice_by_number($db, (string) $fixture['invoice_number']);
check_result(is_array($booking) && is_array($baseInvoice), 'Fixtures could not be loaded.');

$suffix = strtoupper(bin2hex(random_bytes(3)));
$onlinePaymentId = 'pay_verify_' . $suffix;
$manualReference = 'MPAY-' . date('Ymd') . '-' . $suffix;
$bookingId = (int) $booking['id'];
$userId = (int) $booking['user_id'];
$canVerifyOnlineWithoutSms = !emarioh_booking_sms_notifications_are_enabled();

try {
    if ($canVerifyOnlineWithoutSms) {
    $db->beginTransaction();
    $invoice = $baseInvoice;
    $invoice['amount_due'] = '2000.00';
    $invoice['amount_paid'] = '0.00';
    $invoice['balance_due'] = '2000.00';
    $invoice['gateway_payment_id'] = null;
    $invoice['gateway_paid_at'] = null;
    $onlineSummary = [
        'amount_paid' => 1250.00,
        'payment_id' => $onlinePaymentId,
        'payment_status' => 'paid',
        'checkout_status' => 'paid',
        'checkout_session_id' => 'cs_verify_' . $suffix,
    ];

    $updatedOnlineInvoice = emarioh_mark_payment_invoice_paid($db, $invoice, $onlineSummary);
    check_result(is_array($updatedOnlineInvoice), 'Online finalizer returned no invoice.');
    check_result(abs((float) $updatedOnlineInvoice['amount_paid'] - 1250.00) < 0.00001, 'Online amount was not posted.');
    $onlineRows = notification_rows($db, $bookingId, 'client-payment-notification:');
    check_result(count($onlineRows) === 1, 'Online finalizer did not create exactly one client notification.');
    check_result(($onlineRows[0]['to_status'] ?? '') === 'payment_received', 'Online notification type is incorrect.');

    $onlineReplay = emarioh_mark_payment_invoice_paid($db, $updatedOnlineInvoice, $onlineSummary);
    check_result(abs((float) $onlineReplay['amount_paid'] - 1250.00) < 0.00001, 'Online replay changed the paid amount.');
    check_result(count(notification_rows($db, $bookingId, 'client-payment-notification:')) === 1, 'Online replay duplicated the notification.');
    check_result(emarioh_count_unread_client_notifications($db, $userId) > 0, 'Online notification is not unread.');
    $db->rollBack();
    } else {
        fwrite(STDOUT, 'SKIP: online payment verification because booking SMS is enabled.' . PHP_EOL);
    }

    $db->beginTransaction();
    $invoice = $baseInvoice;
    $invoice['amount_due'] = '2000.00';
    $invoice['amount_paid'] = '0.00';
    $invoice['balance_due'] = '2000.00';
    $updatedManualInvoice = emarioh_record_manual_payment(
        $db,
        $invoice,
        $adminId,
        500.00,
        'Cash',
        $manualReference,
        'Rollback-only verification'
    );
    check_result(is_array($updatedManualInvoice), 'Manual finalizer returned no invoice.');
    check_result(abs((float) $updatedManualInvoice['amount_paid'] - 500.00) < 0.00001, 'Manual amount was not posted.');
    $manualRows = notification_rows($db, $bookingId, 'client-payment-notification:');
    check_result(count($manualRows) === 1, 'Manual finalizer did not create exactly one client notification.');
    check_result(($manualRows[0]['to_status'] ?? '') === 'manual_payment', 'Manual notification type is incorrect.');
    check_result((int) ($manualRows[0]['changed_by_user_id'] ?? 0) === $adminId, 'Manual notification does not identify the admin actor.');

    $manualNotificationId = (int) ($manualRows[0]['id'] ?? 0);
    $visibleManualNotifications = array_values(array_filter(
        emarioh_fetch_client_notifications($db, $userId),
        static fn (array $notification): bool => (int) ($notification['id'] ?? 0) === $manualNotificationId
    ));
    check_result(count($visibleManualNotifications) === 1, 'Manual notification is not visible to the linked client.');
    $visibleManualNotification = $visibleManualNotifications[0];
    check_result(($visibleManualNotification['title'] ?? '') === 'Manual payment recorded', 'Manual notification title is incorrect.');
    check_result(($visibleManualNotification['status'] ?? '') === 'manual_payment', 'Manual notification status is incorrect.');
    check_result(($visibleManualNotification['statusLabel'] ?? '') === 'Payment Recorded', 'Manual notification label is incorrect.');
    check_result(($visibleManualNotification['statusClass'] ?? '') === 'confirmed', 'Manual notification style is incorrect.');
    check_result(($visibleManualNotification['href'] ?? '') === 'client-billing.php', 'Manual notification does not open Billing.');
    check_result(($visibleManualNotification['icon'] ?? '') === 'bi-cash-coin', 'Manual notification icon is incorrect.');
    check_result(($visibleManualNotification['readStatus'] ?? '') === 'unread', 'New manual notification is not unread.');

    $latestBookingNotification = emarioh_build_client_booking_notification($booking, $manualRows[0]);
    check_result(($latestBookingNotification['status'] ?? '') === 'manual_payment', 'Latest booking notification lost the payment status.');
    check_result(($latestBookingNotification['href'] ?? '') === 'client-billing.php', 'Latest booking notification does not open Billing.');

    $manualReplay = emarioh_record_manual_payment(
        $db,
        $invoice,
        $adminId,
        500.00,
        'Cash',
        $manualReference,
        'Rollback-only verification'
    );
    check_result(abs((float) $manualReplay['amount_paid'] - 500.00) < 0.00001, 'Manual replay changed the paid amount.');
    check_result(count(notification_rows($db, $bookingId, 'client-payment-notification:')) === 1, 'Manual replay duplicated the notification.');
    $db->rollBack();

    fwrite(STDOUT, "PASS: online/manual finalizers, client notification visibility, and replay idempotency.\n");
} catch (Throwable $throwable) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    fwrite(STDERR, 'FAIL: ' . $throwable->getMessage() . "\n");
    exit(1);
}
