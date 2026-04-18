<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

emarioh_require_method('POST');

$db = emarioh_db();
$currentUser = emarioh_require_role('client');
$data = emarioh_request_data();

$currentPassword = (string) ($data['current_password'] ?? '');
$newPassword = (string) ($data['new_password'] ?? '');
$confirmPassword = (string) ($data['confirm_password'] ?? '');
$currentUserId = (int) ($currentUser['id'] ?? 0);

if ($currentPassword === '') {
    emarioh_fail('Enter your current password.');
}

if (!password_verify($currentPassword, (string) ($currentUser['password_hash'] ?? ''))) {
    emarioh_fail('The current password is incorrect.', 401);
}

if ($newPassword === '') {
    emarioh_fail('Enter a new password.');
}

if (strlen($newPassword) < 8) {
    emarioh_fail('Use at least 8 characters for the new password.');
}

if ($newPassword !== $confirmPassword) {
    emarioh_fail('New password and confirmation do not match.');
}

$db->prepare('
    UPDATE users
    SET password_hash = :password_hash,
        updated_at = :updated_at
    WHERE id = :id
    LIMIT 1
')->execute([
    ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
    ':updated_at' => time(),
    ':id' => $currentUserId,
]);

emarioh_revoke_all_remember_tokens($db, $currentUserId);

emarioh_success([
    'message' => 'Password updated successfully.',
]);
