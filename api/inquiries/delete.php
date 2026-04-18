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

$inquiry = emarioh_find_website_inquiry_by_id($db, $inquiryId);

if ($inquiry === null) {
    emarioh_fail('The selected inquiry could not be found.', 404);
}

if ((string) ($inquiry['status'] ?? 'unread') !== 'read') {
    emarioh_fail('Only read inquiries can be deleted right now.');
}

if (!emarioh_delete_website_inquiry($db, $inquiryId)) {
    emarioh_fail('The inquiry could not be deleted right now. Please try again.', 500);
}

emarioh_success([
    'message' => 'Inquiry deleted successfully.',
    'deleted_id' => $inquiryId,
]);
