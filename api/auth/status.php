<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

$db = emarioh_db();
$currentUser = emarioh_current_user();

emarioh_success([
    'authenticated' => $currentUser !== null,
    'admin_exists' => emarioh_admin_exists($db),
    'user' => $currentUser ? emarioh_public_user($currentUser) : null,
    'redirect_url' => $currentUser ? emarioh_role_landing_url((string) $currentUser['role']) : null,
]);
