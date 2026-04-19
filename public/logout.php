<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$db = emarioh_db();
emarioh_logout_user($db);

emarioh_redirect('login.php');
