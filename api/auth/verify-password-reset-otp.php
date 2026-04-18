<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

emarioh_require_method('POST');

$db = emarioh_db();
$data = emarioh_request_data();
$mobile = emarioh_normalize_mobile((string) ($data['mobile'] ?? ''));
$otp = preg_replace('/\D+/', '', (string) ($data['otp'] ?? '')) ?? '';
$purpose = 'password_reset';

if (!emarioh_is_valid_mobile($mobile)) {
    emarioh_fail('Enter a valid mobile number first.');
}

if (strlen($otp) !== 6) {
    emarioh_fail('Enter the 6-digit OTP.');
}

$user = emarioh_find_user_by_mobile($db, $mobile);

if ($user === null) {
    emarioh_fail('No account found with that mobile number.', 404);
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
        'message' => 'OTP already verified. You can continue to the new password step.',
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
    'message' => 'OTP verified. You can now create your new password.',
]);
