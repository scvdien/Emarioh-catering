<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

emarioh_require_method('POST');

$db = emarioh_db();
$data = emarioh_request_data();
$fullName = emarioh_normalize_name((string) ($data['full_name'] ?? ''));
$mobile = emarioh_normalize_mobile((string) ($data['mobile'] ?? ''));
$password = (string) ($data['password'] ?? '');
$adminExists = emarioh_admin_exists($db);
$expectedMode = $adminExists ? 'client_registration' : 'admin_setup';
$mode = (string) ($data['mode'] ?? $expectedMode);
$role = $mode === 'admin_setup' ? 'admin' : 'client';
$purpose = $mode === 'admin_setup' ? 'admin_setup' : 'client_registration';

if ($mode !== $expectedMode) {
    emarioh_fail(
        $adminExists
            ? 'The admin account is already active. Continue with client registration instead.'
            : 'Create the first admin account before registering client users.',
        409,
        [
            'expected_mode' => $expectedMode,
        ]
    );
}

if ($fullName === '') {
    emarioh_fail($mode === 'admin_setup' ? 'Enter the admin name.' : 'Enter your full name.');
}

if (!emarioh_is_valid_mobile($mobile)) {
    emarioh_fail('Enter a valid mobile number.');
}

if (strlen($password) < 8) {
    emarioh_fail('Password must be at least 8 characters.');
}

if (emarioh_find_user_by_mobile($db, $mobile) !== null) {
    emarioh_fail('That mobile number is already registered. Please log in instead.', 409, [
        'redirect_url' => 'login.php',
    ]);
}

if (!emarioh_has_verified_otp($mobile, $purpose)) {
    emarioh_fail('Verify your OTP first before creating the account.', 409);
}

$timestamp = time();
$db->prepare('
    INSERT INTO users (full_name, mobile, role, password_hash, created_at, updated_at)
    VALUES (:full_name, :mobile, :role, :password_hash, :created_at, :updated_at)
')->execute([
    ':full_name' => $fullName,
    ':mobile' => $mobile,
    ':role' => $role,
    ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ':created_at' => $timestamp,
    ':updated_at' => $timestamp,
]);

$user = emarioh_find_user_by_id($db, (int) $db->lastInsertId());
emarioh_clear_verified_otp($mobile, $purpose);

if ($user === null) {
    emarioh_fail('The account was saved, but the profile could not be loaded. Please try logging in.', 500);
}

if ($role === 'client') {
    emarioh_login_user($db, $user);

    emarioh_success([
        'message' => 'Client account created successfully.',
        'authenticated' => true,
        'user' => emarioh_public_user($user),
        'redirect_url' => emarioh_role_landing_url($role),
    ], 201);
}

emarioh_success([
    'message' => 'Admin account created successfully. You can now log in to the dashboard.',
    'authenticated' => false,
    'user' => emarioh_public_user($user),
    'redirect_url' => 'login.php?registered=1&role=admin',
], 201);
