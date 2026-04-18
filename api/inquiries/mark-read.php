<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

emarioh_require_method('POST');

$db = emarioh_db();
emarioh_require_role('admin');

$data = emarioh_request_data();
$inquiryId = (int) ($data['inquiry_id'] ?? $data['id'] ?? 0);

if ($inquiryId < 1) {
    emarioh_fail('Choose a valid inquiry first.');
}

$inquiry = emarioh_update_website_inquiry_status($db, $inquiryId, 'read');

if ($inquiry === null) {
    emarioh_fail('The selected inquiry could not be found.', 404);
}

emarioh_success([
    'message' => 'Inquiry marked as read.',
    'inquiry' => $inquiry,
]);
