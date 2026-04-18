<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

emarioh_require_method('POST');

$db = emarioh_db();
$currentUser = emarioh_require_role('client');
$data = emarioh_request_data();

$fullName = emarioh_normalize_name((string) ($data['full_name'] ?? ''));
$mobile = emarioh_normalize_mobile((string) ($data['mobile'] ?? ''));
$alternateContact = trim((string) ($data['alternate_contact'] ?? ''));
$currentUserMobile = (string) ($currentUser['mobile'] ?? '');
$mobileChanged = $mobile !== $currentUserMobile;
$mobileOtpPurpose = 'client_mobile_update';

if ($fullName === '') {
    emarioh_fail('Enter your full name.');
}

if (!emarioh_is_valid_mobile($mobile)) {
    emarioh_fail('Enter a valid mobile number.');
}

$existingUser = emarioh_find_user_by_mobile($db, $mobile);
$currentProfile = emarioh_find_client_profile($db, (int) $currentUser['id']);

if ($existingUser !== null && (int) ($existingUser['id'] ?? 0) !== (int) $currentUser['id']) {
    emarioh_fail('That mobile number is already registered to another account.', 409);
}

if ($mobileChanged && !emarioh_has_verified_otp($mobile, $mobileOtpPurpose)) {
    emarioh_fail('Verify the OTP sent to the new mobile number before saving your account.', 409);
}

$timestamp = time();

try {
    $db->beginTransaction();

    $db->prepare('
        UPDATE users
        SET full_name = :full_name,
            mobile = :mobile,
            updated_at = :updated_at
        WHERE id = :id
        LIMIT 1
    ')->execute([
        ':full_name' => $fullName,
        ':mobile' => $mobile,
        ':updated_at' => $timestamp,
        ':id' => (int) $currentUser['id'],
    ]);

    emarioh_upsert_client_profile(
        $db,
        (int) $currentUser['id'],
        isset($currentProfile['email']) ? (string) $currentProfile['email'] : null,
        $alternateContact === '' ? null : $alternateContact
    );

    $db->commit();
} catch (Throwable $throwable) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    emarioh_fail('Your account details could not be updated right now. Please try again in a moment.', 500);
}

if ($mobileChanged) {
    emarioh_clear_verified_otp($mobile, $mobileOtpPurpose);
}

$updatedUser = emarioh_find_user_by_id($db, (int) $currentUser['id']);
$updatedProfile = emarioh_find_client_profile($db, (int) $currentUser['id']);

if ($updatedUser === null) {
    emarioh_fail('Your account details were saved, but the updated profile could not be loaded.', 500);
}

emarioh_success([
    'message' => 'Account details updated successfully.',
    'user' => emarioh_public_user($updatedUser),
    'profile' => [
        'alternate_contact' => trim((string) ($updatedProfile['alternate_contact'] ?? '')),
    ],
]);
