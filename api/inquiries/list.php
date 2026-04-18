<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

emarioh_require_method('GET');

$db = emarioh_db();
emarioh_require_role('admin');

$limit = (int) ($_GET['limit'] ?? 100);
$limit = max(1, min($limit, 250));
$messages = emarioh_fetch_website_inquiries($db, [
    'limit' => $limit,
]);
$unreadTotal = count(array_filter(
    $messages,
    static fn (array $message): bool => (string) ($message['status'] ?? 'unread') === 'unread'
));

emarioh_success([
    'messages' => $messages,
    'total' => count($messages),
    'unread_total' => $unreadTotal,
]);
