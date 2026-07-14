<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

emarioh_require_method('POST');

$db = emarioh_db();
$currentUser = emarioh_require_role('admin');
$data = emarioh_request_data();

$invoiceNumber = trim((string) ($data['invoice_number'] ?? ''));
$amountRaw = trim((string) ($data['amount'] ?? ''));
$amountValue = str_contains($amountRaw, '-')
    ? 0.0
    : (float) (preg_replace('/[^0-9.]/', '', $amountRaw) ?? '0');
$methodKey = strtolower(trim((string) ($data['payment_method'] ?? 'personal_gcash')));
$referenceNumber = substr(trim((string) ($data['reference_number'] ?? '')), 0, 100);

if ($referenceNumber === '' || preg_match('/^MPAY-\d{8}-[A-F0-9]{6}$/', $referenceNumber) !== 1) {
    $referenceNumber = emarioh_generate_manual_payment_reference();
}

$notes = substr(trim((string) ($data['notes'] ?? '')), 0, 500);
$methodLabels = [
    'cash' => 'Cash',
    'personal_gcash' => 'Personal GCash',
    'bank_transfer' => 'Bank Transfer',
    'other' => 'Other Manual Payment',
];

if ($invoiceNumber === '') {
    emarioh_fail('Choose a valid invoice first.');
}

if ($amountValue <= 0.00001) {
    emarioh_fail('Enter a valid manual payment amount.');
}

if (!array_key_exists($methodKey, $methodLabels)) {
    emarioh_fail('Choose a valid manual payment method.');
}

$updatedInvoice = null;
$booking = null;

try {
    emarioh_ensure_admin_notifications_table($db);
    $db->beginTransaction();

    $statement = $db->prepare('
        SELECT *
        FROM payment_invoices
        WHERE invoice_number = :invoice_number
        LIMIT 1
        FOR UPDATE
    ');
    $statement->execute([
        ':invoice_number' => $invoiceNumber,
    ]);

    $invoice = $statement->fetch();

    if (!is_array($invoice)) {
        throw new RuntimeException('Payment invoice not found.', 404);
    }

    if (strtolower(trim((string) ($invoice['status'] ?? 'pending'))) === 'cancelled') {
        throw new InvalidArgumentException('Cancelled invoices cannot accept manual payments.', 409);
    }

    $booking = emarioh_find_booking_by_id($db, (int) ($invoice['booking_id'] ?? 0));

    if ($booking === null) {
        throw new RuntimeException('The related booking request could not be found.', 404);
    }

    if (!in_array((string) ($booking['status'] ?? ''), ['approved', 'completed'], true)) {
        throw new InvalidArgumentException('Manual payments are only available for approved or completed bookings.', 422);
    }

    $updatedInvoice = emarioh_record_manual_payment(
        $db,
        $invoice,
        (int) ($currentUser['id'] ?? 0),
        $amountValue,
        $methodLabels[$methodKey],
        $referenceNumber,
        $notes
    );

    if ($updatedInvoice === null) {
        throw new RuntimeException('Manual payment could not be recorded right now.', 500);
    }

    $db->commit();
} catch (Throwable $throwable) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    $statusCode = (int) $throwable->getCode();

    if ($statusCode < 400 || $statusCode > 599) {
        $statusCode = $throwable instanceof InvalidArgumentException ? 422 : 500;
    }

    emarioh_fail($throwable->getMessage(), $statusCode);
}

if ($booking !== null) {
    try {
        emarioh_send_booking_sms_template(
            $db,
            $booking,
            'payment_verified',
            [
                'trigger_label' => 'Manual payment recorded',
                'source_label' => 'Payment Management',
            ]
        );
    } catch (Throwable $throwable) {
        // Recording the manual payment should remain successful even if SMS is unavailable.
    }
}

$balanceDueValue = max(0, (float) ($updatedInvoice['balance_due'] ?? 0));
$amountPaidValue = max(0, (float) ($updatedInvoice['amount_paid'] ?? 0));

emarioh_success([
    'message' => $balanceDueValue <= 0.00001
        ? 'Manual payment recorded. This invoice is now fully paid.'
        : 'Manual payment recorded. Remaining balance: ' . emarioh_format_money_amount($balanceDueValue) . '.',
    'invoice_number' => (string) ($updatedInvoice['invoice_number'] ?? $invoiceNumber),
    'status' => (string) ($updatedInvoice['status'] ?? ''),
    'amount_paid' => $amountPaidValue,
    'balance_due' => $balanceDueValue,
    'amount_paid_label' => emarioh_format_money_amount($amountPaidValue),
    'balance_due_label' => emarioh_format_money_amount($balanceDueValue),
    'reference_number' => $referenceNumber,
]);
