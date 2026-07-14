<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

emarioh_require_method('POST');

$db = emarioh_db();
$currentUser = emarioh_require_role('client');
$data = emarioh_request_data();
$notificationId = (int) ($data['notification_id'] ?? $data['id'] ?? 0);

if ($notificationId < 1) {
    emarioh_fail('Choose a valid notification first.');
}

if (!emarioh_delete_client_notification($db, (int) $currentUser['id'], $notificationId)) {
    emarioh_fail('The selected notification could not be found.', 404);
}

emarioh_success([
    'message' => 'Notification deleted.',
    'unread_total' => emarioh_count_unread_client_notifications($db, (int) $currentUser['id']),
]);
