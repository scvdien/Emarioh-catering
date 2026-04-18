<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

emarioh_require_method('POST');

$db = emarioh_db();
$currentUser = emarioh_require_role('client');
$data = emarioh_request_data();
$mobile = emarioh_normalize_mobile((string) ($data['mobile'] ?? ''));
$otp = preg_replace('/\D+/', '', (string) ($data['otp'] ?? '')) ?? '';
$purpose = 'client_mobile_update';

if (!emarioh_is_valid_mobile($mobile)) {
    emarioh_fail('Enter a valid mobile number first.');
}

if ($mobile === (string) ($currentUser['mobile'] ?? '')) {
    emarioh_fail('Enter a new mobile number before verifying OTP.', 409);
}

if (strlen($otp) !== 6) {
    emarioh_fail('Enter the 6-digit OTP.');
}

$otpRecord = emarioh_find_latest_otp($db, $mobile, $purpose);

if ($otpRecord === null) {
    emarioh_fail('Request a new OTP first.', 404);
}

if ((int) $otpRecord['expires_at'] < time()) {
    emarioh_fail('That OTP has expired. Request a new one to continue.', 410);
}

if ((int) ($otpRecord['verified_at'] ?? 0) > 0) {
    emarioh_store_verified_otp($mobile, $purpose);

    emarioh_success([
        'message' => 'OTP already verified. You can now save the new mobile number.',
    ]);
}

if (!hash_equals((string) $otpRecord['code_hash'], hash('sha256', $otp))) {
    emarioh_fail('Incorrect OTP. Check the latest code and try again.', 401);
}

$db->prepare('UPDATE otp_codes SET verified_at = :verified_at WHERE id = :id')
    ->execute([
        ':verified_at' => time(),
        ':id' => (int) $otpRecord['id'],
    ]);

emarioh_store_verified_otp($mobile, $purpose);

emarioh_success([
    'message' => 'OTP verified. You can now save the new mobile number.',
]);
