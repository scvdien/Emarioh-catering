<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

emarioh_require_method('POST');

$db = emarioh_db();
$data = emarioh_request_data();
$mobile = emarioh_normalize_mobile((string) ($data['mobile'] ?? ''));
$password = (string) ($data['password'] ?? '');
$purpose = 'password_reset';

if (!emarioh_is_valid_mobile($mobile)) {
    emarioh_fail('Enter a valid mobile number.');
}

if (strlen($password) < 8) {
    emarioh_fail('Password must be at least 8 characters.');
}

$user = emarioh_find_user_by_mobile($db, $mobile);

if ($user === null) {
    emarioh_fail('No account found with that mobile number.', 404);
}

if (!emarioh_has_verified_otp($mobile, $purpose)) {
    emarioh_fail('Verify your OTP first before resetting the password.', 409);
}

$db->prepare('UPDATE users SET password_hash = :password_hash, updated_at = :updated_at WHERE id = :id')
    ->execute([
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':updated_at' => time(),
        ':id' => (int) $user['id'],
    ]);

emarioh_revoke_all_remember_tokens($db, (int) $user['id']);
emarioh_delete_otp($db, $mobile, $purpose);
emarioh_clear_verified_otp($mobile, $purpose);

emarioh_success([
    'message' => 'Password updated successfully. Log in with your new password.',
    'redirect_url' => 'login.php?password_reset=1',
]);
