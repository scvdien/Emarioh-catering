<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

emarioh_require_method('POST');

$db = emarioh_db();
$currentUser = emarioh_require_role('client');
$data = emarioh_request_data();

$eventType = emarioh_normalize_name((string) ($data['event_type'] ?? ''));
$eventDateValue = trim((string) ($data['event_date'] ?? ''));
$eventTimeValue = trim((string) ($data['event_time'] ?? ''));
$guestCount = (int) ($data['guest_count'] ?? 0);
$venueOption = strtolower(trim((string) ($data['venue_option'] ?? 'own')));
$venueName = emarioh_normalize_name((string) ($data['venue_name'] ?? ''));
$packageCategoryValue = trim((string) ($data['package_category_value'] ?? ''));
$packageSelectionValue = trim((string) ($data['package_selection_value'] ?? ''));
$packageLabel = trim((string) ($data['package_label'] ?? ''));
$packageTierLabel = trim((string) ($data['package_tier_label'] ?? ''));
$packageTierPrice = trim((string) ($data['package_tier_price'] ?? ''));
$packageAllowsDownPayment = !empty($data['package_allows_down_payment']);
$packageDownPaymentAmount = trim((string) ($data['package_down_payment_amount'] ?? ''));
$primaryContact = emarioh_normalize_name((string) ($data['primary_contact'] ?? ''));
$primaryMobile = emarioh_normalize_mobile((string) ($data['primary_mobile'] ?? ''));
$primaryEmail = strtolower(trim((string) ($data['primary_email'] ?? '')));
$alternateContact = trim((string) ($data['alternate_contact'] ?? ''));
$eventNotes = trim((string) ($data['event_notes'] ?? ''));

if ($eventType === '') {
    emarioh_fail('Please select an event type.');
}

$eventDate = DateTimeImmutable::createFromFormat('Y-m-d', $eventDateValue);
$today = new DateTimeImmutable('today');

if (!$eventDate || $eventDate->format('Y-m-d') !== $eventDateValue) {
    emarioh_fail('Please choose a valid event date.');
}

if ($eventDate < $today) {
    emarioh_fail('Please choose an event date that is not in the past.');
}

if (emarioh_booking_date_has_conflict($db, $eventDateValue)) {
    emarioh_fail('The selected event date is already booked. Please choose another date.');
}

$eventTime = DateTimeImmutable::createFromFormat('H:i', $eventTimeValue);

if (!$eventTime || $eventTime->format('H:i') !== $eventTimeValue) {
    emarioh_fail('Please choose a valid event start time.');
}

if ($guestCount < 20) {
    emarioh_fail('Guest count must be at least 20.');
}

if (!in_array($venueOption, ['own', 'emarioh'], true)) {
    emarioh_fail('Please choose a valid venue option.');
}

if ($venueOption === 'emarioh') {
    $venueName = 'Emarioh In-House Venue';
}

if ($venueName === '') {
    emarioh_fail('Please provide the venue or event location.');
}

if ($packageSelectionValue === '' || $packageLabel === '') {
    emarioh_fail('Please choose a package before submitting your request.');
}

if ($primaryContact === '') {
    emarioh_fail('Please provide the main contact name.');
}

if (!emarioh_is_valid_mobile($primaryMobile)) {
    emarioh_fail('Please enter a valid Philippine mobile number.');
}

if ($primaryEmail !== '' && filter_var($primaryEmail, FILTER_VALIDATE_EMAIL) === false) {
    emarioh_fail('Please enter a valid email address.');
}

$packageCode = trim((string) explode('::', $packageSelectionValue, 2)[0]);
$packageRecord = emarioh_find_service_package_by_code($db, $packageCode);

if ($packageRecord !== null) {
    $currentPackageAllowsDownPayment = (int) ($packageRecord['allow_down_payment'] ?? 0) === 1;
    $currentPackageDownPaymentAmount = $currentPackageAllowsDownPayment
        ? emarioh_resolve_package_down_payment_amount_label($packageRecord, $packageTierLabel)
        : '';

    $packageAllowsDownPayment = $currentPackageAllowsDownPayment && $currentPackageDownPaymentAmount !== '';

    if ($currentPackageAllowsDownPayment) {
        $packageDownPaymentAmount = $currentPackageDownPaymentAmount;
    } else {
        $packageDownPaymentAmount = '';
    }
}

$reference = emarioh_generate_booking_reference($db);

try {
    $db->beginTransaction();

    emarioh_upsert_client_profile(
        $db,
        (int) $currentUser['id'],
        $primaryEmail === '' ? null : $primaryEmail,
        $alternateContact === '' ? null : $alternateContact
    );

    $db->prepare('
        INSERT INTO booking_requests (
            reference,
            user_id,
            event_type,
            event_date,
            event_time,
            guest_count,
            venue_option,
            venue_name,
            package_category_value,
            package_selection_value,
            package_label,
            package_id,
            package_tier_label,
            package_tier_price,
            package_allows_down_payment,
            package_down_payment_amount,
            primary_contact,
            primary_mobile,
            primary_email,
            alternate_contact,
            event_notes,
            booking_source
        ) VALUES (
            :reference,
            :user_id,
            :event_type,
            :event_date,
            :event_time,
            :guest_count,
            :venue_option,
            :venue_name,
            :package_category_value,
            :package_selection_value,
            :package_label,
            :package_id,
            :package_tier_label,
            :package_tier_price,
            :package_allows_down_payment,
            :package_down_payment_amount,
            :primary_contact,
            :primary_mobile,
            :primary_email,
            :alternate_contact,
            :event_notes,
            :booking_source
        )
    ')->execute([
        ':reference' => $reference,
        ':user_id' => (int) $currentUser['id'],
        ':event_type' => $eventType,
        ':event_date' => $eventDateValue,
        ':event_time' => $eventTimeValue . ':00',
        ':guest_count' => $guestCount,
        ':venue_option' => $venueOption,
        ':venue_name' => $venueName,
        ':package_category_value' => $packageCategoryValue === '' ? null : $packageCategoryValue,
        ':package_selection_value' => $packageSelectionValue,
        ':package_label' => $packageLabel,
        ':package_id' => $packageRecord ? (int) $packageRecord['id'] : null,
        ':package_tier_label' => $packageTierLabel === '' ? null : $packageTierLabel,
        ':package_tier_price' => $packageTierPrice === '' ? null : $packageTierPrice,
        ':package_allows_down_payment' => $packageAllowsDownPayment ? 1 : 0,
        ':package_down_payment_amount' => $packageDownPaymentAmount === '' ? null : $packageDownPaymentAmount,
        ':primary_contact' => $primaryContact,
        ':primary_mobile' => $primaryMobile,
        ':primary_email' => $primaryEmail,
        ':alternate_contact' => $alternateContact === '' ? null : $alternateContact,
        ':event_notes' => $eventNotes === '' ? null : $eventNotes,
        ':booking_source' => 'client_portal',
    ]);

    $bookingId = (int) $db->lastInsertId();

    emarioh_log_booking_status(
        $db,
        $bookingId,
        (int) $currentUser['id'],
        null,
        'pending_review',
        'Booking request submitted',
        sprintf(
            '%s submitted %s for %s.',
            emarioh_first_name((string) $currentUser['full_name']),
            $eventType,
            $eventDate->format('F j, Y')
        ),
        $eventNotes === '' ? null : $eventNotes
    );

    $db->commit();
} catch (Throwable $throwable) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    emarioh_fail('Booking request could not be saved right now. Please try again in a moment.', 500);
}

try {
    $createdBooking = emarioh_find_booking_by_id($db, $bookingId);

    if ($createdBooking !== null) {
        emarioh_send_booking_sms_template(
            $db,
            $createdBooking,
            'booking_received',
            [
                'trigger_label' => 'Booking received',
                'source_label' => 'Booking Management',
            ]
        );
    }
} catch (Throwable $throwable) {
    // Keep booking creation successful even when the SMS gateway is unavailable.
}

emarioh_success([
    'message' => 'Booking request submitted successfully.',
    'booking_id' => $bookingId,
    'reference' => $reference,
    'redirect_url' => 'client-my-bookings.php',
], 201);
