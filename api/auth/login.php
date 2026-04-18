<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

emarioh_require_method('POST');

$db = emarioh_db();

if (!emarioh_admin_exists($db)) {
    emarioh_fail('Complete the first-time admin setup before using the shared login form.', 409, [
        'setup_required' => true,
        'redirect_url' => 'registration.php?setup=admin',
    ]);
}

$data = emarioh_request_data();
$mobile = emarioh_normalize_mobile((string) ($data['mobile'] ?? ''));
$password = (string) ($data['password'] ?? '');
$rememberMe = filter_var($data['remember_me'] ?? false, FILTER_VALIDATE_BOOLEAN);

if (!emarioh_is_valid_mobile($mobile)) {
    emarioh_fail('Enter a valid mobile number.');
}

if (trim($password) === '') {
    emarioh_fail('Enter your password.');
}

$user = emarioh_find_user_by_mobile($db, $mobile);

if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
    emarioh_fail('Incorrect mobile number or password.', 401);
}

emarioh_login_user($db, $user, $rememberMe);

emarioh_success([
    'message' => 'Login successful.',
    'authenticated' => true,
    'user' => emarioh_public_user($user),
    'redirect_url' => emarioh_role_landing_url((string) $user['role']),
]);
