<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

emarioh_require_method('POST');

$db = emarioh_db();
$data = emarioh_request_data();
$fullName = emarioh_normalize_name((string) ($data['full_name'] ?? ''));
$mobile = emarioh_normalize_mobile((string) ($data['mobile'] ?? ''));
$adminExists = emarioh_admin_exists($db);
$expectedMode = $adminExists ? 'client_registration' : 'admin_setup';
$mode = (string) ($data['mode'] ?? $expectedMode);
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
    emarioh_fail($mode === 'admin_setup' ? 'Enter the admin name first.' : 'Enter your full name first.');
}

if (!emarioh_is_valid_mobile($mobile)) {
    emarioh_fail('Enter a valid mobile number.');
}

$existingUser = emarioh_find_user_by_mobile($db, $mobile);

if ($existingUser !== null) {
    emarioh_fail('That mobile number is already registered. Please log in instead.', 409, [
        'redirect_url' => 'login.php',
    ]);
}

$otp = emarioh_create_otp($db, $mobile, $purpose);

if (emarioh_sms_gateway_is_enabled()) {
    $smsResult = emarioh_send_otp_sms($db, $fullName, $mobile, $otp, $purpose);

    if (!($smsResult['ok'] ?? false)) {
        emarioh_delete_otp($db, $mobile, $purpose);
        emarioh_fail((string) ($smsResult['message'] ?? 'OTP could not be sent right now. Please try again in a moment.'), 502);
    }
}

$response = [
    'message' => emarioh_sms_gateway_is_enabled()
        ? (
            $mode === 'admin_setup'
                ? 'Setup OTP sent by SMS. Verify it to continue with the first admin account.'
                : 'OTP sent by SMS. Verify it to continue with account registration.'
        )
        : (
            $mode === 'admin_setup'
                ? 'Setup OTP created. Verify it to continue with the first admin account.'
                : 'OTP created. Verify it to continue with account registration.'
        ),
    'masked_mobile' => emarioh_mask_mobile($mobile),
    'expires_in' => EMARIOH_OTP_TTL,
    'mode' => $mode,
];

if (emarioh_is_development_mode()) {
    $response['demo_otp'] = $otp;
}

emarioh_success($response, 201);
