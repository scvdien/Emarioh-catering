<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

emarioh_require_method('POST');

$db = emarioh_db();
$data = emarioh_request_data();
$mobile = emarioh_normalize_mobile((string) ($data['mobile'] ?? ''));
$purpose = 'password_reset';

if (!emarioh_is_valid_mobile($mobile)) {
    emarioh_fail('Enter a valid mobile number.');
}

$user = emarioh_find_user_by_mobile($db, $mobile);

if ($user === null) {
    emarioh_fail('No account found with that mobile number.', 404);
}

$otp = emarioh_create_otp($db, $mobile, $purpose);

if (emarioh_sms_gateway_is_enabled()) {
    $smsResult = emarioh_send_otp_sms($db, (string) $user['full_name'], $mobile, $otp, $purpose);

    if (!($smsResult['ok'] ?? false)) {
        emarioh_delete_otp($db, $mobile, $purpose);
        emarioh_fail((string) ($smsResult['message'] ?? 'OTP could not be sent right now. Please try again in a moment.'), 502);
    }
}

$response = [
    'message' => emarioh_sms_gateway_is_enabled()
        ? 'Password reset OTP sent by SMS. Verify it to continue.'
        : 'Password reset OTP created. Verify it to continue.',
    'masked_mobile' => emarioh_mask_mobile($mobile),
    'expires_in' => EMARIOH_OTP_TTL,
];

if (emarioh_is_development_mode()) {
    $response['demo_otp'] = $otp;
}

emarioh_success($response, 201);
