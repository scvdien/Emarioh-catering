<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

emarioh_require_method('POST');

$db = emarioh_db();
$data = emarioh_request_data();
$currentUser = emarioh_current_user();

$fullName = emarioh_normalize_name((string) ($data['name'] ?? $data['Name'] ?? ''));
$email = strtolower(trim((string) ($data['email'] ?? $data['Email'] ?? '')));
$message = trim((string) ($data['message'] ?? $data['Message'] ?? ''));
$subject = trim((string) ($data['subject'] ?? $data['Subject'] ?? ''));

if ($fullName === '') {
    emarioh_fail('Please enter your name.');
}

if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    emarioh_fail('Please enter a valid email address.');
}

if ($message === '') {
    emarioh_fail('Please enter your message.');
}

$messageLength = function_exists('mb_strlen') ? mb_strlen($message) : strlen($message);

if ($messageLength > 5000) {
    emarioh_fail('Please keep your message under 5000 characters.');
}

$mobile = '';

if (is_array($currentUser)) {
    $mobile = trim((string) ($currentUser['mobile'] ?? ''));
}

try {
    $inquiry = emarioh_create_website_inquiry($db, [
        'user_id' => is_array($currentUser) ? (int) ($currentUser['id'] ?? 0) : null,
        'full_name' => $fullName,
        'email' => $email,
        'mobile' => $mobile,
        'subject' => $subject,
        'message' => $message,
        'source' => 'Public Website',
    ]);
} catch (Throwable $throwable) {
    emarioh_fail('Your inquiry could not be saved right now. Please try again in a moment.', 500);
}

emarioh_success([
    'message' => 'Your message was sent to the Emarioh admin inbox.',
    'inquiry' => $inquiry,
], 201);
