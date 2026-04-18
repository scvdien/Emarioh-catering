<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

emarioh_require_method('POST');

$db = emarioh_db();
emarioh_logout_user($db);

emarioh_success([
    'message' => 'Logout successful.',
    'redirect_url' => 'login.php',
]);
