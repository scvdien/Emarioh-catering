<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

emarioh_require_method('POST');

$db = emarioh_db();
$currentUser = emarioh_require_role('admin');
$data = emarioh_request_data();
$fullName = emarioh_normalize_name((string) ($data['full_name'] ?? $currentUser['full_name'] ?? 'Admin'));
$mobile = emarioh_normalize_mobile((string) ($data['mobile'] ?? ''));
$currentUserId = (int) ($currentUser['id'] ?? 0);
$currentUserMobile = (string) ($currentUser['mobile'] ?? '');
$purpose = 'admin_mobile_update';

if ($fullName === '') {
    $fullName = (string) ($currentUser['full_name'] ?? 'Admin');
}

if (!emarioh_is_valid_mobile($mobile)) {
    emarioh_fail('Enter a valid mobile number.');
}

if ($mobile === $currentUserMobile) {
    emarioh_fail('Enter a new mobile number before requesting an OTP.', 409);
}

$existingUser = emarioh_find_user_by_mobile($db, $mobile);

if ($existingUser !== null && (int) ($existingUser['id'] ?? 0) !== $currentUserId) {
    emarioh_fail('That mobile number is already linked to another account.', 409);
}

$otp = emarioh_create_otp($db, $mobile, $purpose);

if (emarioh_sms_gateway_is_enabled()) {
    $smsResult = emarioh_send_otp_sms($db, $fullName, $mobile, $otp, $purpose);

    if (!($smsResult['ok'] ?? false)) {
        emarioh_delete_otp($db, $mobile, $purpose);
        emarioh_fail((string) ($smsResult['message'] ?? 'OTP could not be sent right now. Please try again in a moment.'), 502);
    }
}

$response = [
    'message' => emarioh_sms_gateway_is_enabled()
        ? 'OTP sent by SMS. Verify it before saving the new admin mobile number.'
        : 'OTP created. Verify it before saving the new admin mobile number.',
    'masked_mobile' => emarioh_mask_mobile($mobile),
    'expires_in' => EMARIOH_OTP_TTL,
];

if (emarioh_is_development_mode()) {
    $response['demo_otp'] = $otp;
}

emarioh_success($response, 201);
