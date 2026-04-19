<?php
declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';

$db = emarioh_db();
$currentUser = emarioh_require_page_role('admin');
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$settingsSectionRoutes = [
    'admin-account' => 'admin-settings-account.php',
    'manage-public-page' => 'admin-settings-public-page.php',
    'payment-settings' => 'admin-settings-payment.php',
    'sms-templates' => 'admin-settings-sms.php',
];
$settingsSectionMeta = [
    'admin-account' => [
        'title' => 'Admin Account',
        'eyebrow' => 'Settings',
        'description' => 'Update admin name, mobile number, and password.',
    ],
    'manage-public-page' => [
        'title' => 'Public Page',
        'eyebrow' => 'Settings',
        'description' => 'Manage the hero image, services, gallery, and contacts.',
    ],
    'payment-settings' => [
        'title' => 'Payment',
        'eyebrow' => 'Settings',
        'description' => 'Set down payment rules for each service.',
    ],
    'sms-templates' => [
        'title' => 'SMS Templates',
        'eyebrow' => 'Settings',
        'description' => 'Review message templates and supported placeholders.',
    ],
];
$settingsPageSection = isset($settingsPageSection) ? trim((string) $settingsPageSection) : '';
$settingsPageIsDetail = $settingsPageSection !== '';
$settingsPageMeta = $settingsSectionMeta[$settingsPageSection] ?? [
    'title' => 'Profile & Settings',
    'eyebrow' => 'Admin Account',
    'description' => 'Manage your account, shortcuts, and system preferences.',
];
$settingsPageTitle = isset($settingsPageTitle) ? trim((string) $settingsPageTitle) : $settingsPageMeta['title'];
$settingsPageEyebrow = isset($settingsPageEyebrow) ? trim((string) $settingsPageEyebrow) : $settingsPageMeta['eyebrow'];
$settingsPageDescription = isset($settingsPageDescription) ? trim((string) $settingsPageDescription) : $settingsPageMeta['description'];
$settingsBackHref = isset($settingsBackHref) ? trim((string) $settingsBackHref) : ($settingsPageIsDetail ? 'admin-settings-menu.php' : '');
$settingsBackLabel = isset($settingsBackLabel) ? trim((string) $settingsBackLabel) : ($settingsPageIsDetail ? 'Back to settings menu' : '');
$settingsNavHref = $settingsPageIsDetail ? 'admin-settings-menu.php' : 'admin-settings.php';
$settingsSectionUrl = static function (string $sectionId, array $query = []) use ($settingsPageIsDetail, $settingsSectionRoutes): string {
    $sectionId = trim($sectionId);
    $basePath = 'admin-settings.php';

    if ($settingsPageIsDetail && isset($settingsSectionRoutes[$sectionId])) {
        $basePath = $settingsSectionRoutes[$sectionId];
    }

    if ($query !== []) {
        $basePath .= '?' . http_build_query($query);
    }

    if (!$settingsPageIsDetail && $sectionId !== '') {
        $basePath .= '#' . $sectionId;
    }

    return $basePath;
};
$settingsSectionHiddenAttr = static function (string $sectionId) use ($settingsPageIsDetail, $settingsPageSection): string {
    if (!$settingsPageIsDetail) {
        return '';
    }

    return $settingsPageSection === $sectionId ? '' : ' hidden';
};
$publicSiteSettings = emarioh_fetch_public_site_settings($db);
$heroImagePath = emarioh_normalize_public_asset_path(
    (string) ($publicSiteSettings['hero_image_path'] ?? ''),
    ''
);
$heroImageUrl = emarioh_public_asset_url($heroImagePath);
$heroImageAbsolutePath = $heroImagePath !== '' ? emarioh_public_asset_absolute_path($heroImagePath) : null;
$hasHeroImage = $heroImageAbsolutePath !== null && is_file($heroImageAbsolutePath) && $heroImageUrl !== '';

if (!$hasHeroImage) {
    $heroImagePath = '';
    $heroImageUrl = '';
}

$heroImageFileName = $hasHeroImage ? basename($heroImagePath) : 'No image uploaded yet';
$heroNoticeMessage = '';
$heroNoticeClass = 'danger';
$publicServiceCards = emarioh_fetch_public_service_cards($db);
$serviceCardsForm = $publicServiceCards;
$servicesNoticeMessage = '';
$servicesNoticeClass = 'danger';
$galleryCategoryOptions = emarioh_gallery_category_options();
$galleryItems = emarioh_fetch_gallery_items($db);
$galleryForm = [
    'category' => 'wedding',
    'caption' => '',
];
$galleryNoticeMessage = '';
$galleryNoticeClass = 'danger';
$adminAccountNoticeMessage = '';
$adminAccountNoticeClass = 'danger';
$adminAccountFlashMessage = '';
$adminAccountFlashState = '';
$adminAccountFlashIcon = 'bi-info-circle';
$adminAccountFlashSessionKey = 'emarioh_admin_account_flash';

if (isset($_SESSION[$adminAccountFlashSessionKey]) && is_array($_SESSION[$adminAccountFlashSessionKey])) {
    $adminAccountFlashData = $_SESSION[$adminAccountFlashSessionKey];
    unset($_SESSION[$adminAccountFlashSessionKey]);

    $adminAccountNoticeMessage = trim((string) ($adminAccountFlashData['message'] ?? ''));
    $adminAccountNoticeClass = trim((string) ($adminAccountFlashData['class'] ?? 'danger')) ?: 'danger';
    $adminAccountFlashForm = $adminAccountFlashData['form'] ?? null;

    if (is_array($adminAccountFlashForm)) {
        $adminAccountForm = [
            'full_name' => trim((string) ($adminAccountFlashForm['full_name'] ?? '')),
            'mobile' => trim((string) ($adminAccountFlashForm['mobile'] ?? '')),
        ];
    }
}

if ((string) ($_GET['admin_account'] ?? '') === 'password_updated') {
    $adminAccountFlashMessage = 'Password updated successfully.';
    $adminAccountFlashState = 'success';
    $adminAccountFlashIcon = 'bi-check2-circle';
}

$adminMobileOtpPurpose = 'admin_mobile_update';
$adminAccountCurrentMobileRaw = (string) ($currentUser['mobile'] ?? '');
$adminAccountForm = $adminAccountForm ?? [
    'full_name' => trim((string) ($currentUser['full_name'] ?? '')),
    'mobile' => emarioh_format_mobile($adminAccountCurrentMobileRaw),
];
$contactDetailsForm = [
    'service_area' => trim((string) ($publicSiteSettings['service_area'] ?? '')),
    'public_email' => trim((string) ($publicSiteSettings['public_email'] ?? '')),
    'primary_mobile' => trim((string) ($publicSiteSettings['primary_mobile'] ?? '')),
    'business_hours' => trim((string) ($publicSiteSettings['business_hours'] ?? '')),
];
$contactDetailsDisplay = [
    'secondary_mobile' => trim((string) ($publicSiteSettings['secondary_mobile'] ?? '')),
    'facebook_url' => trim((string) ($publicSiteSettings['facebook_url'] ?? '')),
    'messenger_url' => trim((string) ($publicSiteSettings['messenger_url'] ?? '')),
    'business_address' => trim((string) ($publicSiteSettings['business_address'] ?? '')),
    'map_embed_url' => trim((string) ($publicSiteSettings['map_embed_url'] ?? '')),
];
$contactsNoticeMessage = '';
$contactsNoticeClass = 'danger';
$smsTemplatesForm = emarioh_fetch_sms_templates($db);
$smsTemplatesNoticeMessage = '';
$smsTemplatesNoticeClass = 'danger';
$smsTemplatePlaceholderTokens = [];

foreach ($smsTemplatesForm as $smsTemplate) {
    $placeholderTokens = array_filter(array_map(
        static fn (string $token): string => trim($token),
        explode(',', (string) ($smsTemplate['placeholders'] ?? ''))
    ));

    foreach ($placeholderTokens as $placeholderToken) {
        if (!in_array($placeholderToken, $smsTemplatePlaceholderTokens, true)) {
            $smsTemplatePlaceholderTokens[] = $placeholderToken;
        }
    }
}

$packageCatalogJson = json_encode(
    emarioh_fetch_service_package_catalog($db),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?: '[]';

function emarioh_admin_settings_public_asset_meta(?string $relativePath, string $emptyLabel = 'No image uploaded yet'): array
{
    $normalizedPath = emarioh_normalize_public_asset_path((string) $relativePath, '');
    $assetUrl = $normalizedPath !== '' ? emarioh_public_asset_url($normalizedPath) : '';
    $assetAbsolutePath = $normalizedPath !== '' ? emarioh_public_asset_absolute_path($normalizedPath) : null;
    $hasAsset = $assetAbsolutePath !== null && is_file($assetAbsolutePath) && $assetUrl !== '';

    if (!$hasAsset) {
        return [
            'path' => '',
            'url' => '',
            'absolute_path' => null,
            'has_asset' => false,
            'file_name' => $emptyLabel,
        ];
    }

    return [
        'path' => $normalizedPath,
        'url' => $assetUrl,
        'absolute_path' => $assetAbsolutePath,
        'has_asset' => true,
        'file_name' => basename($normalizedPath),
    ];
}

function emarioh_admin_settings_nested_upload(array $uploads, string $slotKey): ?array
{
    if (!isset($uploads['name']) || !is_array($uploads['name'])) {
        return null;
    }

    return [
        'name' => (string) ($uploads['name'][$slotKey] ?? ''),
        'type' => (string) ($uploads['type'][$slotKey] ?? ''),
        'tmp_name' => (string) ($uploads['tmp_name'][$slotKey] ?? ''),
        'error' => (int) ($uploads['error'][$slotKey] ?? UPLOAD_ERR_NO_FILE),
        'size' => (int) ($uploads['size'][$slotKey] ?? 0),
    ];
}

function emarioh_admin_settings_gallery_title(string $caption, string $fileName): string
{
    $caption = trim($caption);

    if ($caption !== '') {
        return $caption;
    }

    $fileStem = trim((string) pathinfo($fileName, PATHINFO_FILENAME));
    $fileStem = trim(preg_replace('/[_-]+/', ' ', $fileStem) ?? $fileStem);
    $fileStem = trim(preg_replace('/\s+/', ' ', $fileStem) ?? $fileStem);

    return $fileStem !== '' ? ucwords($fileStem) : 'Gallery image';
}

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST'
    && (string) ($_POST['settings_action'] ?? '') === 'save_admin_account'
) {
    $adminAccountNoticeClass = 'danger';
    $submittedFullName = emarioh_normalize_name((string) ($_POST['admin_full_name'] ?? ''));
    $submittedMobileInput = trim((string) ($_POST['admin_mobile'] ?? ''));
    $submittedMobile = emarioh_normalize_mobile($submittedMobileInput);
    $currentPassword = (string) ($_POST['admin_current_password'] ?? '');
    $newPassword = (string) ($_POST['admin_new_password'] ?? '');
    $confirmPassword = (string) ($_POST['admin_confirm_password'] ?? '');
    $currentUserId = (int) ($currentUser['id'] ?? 0);
    $currentUserName = emarioh_normalize_name((string) ($currentUser['full_name'] ?? ''));
    $currentUserMobile = (string) ($currentUser['mobile'] ?? '');
    $mobileChanged = $submittedMobile !== $currentUserMobile;
    $passwordChangeRequested = $currentPassword !== '' || $newPassword !== '' || $confirmPassword !== '';

    $adminAccountForm = [
        'full_name' => $submittedFullName,
        'mobile' => $submittedMobileInput,
    ];

    if ($submittedFullName === '') {
        $adminAccountNoticeMessage = 'Enter the admin name before saving changes.';
    } elseif ($submittedMobileInput === '') {
        $adminAccountNoticeMessage = 'Enter the admin mobile number before saving changes.';
    } elseif (!emarioh_is_valid_mobile($submittedMobile)) {
        $adminAccountNoticeMessage = 'Enter a valid Philippine mobile number.';
    } elseif ($mobileChanged) {
        $existingUser = emarioh_find_user_by_mobile($db, $submittedMobile);

        if ($existingUser !== null && (int) ($existingUser['id'] ?? 0) !== $currentUserId) {
            $adminAccountNoticeMessage = 'That mobile number is already linked to another account.';
        }
    }

    if ($adminAccountNoticeMessage === '' && $passwordChangeRequested) {
        if ($currentPassword === '') {
            $adminAccountNoticeMessage = 'Enter the current password before saving a password change.';
        } elseif (!password_verify($currentPassword, (string) ($currentUser['password_hash'] ?? ''))) {
            $adminAccountNoticeMessage = 'The current password is incorrect.';
        } elseif ($newPassword === '') {
            $adminAccountNoticeMessage = 'Enter a new password before saving.';
        } elseif (strlen($newPassword) < 8) {
            $adminAccountNoticeMessage = 'Use at least 8 characters for the new password.';
        } elseif ($newPassword !== $confirmPassword) {
            $adminAccountNoticeMessage = 'New password and confirmation do not match.';
        }
    }

    if ($adminAccountNoticeMessage === '') {
        $nameChanged = $submittedFullName !== $currentUserName;
        $hasChanges = $nameChanged || $mobileChanged || $passwordChangeRequested;

        if (!$hasChanges) {
            $adminAccountNoticeClass = 'warning';
            $adminAccountNoticeMessage = 'No admin account changes were detected.';
        } else {
            if ($mobileChanged && !emarioh_has_verified_otp($submittedMobile, $adminMobileOtpPurpose)) {
                $adminAccountNoticeMessage = 'Verify the OTP sent to the new mobile number before saving the admin account.';
            }
        }
    }

    if ($adminAccountNoticeMessage !== '') {
        $_SESSION[$adminAccountFlashSessionKey] = [
            'message' => $adminAccountNoticeMessage,
            'class' => $adminAccountNoticeClass,
            'form' => $adminAccountForm,
        ];

        emarioh_redirect($settingsSectionUrl('admin-account'));
    }

    if ($adminAccountNoticeMessage === '') {
        $updateSql = 'UPDATE users SET full_name = :full_name, mobile = :mobile, updated_at = :updated_at';
        $updateParams = [
            ':full_name' => $submittedFullName,
            ':mobile' => $submittedMobile,
            ':updated_at' => time(),
            ':id' => $currentUserId,
        ];

        if ($passwordChangeRequested) {
            $updateSql .= ', password_hash = :password_hash';
            $updateParams[':password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $updateSql .= ' WHERE id = :id';

        try {
            $db->beginTransaction();
            $db->prepare($updateSql)->execute($updateParams);

            if ($mobileChanged) {
                $currentPublicPrimaryMobile = trim((string) ($publicSiteSettings['primary_mobile'] ?? ''));
                $shouldSyncPublicPrimaryMobile = $currentPublicPrimaryMobile === ''
                    || emarioh_normalize_mobile($currentPublicPrimaryMobile) === $currentUserMobile;

                if ($shouldSyncPublicPrimaryMobile) {
                    emarioh_save_public_site_settings($db, [
                        'primary_mobile' => emarioh_format_mobile($submittedMobile),
                    ]);
                }

                emarioh_delete_otp($db, $submittedMobile, $adminMobileOtpPurpose);
                emarioh_clear_verified_otp($submittedMobile, $adminMobileOtpPurpose);
            }

            if ($passwordChangeRequested) {
                emarioh_revoke_all_remember_tokens($db, $currentUserId);
            }

            $db->commit();

            if ($passwordChangeRequested) {
                emarioh_redirect($settingsSectionUrl('admin-account', ['admin_account' => 'password_updated']));
            }

            emarioh_redirect($settingsSectionUrl('admin-account'));
        } catch (Throwable $throwable) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $adminAccountNoticeMessage = 'Admin account could not be saved right now. Please try again.';

            if (emarioh_is_development_mode()) {
                $adminAccountNoticeMessage .= ' Details: ' . $throwable->getMessage();
            }

            $_SESSION[$adminAccountFlashSessionKey] = [
                'message' => $adminAccountNoticeMessage,
                'class' => $adminAccountNoticeClass,
                'form' => $adminAccountForm,
            ];

            emarioh_redirect($settingsSectionUrl('admin-account'));
        }
    }
}

$adminAccountDisplayMobile = emarioh_normalize_mobile((string) ($adminAccountForm['mobile'] ?? ''));
$adminAccountHasVerifiedMobile = $adminAccountDisplayMobile !== ''
    && $adminAccountDisplayMobile !== (string) ($currentUser['mobile'] ?? '')
    && emarioh_has_verified_otp($adminAccountDisplayMobile, $adminMobileOtpPurpose);
$adminAccountNoteMessage = 'Review your changes before saving.';
$adminAccountNoteState = '';
$adminAccountNoteIcon = 'bi-info-circle';

if ($adminAccountNoticeMessage !== '') {
    $adminAccountNoteMessage = $adminAccountNoticeMessage;
    $adminAccountNoteState = $adminAccountNoticeClass === 'warning' ? 'warning' : 'danger';
    $adminAccountNoteIcon = $adminAccountNoticeClass === 'warning' ? 'bi-info-circle' : 'bi-exclamation-circle';
} elseif ($adminAccountFlashMessage !== '') {
    $adminAccountNoteMessage = $adminAccountFlashMessage;
    $adminAccountNoteState = $adminAccountFlashState;
    $adminAccountNoteIcon = $adminAccountFlashIcon;
}

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST'
    && (string) ($_POST['settings_action'] ?? '') === 'save_hero_image'
) {
    $heroNoticeClass = 'danger';
    $heroUpload = $_FILES['hero_image'] ?? null;
    $allowedHeroImageTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $maxHeroImageBytes = 5 * 1024 * 1024;

    if (!is_array($heroUpload)) {
        $heroNoticeMessage = 'Choose an image before saving.';
    } else {
        $uploadError = (int) ($heroUpload['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($uploadError === UPLOAD_ERR_NO_FILE) {
            $heroNoticeMessage = 'Choose an image before saving.';
        } elseif ($uploadError !== UPLOAD_ERR_OK) {
            $heroNoticeMessage = match ($uploadError) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The selected image is too large.',
                default => 'The selected image could not be uploaded. Please try again.',
            };
        } elseif ((int) ($heroUpload['size'] ?? 0) > $maxHeroImageBytes) {
            $heroNoticeMessage = 'The selected image is too large. Use an image up to 5 MB only.';
        } else {
            $temporaryFilePath = (string) ($heroUpload['tmp_name'] ?? '');

            if ($temporaryFilePath === '' || !is_uploaded_file($temporaryFilePath)) {
                $heroNoticeMessage = 'The uploaded image could not be validated. Please try again.';
            } else {
                $fileInfo = new finfo(FILEINFO_MIME_TYPE);
                $detectedMimeType = strtolower((string) $fileInfo->file($temporaryFilePath));
                $detectedExtension = $allowedHeroImageTypes[$detectedMimeType] ?? null;

                if ($detectedExtension === null || @getimagesize($temporaryFilePath) === false) {
                    $heroNoticeMessage = 'Use a valid JPG, PNG, WEBP, or GIF image.';
                } else {
                    $heroUploadDirectoryRelative = emarioh_public_upload_relative_directory('hero');
                    $previousHeroImagePath = emarioh_normalize_public_asset_path(
                        (string) ($publicSiteSettings['hero_image_path'] ?? ''),
                        ''
                    );
                    $previousHeroImageAbsolute = emarioh_public_asset_matches_upload_bucket($previousHeroImagePath, 'hero')
                        ? emarioh_public_asset_absolute_path($previousHeroImagePath)
                        : null;
                    try {
                        $nextHeroImageFileName = 'hero-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $detectedExtension;
                        $nextHeroImagePath = $heroUploadDirectoryRelative . '/' . $nextHeroImageFileName;
                        $heroUploadDirectoryAbsolute = emarioh_ensure_public_upload_directory('hero');
                        $nextHeroImageAbsolute = $heroUploadDirectoryAbsolute . DIRECTORY_SEPARATOR . $nextHeroImageFileName;

                        if (!move_uploaded_file($temporaryFilePath, $nextHeroImageAbsolute)) {
                            throw new RuntimeException('The uploaded file could not be moved.');
                        }

                        emarioh_save_public_site_settings($db, [
                            'hero_image_path' => $nextHeroImagePath,
                            'hero_image_alt' => (string) ($publicSiteSettings['hero_image_alt'] ?? 'Emarioh Catering Services hero image'),
                        ]);

                        if ($previousHeroImageAbsolute !== null
                            && $previousHeroImageAbsolute !== $nextHeroImageAbsolute
                            && is_file($previousHeroImageAbsolute)
                        ) {
                            @unlink($previousHeroImageAbsolute);
                        }

                        emarioh_redirect($settingsSectionUrl('manage-public-page'));
                    } catch (Throwable $throwable) {
                        if (isset($nextHeroImageAbsolute) && is_file($nextHeroImageAbsolute)) {
                            @unlink($nextHeroImageAbsolute);
                        }

                        $heroNoticeMessage = 'Hero image could not be saved right now. Please try again.';

                        if (emarioh_is_development_mode()) {
                            $heroNoticeMessage .= ' Details: ' . $throwable->getMessage();
                        }
                    }
                }
            }
        }
    }
}

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST'
    && (string) ($_POST['settings_action'] ?? '') === 'save_service_cards'
) {
    $serviceCardDefaults = emarioh_default_public_service_cards();
    $allowedServiceImageTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $serviceImageUploads = $_FILES['service_images'] ?? [];
    $maxServiceImageBytes = 5 * 1024 * 1024;
    $serviceUploadDirectoryRelative = emarioh_public_upload_relative_directory('services');
    $pendingServiceChanges = [];
    $newServiceImageAbsolutePaths = [];
    $previousServiceImageAbsolutePaths = [];
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);

    foreach ($serviceCardDefaults as $slotKey => $defaultCard) {
        $submittedCard = $_POST['service_cards'][$slotKey] ?? [];
        $submittedCard = is_array($submittedCard) ? $submittedCard : [];
        $serviceTitle = trim((string) ($submittedCard['title'] ?? ($publicServiceCards[$slotKey]['title'] ?? $defaultCard['title'])));
        $serviceDescription = trim((string) ($submittedCard['description'] ?? ($publicServiceCards[$slotKey]['description'] ?? $defaultCard['description'])));

        $serviceCardsForm[$slotKey]['title'] = $serviceTitle;
        $serviceCardsForm[$slotKey]['description'] = $serviceDescription;

        if ($serviceTitle === '') {
            $servicesNoticeMessage = sprintf('Enter a title for %s before saving.', str_replace('_', ' ', ucfirst($slotKey)));
            break;
        }

        if ($serviceDescription === '') {
            $servicesNoticeMessage = sprintf('Enter a description for %s before saving.', str_replace('_', ' ', ucfirst($slotKey)));
            break;
        }

        $pendingServiceChanges[$slotKey] = [
            'title' => $serviceTitle,
            'description' => $serviceDescription,
            'image_alt' => $serviceTitle . ' service image',
        ];

        $serviceUpload = emarioh_admin_settings_nested_upload(is_array($serviceImageUploads) ? $serviceImageUploads : [], $slotKey);
        $uploadError = (int) ($serviceUpload['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($uploadError === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $serviceNumber = (int) ($defaultCard['sort_order'] ?? 0);
        $serviceLabel = $serviceNumber > 0 ? 'Service ' . $serviceNumber : str_replace('_', ' ', ucfirst($slotKey));

        if ($uploadError !== UPLOAD_ERR_OK) {
            $servicesNoticeMessage = match ($uploadError) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => $serviceLabel . ' image is too large.',
                default => $serviceLabel . ' image could not be uploaded. Please try again.',
            };
            break;
        }

        if ((int) ($serviceUpload['size'] ?? 0) > $maxServiceImageBytes) {
            $servicesNoticeMessage = $serviceLabel . ' image is too large. Use an image up to 5 MB only.';
            break;
        }

        $temporaryFilePath = (string) ($serviceUpload['tmp_name'] ?? '');

        if ($temporaryFilePath === '' || !is_uploaded_file($temporaryFilePath)) {
            $servicesNoticeMessage = $serviceLabel . ' image could not be validated. Please try again.';
            break;
        }

        $detectedMimeType = strtolower((string) $fileInfo->file($temporaryFilePath));
        $detectedExtension = $allowedServiceImageTypes[$detectedMimeType] ?? null;

        if ($detectedExtension === null || @getimagesize($temporaryFilePath) === false) {
            $servicesNoticeMessage = 'Use a valid JPG, PNG, WEBP, or GIF image for ' . $serviceLabel . '.';
            break;
        }

        try {
            $serviceUploadDirectoryAbsolute = emarioh_ensure_public_upload_directory('services');
            $nextServiceImageFileName = $slotKey . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $detectedExtension;
            $nextServiceImagePath = $serviceUploadDirectoryRelative . '/' . $nextServiceImageFileName;
            $nextServiceImageAbsolute = $serviceUploadDirectoryAbsolute . DIRECTORY_SEPARATOR . $nextServiceImageFileName;

            if (!move_uploaded_file($temporaryFilePath, $nextServiceImageAbsolute)) {
                throw new RuntimeException('The uploaded service image could not be moved.');
            }

            $pendingServiceChanges[$slotKey]['image_path'] = $nextServiceImagePath;
            $newServiceImageAbsolutePaths[] = $nextServiceImageAbsolute;

            $previousServiceImagePath = emarioh_normalize_public_asset_path(
                (string) ($publicServiceCards[$slotKey]['image_path'] ?? ''),
                ''
            );

            if (emarioh_public_asset_matches_upload_bucket($previousServiceImagePath, 'services')) {
                $previousServiceImageAbsolute = emarioh_public_asset_absolute_path($previousServiceImagePath);

                if ($previousServiceImageAbsolute !== null) {
                    $previousServiceImageAbsolutePaths[$previousServiceImageAbsolute] = $previousServiceImageAbsolute;
                }
            }
        } catch (Throwable $throwable) {
            $servicesNoticeMessage = 'Service images could not be saved right now. Please try again.';

            if (emarioh_is_development_mode()) {
                $servicesNoticeMessage .= ' Details: ' . $throwable->getMessage();
            }

            break;
        }
    }

    if ($servicesNoticeMessage !== '') {
        foreach ($newServiceImageAbsolutePaths as $uploadedImageAbsolutePath) {
            if (is_file($uploadedImageAbsolutePath)) {
                @unlink($uploadedImageAbsolutePath);
            }
        }
    } else {
        try {
            emarioh_save_public_service_cards($db, $pendingServiceChanges);

            foreach ($previousServiceImageAbsolutePaths as $previousServiceImageAbsolutePath) {
                if (is_file($previousServiceImageAbsolutePath)) {
                    @unlink($previousServiceImageAbsolutePath);
                }
            }

            emarioh_redirect($settingsSectionUrl('manage-public-page'));
        } catch (Throwable $throwable) {
            foreach ($newServiceImageAbsolutePaths as $uploadedImageAbsolutePath) {
                if (is_file($uploadedImageAbsolutePath)) {
                    @unlink($uploadedImageAbsolutePath);
                }
            }

            $servicesNoticeMessage = 'Service cards could not be saved right now. Please try again.';

            if (emarioh_is_development_mode()) {
                $servicesNoticeMessage .= ' Details: ' . $throwable->getMessage();
            }
        }
    }
}

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST'
    && (string) ($_POST['settings_action'] ?? '') === 'save_gallery_image'
) {
    $galleryNoticeClass = 'danger';
    $galleryForm['category'] = emarioh_normalize_gallery_category((string) ($_POST['gallery_category'] ?? 'wedding'), 'wedding');
    $galleryForm['caption'] = trim((string) ($_POST['gallery_caption'] ?? ''));
    $galleryUpload = $_FILES['gallery_image'] ?? null;
    $allowedGalleryImageTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $maxGalleryImageBytes = 5 * 1024 * 1024;
    $galleryUploadDirectoryRelative = emarioh_public_upload_relative_directory('gallery');

    if (!is_array($galleryUpload)) {
        $galleryNoticeMessage = 'Choose a gallery image before saving.';
    } else {
        $uploadError = (int) ($galleryUpload['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($uploadError === UPLOAD_ERR_NO_FILE) {
            $galleryNoticeMessage = 'Choose a gallery image before saving.';
        } elseif ($uploadError !== UPLOAD_ERR_OK) {
            $galleryNoticeMessage = match ($uploadError) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The selected gallery image is too large.',
                default => 'The selected gallery image could not be uploaded. Please try again.',
            };
        } elseif ((int) ($galleryUpload['size'] ?? 0) > $maxGalleryImageBytes) {
            $galleryNoticeMessage = 'The selected gallery image is too large. Use an image up to 5 MB only.';
        } else {
            $temporaryFilePath = (string) ($galleryUpload['tmp_name'] ?? '');

            if ($temporaryFilePath === '' || !is_uploaded_file($temporaryFilePath)) {
                $galleryNoticeMessage = 'The uploaded gallery image could not be validated. Please try again.';
            } else {
                $fileInfo = new finfo(FILEINFO_MIME_TYPE);
                $detectedMimeType = strtolower((string) $fileInfo->file($temporaryFilePath));
                $detectedExtension = $allowedGalleryImageTypes[$detectedMimeType] ?? null;

                if ($detectedExtension === null || @getimagesize($temporaryFilePath) === false) {
                    $galleryNoticeMessage = 'Use a valid JPG, PNG, WEBP, or GIF image for the gallery.';
                } else {
                    try {
                        $galleryUploadDirectoryAbsolute = emarioh_ensure_public_upload_directory('gallery');
                        $nextGalleryFileName = 'gallery-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $detectedExtension;
                        $nextGalleryImagePath = $galleryUploadDirectoryRelative . '/' . $nextGalleryFileName;
                        $nextGalleryImageAbsolute = $galleryUploadDirectoryAbsolute . DIRECTORY_SEPARATOR . $nextGalleryFileName;

                        if (!move_uploaded_file($temporaryFilePath, $nextGalleryImageAbsolute)) {
                            throw new RuntimeException('The uploaded gallery image could not be moved.');
                        }

                        $originalFileName = trim(basename((string) ($galleryUpload['name'] ?? '')));

                        if ($originalFileName === '') {
                            $originalFileName = $nextGalleryFileName;
                        }

                        $galleryTitle = emarioh_admin_settings_gallery_title($galleryForm['caption'], $originalFileName);

                        emarioh_save_gallery_item($db, [
                            'title' => $galleryTitle,
                            'category' => $galleryForm['category'],
                            'file_name' => $originalFileName,
                            'image_path' => $nextGalleryImagePath,
                            'image_alt' => $galleryTitle . ' gallery image',
                            'placement_label' => $galleryCategoryOptions[$galleryForm['category']] ?? 'Gallery',
                        ]);

                        emarioh_redirect($settingsSectionUrl('manage-public-page'));
                    } catch (Throwable $throwable) {
                        if (isset($nextGalleryImageAbsolute) && is_file($nextGalleryImageAbsolute)) {
                            @unlink($nextGalleryImageAbsolute);
                        }

                        $galleryNoticeMessage = 'Gallery image could not be saved right now. Please try again.';

                        if (emarioh_is_development_mode()) {
                            $galleryNoticeMessage .= ' Details: ' . $throwable->getMessage();
                        }
                    }
                }
            }
        }
    }
}

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST'
    && (string) ($_POST['settings_action'] ?? '') === 'delete_gallery_image'
) {
    $galleryNoticeClass = 'danger';
    $galleryItemId = filter_input(INPUT_POST, 'gallery_item_id', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    if ($galleryItemId === false || $galleryItemId === null) {
        $galleryNoticeMessage = 'Choose a valid gallery image before deleting.';
    } else {
        try {
            $deletedGalleryItem = emarioh_delete_gallery_item($db, (int) $galleryItemId);

            if ($deletedGalleryItem === null) {
                $galleryNoticeMessage = 'That gallery image could not be found anymore.';
            } else {
                $deletedGalleryImagePath = emarioh_normalize_public_asset_path((string) ($deletedGalleryItem['image_path'] ?? ''), '');

                if (emarioh_public_asset_matches_upload_bucket($deletedGalleryImagePath, 'gallery')) {
                    $deletedGalleryImageAbsolute = emarioh_public_asset_absolute_path($deletedGalleryImagePath);

                    if ($deletedGalleryImageAbsolute !== null && is_file($deletedGalleryImageAbsolute)) {
                        @unlink($deletedGalleryImageAbsolute);
                    }
                }

                emarioh_redirect($settingsSectionUrl('manage-public-page'));
            }
        } catch (Throwable $throwable) {
            $galleryNoticeMessage = 'Gallery image could not be deleted right now. Please try again.';

            if (emarioh_is_development_mode()) {
                $galleryNoticeMessage .= ' Details: ' . $throwable->getMessage();
            }
        }
    }
}

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST'
    && (string) ($_POST['settings_action'] ?? '') === 'save_contact_details'
) {
    $contactsNoticeClass = 'danger';
    $contactDetailsForm = [
        'service_area' => trim((string) ($_POST['contact_service_area'] ?? '')),
        'public_email' => trim((string) ($_POST['contact_public_email'] ?? '')),
        'primary_mobile' => trim((string) ($_POST['contact_primary_mobile'] ?? '')),
        'business_hours' => trim((string) ($_POST['contact_business_hours'] ?? '')),
    ];

    if ($contactDetailsForm['service_area'] === '') {
        $contactsNoticeMessage = 'Enter the service area before saving contacts.';
    } elseif ($contactDetailsForm['public_email'] === '') {
        $contactsNoticeMessage = 'Enter the public email before saving contacts.';
    } elseif (filter_var($contactDetailsForm['public_email'], FILTER_VALIDATE_EMAIL) === false) {
        $contactsNoticeMessage = 'Enter a valid public email address.';
    } elseif ($contactDetailsForm['primary_mobile'] === '') {
        $contactsNoticeMessage = 'Enter the mobile number before saving contacts.';
    } elseif ($contactDetailsForm['business_hours'] === '') {
        $contactsNoticeMessage = 'Enter the business hours before saving contacts.';
    } else {
        try {
            emarioh_save_public_site_settings($db, [
                'service_area' => $contactDetailsForm['service_area'],
                'public_email' => $contactDetailsForm['public_email'],
                'primary_mobile' => $contactDetailsForm['primary_mobile'],
                'business_hours' => $contactDetailsForm['business_hours'],
            ]);

            emarioh_redirect($settingsSectionUrl('manage-public-page'));
        } catch (Throwable $throwable) {
            $contactsNoticeMessage = 'Contact details could not be saved right now. Please try again.';

            if (emarioh_is_development_mode()) {
                $contactsNoticeMessage .= ' Details: ' . $throwable->getMessage();
            }
        }
    }
}

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST'
    && (string) ($_POST['settings_action'] ?? '') === 'save_sms_templates'
) {
    $smsTemplatesNoticeClass = 'danger';
    $submittedSmsTemplates = is_array($_POST['sms_templates'] ?? null)
        ? $_POST['sms_templates']
        : [];

    foreach ($smsTemplatesForm as $templateKey => $template) {
        $templateBody = trim((string) ($submittedSmsTemplates[$templateKey] ?? ''));
        $smsTemplatesForm[$templateKey]['template_body'] = $templateBody;

        if ($templateBody === '' && $smsTemplatesNoticeMessage === '') {
            $templateName = trim((string) ($template['template_name'] ?? 'SMS template')) ?: 'SMS template';
            $smsTemplatesNoticeMessage = sprintf('Enter the %s message before saving.', $templateName);
        }
    }

    if ($smsTemplatesNoticeMessage === '') {
        try {
            $smsTemplatesForm = emarioh_save_sms_templates(
                $db,
                array_map(
                    static fn (array $template): string => trim((string) ($template['template_body'] ?? '')),
                    $smsTemplatesForm
                )
            );

            emarioh_redirect($settingsSectionUrl('sms-templates'));
        } catch (Throwable $throwable) {
            $smsTemplatesNoticeMessage = 'SMS templates could not be saved right now. Please try again.';

            if (emarioh_is_development_mode()) {
                $smsTemplatesNoticeMessage .= ' Details: ' . $throwable->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emarioh Catering Services <?= $escape($settingsPageTitle) ?></title>
    <?= emarioh_render_vendor_head_assets(); ?>
    <link rel="stylesheet" href="assets/css/index.css?v=20260418o">
    <link rel="stylesheet" href="assets/css/package-admin.css">
    <link rel="stylesheet" href="assets/css/pages/admin-settings.css?v=20260418w">
</head>
<body class="admin-dashboard-page admin-settings-page<?= $settingsPageIsDetail ? ' admin-settings-detail-page' : '' ?>" data-auth-guard="admin" data-mobile-settings-view="<?= $settingsPageIsDetail ? 'detail' : 'hub' ?>"<?= $settingsPageIsDetail ? ' data-active-settings-section="' . $escape($settingsPageSection) . '"' : '' ?>>
    <div class="dashboard-shell container-fluid">
        <div class="dashboard-frame">
            <aside class="dashboard-sidebar offcanvas-xl offcanvas-start border-0" tabindex="-1" id="dashboardSidebar" aria-labelledby="dashboardSidebarLabel">
                <div class="offcanvas-header sidebar-mobile-header d-xl-none">
                    <div class="sidebar-mobile-brand">
                        <span class="sidebar-brand__frame sidebar-brand__frame--small">
                            <img src="assets/images/logo.jpg" alt="Emarioh Catering Services" class="sidebar-brand__logo">
                        </span>
                        <div class="sidebar-brand__copy">
                            <span class="sidebar-brand__name">Emarioh</span>
                            <span class="sidebar-brand__sub">Catering Services</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>

                <div class="offcanvas-body p-0">
                    <div class="sidebar-inner d-flex flex-column h-100">
                        <div class="sidebar-brand">
                            <a href="index.php" class="sidebar-brand__link text-decoration-none" id="dashboardSidebarLabel" aria-label="Emarioh Catering Services Admin Dashboard">
                                <span class="sidebar-brand__frame">
                                    <img src="assets/images/logo.jpg" alt="Emarioh Catering Services" class="sidebar-brand__logo">
                                </span>
                                <span class="sidebar-brand__copy">
                                    <span class="sidebar-brand__name">Emarioh</span>
                                    <span class="sidebar-brand__sub">Catering Services</span>
                                </span>
                            </a>
                        </div>

                        <div class="sidebar-divider" aria-hidden="true"></div>

                        <nav class="dashboard-nav nav flex-column" aria-label="Admin navigation">
                            <a class="nav-link" href="index.php"><span class="nav-link__icon"><i class="bi bi-grid-1x2-fill"></i></span><span>Dashboard</span></a>
                            <a class="nav-link" href="admin-bookings.php"><span class="nav-link__icon"><i class="bi bi-journal-check"></i></span><span>Booking Management</span></a>
                            <a class="nav-link" href="admin-clients.php"><span class="nav-link__icon"><i class="bi bi-people"></i></span><span>Clients</span></a>
                            <a class="nav-link" href="admin-events.php"><span class="nav-link__icon"><i class="bi bi-calendar-event"></i></span><span>Event Schedule</span></a>
                            <a class="nav-link" href="admin-payments.php"><span class="nav-link__icon"><i class="bi bi-wallet2"></i></span><span>Payment</span></a>
                            <a class="nav-link" href="admin-inquiries.php"><span class="nav-link__icon"><i class="bi bi-envelope-paper"></i></span><span>Website Inquiries</span></a>
                            <a class="nav-link active" href="<?= $escape($settingsNavHref) ?>" aria-current="page"><span class="nav-link__icon"><i class="bi bi-gear"></i></span><span>Settings</span></a>
                        </nav>

                        <div class="sidebar-footer mt-auto">
                            <a class="sidebar-logout" href="logout.php">
                                <span class="sidebar-logout__icon">
                                    <i class="bi bi-box-arrow-right"></i>
                                </span>
                                <span class="sidebar-logout__label">Log out</span>
                            </a>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="dashboard-main">
                <header class="dashboard-topbar">
                    <div class="topbar-leading">
                        <button class="btn mobile-menu-button d-xl-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#dashboardSidebar" aria-controls="dashboardSidebar" aria-label="Open navigation">
                            <i class="bi bi-list"></i>
                        </button>

                        <div class="topbar-copy">
                            <p class="settings-mobile-topbar__eyebrow d-xl-none"><?= $escape($settingsPageEyebrow) ?></p>
                            <h1 class="topbar-copy__title">
                                <span class="d-none d-xl-inline"><?= $escape($settingsPageTitle) ?></span>
                                <span class="d-xl-none"><?= $escape($settingsPageTitle) ?></span>
                            </h1>
                            <p class="settings-mobile-topbar__text d-xl-none"><?= $escape($settingsPageDescription) ?></p>
                        </div>
                    </div>
</header>

                <main class="dashboard-content settings-dashboard-content">
<?php if (!$settingsPageIsDetail): ?>
<section class="surface-card settings-profile-hub d-xl-none" data-settings-hub aria-labelledby="settingsProfileHubTitle">
    <div class="settings-profile-hub__header">
        <span class="settings-profile-hub__icon" aria-hidden="true"><i class="bi bi-person-circle"></i></span>
        <div class="settings-profile-hub__copy">
            <p class="settings-profile-hub__eyebrow">Administrator</p>
            <h2 id="settingsProfileHubTitle"><?= $escape(trim((string) ($adminAccountForm['full_name'] ?? '')) ?: 'Admin Profile') ?></h2>
            <p class="settings-profile-hub__summary">Review account details, client updates, and admin tools from one place.</p>
        </div>
    </div>
    <div class="settings-profile-hub__grid">
        <a class="settings-profile-shortcut" href="admin-clients.php">
            <span class="settings-profile-shortcut__content">
                <span class="settings-profile-shortcut__icon" aria-hidden="true"><i class="bi bi-people"></i></span>
                <span class="settings-profile-shortcut__copy">
                    <strong>Clients</strong>
                    <span>View client records and bookings.</span>
                </span>
            </span>
            <span class="settings-profile-shortcut__chevron" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
        </a>
        <a class="settings-profile-shortcut" href="admin-inquiries.php">
            <span class="settings-profile-shortcut__content">
                <span class="settings-profile-shortcut__icon" aria-hidden="true"><i class="bi bi-envelope-paper"></i></span>
                <span class="settings-profile-shortcut__copy">
                    <strong>Inbox</strong>
                    <span>Check inquiries and follow-ups.</span>
                </span>
            </span>
            <span class="settings-profile-shortcut__chevron" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
        </a>
        <a class="settings-profile-shortcut" href="admin-settings-menu.php">
            <span class="settings-profile-shortcut__content">
                <span class="settings-profile-shortcut__icon" aria-hidden="true"><i class="bi bi-gear"></i></span>
                <span class="settings-profile-shortcut__copy">
                    <strong>Settings</strong>
                    <span>Manage account, payments, and SMS.</span>
                </span>
            </span>
            <span class="settings-profile-shortcut__chevron" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
        </a>
    </div>
    <a class="settings-profile-logout" href="logout.php">
        <span class="settings-profile-logout__content">
            <span class="settings-profile-shortcut__icon" aria-hidden="true"><i class="bi bi-box-arrow-right"></i></span>
            <span class="settings-profile-shortcut__copy">
                <strong>Log Out</strong>
                <span>Sign out of the admin account.</span>
            </span>
        </span>
        <span class="settings-profile-shortcut__chevron" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
    </a>
</section>
<?php endif; ?>
<section class="surface-card settings-panel" data-settings-panel data-mobile-state="<?= $settingsPageIsDetail ? 'expanded' : 'collapsed' ?>" aria-hidden="<?= $settingsPageIsDetail ? 'false' : 'true' ?>">
    <?php if ($settingsPageIsDetail && $settingsBackHref !== ''): ?>
    <div class="settings-panel__mobile-actions<?= $settingsPageIsDetail ? '' : ' d-xl-none' ?>">
        <a class="settings-panel__back" href="<?= $escape($settingsBackHref) ?>">
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
            <span><?= $escape($settingsBackLabel) ?></span>
        </a>
    </div>
    <?php endif; ?>

    <?php if (!$settingsPageIsDetail): ?>
    <nav class="settings-tabs settings-filter-bar" aria-label="Settings quick filters">
        <button class="settings-tab is-active" type="button" data-settings-filter="admin-account">Admin Account</button>
        <button class="settings-tab" type="button" data-settings-filter="manage-public-page">Manage Public Page</button>
        <button class="settings-tab" type="button" data-settings-filter="payment-settings">Payment</button>
        <button class="settings-tab" type="button" data-settings-filter="sms-templates">SMS Templates</button>
    </nav>
    <?php endif; ?>
        <section class="settings-section" id="admin-account" data-settings-section="admin-account"<?= $settingsSectionHiddenAttr('admin-account') ?>>
            <div class="settings-section__intro">
                <div>
                    <h2>Admin Account</h2>
                </div>
            </div>

            <div class="settings-account-shell">
                <article class="settings-block settings-block--account">
                    <form method="post" id="adminAccountForm" class="settings-account-form">
                        <input type="hidden" name="settings_action" value="save_admin_account">

                        <div class="settings-account-layout">
                            <section class="settings-account-section" aria-labelledby="settingsProfileTitle">
                                <div class="settings-account-section__header">
                                    <span class="settings-account-section__icon" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
                                    <div class="settings-account-section__copy">
                                        <h4 id="settingsProfileTitle">Profile Details</h4>
                                        <p>Basic admin info used across the system.</p>
                                    </div>
                                </div>
                                <div class="settings-field-grid settings-field-grid--account">
                                    <label class="settings-field">
                                        <span class="settings-field__label">Admin Name</span>
                                        <input
                                            class="settings-input"
                                            type="text"
                                            name="admin_full_name"
                                            value="<?= $escape($adminAccountForm['full_name']) ?>"
                                            data-original-value="<?= $escape(trim((string) ($currentUser['full_name'] ?? ''))) ?>"
                                            maxlength="150"
                                            autocomplete="name"
                                            required
                                        >
                                        <span class="settings-field__hint">Used in admin records.</span>
                                    </label>
                                    <div class="settings-field">
                                        <span class="settings-field__label">Mobile Number</span>
                                        <input
                                            class="settings-input"
                                            type="tel"
                                            id="adminMobileInput"
                                            name="admin_mobile"
                                            value="<?= $escape($adminAccountForm['mobile']) ?>"
                                            maxlength="20"
                                            inputmode="numeric"
                                            autocomplete="tel"
                                            data-original-mobile="<?= $escape($adminAccountCurrentMobileRaw) ?>"
                                            data-mobile-verified="<?= $adminAccountHasVerifiedMobile ? '1' : '0' ?>"
                                            readonly
                                            required
                                        >
                                        <div class="settings-mobile-field-footer">
                                            <span class="settings-field__hint">Used for sign-in OTP.</span>
                                            <button class="settings-mobile-change-btn" type="button" id="adminMobileChangeButton">Change Mobile Number</button>
                                        </div>
                                        <p class="settings-mobile-status-note is-hidden" id="adminMobileStatusNote"></p>
                                    </div>
                                </div>
                            </section>

                            <div class="settings-account-divider" aria-hidden="true"></div>

                            <section class="settings-account-section" aria-labelledby="settingsSecurityTitle">
                                <div class="settings-account-section__header">
                                    <span class="settings-account-section__icon" aria-hidden="true"><i class="bi bi-shield-lock"></i></span>
                                    <div class="settings-account-section__copy">
                                        <h4 id="settingsSecurityTitle">Password &amp; Security</h4>
                                        <p>Change the admin password when needed.</p>
                                    </div>
                                </div>
                                <div class="settings-field-grid settings-field-grid--account settings-field-grid--security">
                                    <label class="settings-field settings-field--full">
                                        <span class="settings-field__label">Current Password</span>
                                        <input class="settings-input" type="password" name="admin_current_password" placeholder="Enter current password" autocomplete="current-password">
                                        <span class="settings-field__hint">Required for password changes.</span>
                                    </label>
                                    <label class="settings-field">
                                        <span class="settings-field__label">New Password</span>
                                        <input class="settings-input" type="password" name="admin_new_password" placeholder="Enter new password" autocomplete="new-password">
                                        <span class="settings-field__hint">At least 8 characters.</span>
                                    </label>
                                    <label class="settings-field">
                                        <span class="settings-field__label">Confirm Password</span>
                                        <input class="settings-input" type="password" name="admin_confirm_password" placeholder="Confirm new password" autocomplete="new-password">
                                        <span class="settings-field__hint">Must match the new password.</span>
                                    </label>
                                </div>
                            </section>
                        </div>

                        <div class="settings-account-actions">
                            <p
                                class="settings-account-actions__note<?= $adminAccountNoteState !== '' ? ' is-' . $escape($adminAccountNoteState) : '' ?><?= $adminAccountFlashState !== '' ? ' is-flash' : '' ?>"
                                id="adminAccountActionNote"
                                data-default-message="Review your changes before saving."
                                data-default-icon="bi-info-circle"
                                data-flash-message="<?= $escape($adminAccountFlashMessage) ?>"
                                data-flash-icon="<?= $escape($adminAccountFlashIcon) ?>"
                                data-flash-state="<?= $escape($adminAccountFlashState) ?>"
                            >
                                <i class="bi <?= $escape($adminAccountNoteIcon) ?>"></i>
                                <span><?= $escape($adminAccountNoteMessage) ?></span>
                            </p>
                            <button class="action-btn action-btn--primary" type="submit" id="adminAccountSaveButton"><i class="bi bi-check2-circle"></i><span>Save Changes</span></button>
                        </div>
                    </form>
                </article>
            </div>
        </section>
        <section class="settings-section" id="manage-public-page" data-settings-section="manage-public-page"<?= $settingsSectionHiddenAttr('manage-public-page') ?>>
            <div class="settings-section__intro">
                <div>
                    <h2>Manage Public Page</h2>
                </div>
            </div>

            <div class="settings-manage-page">
                <article class="settings-block settings-block--public-page-note">
                    <div class="settings-block__heading">
                        <h3>Manage Public Page</h3>
                        <p>Only the hero image, the three service cards with images, the gallery, and the contact details are managed here. The public-facing layout and package section stay unchanged.</p>
                    </div>
                </article>

                <article class="settings-block">
                    <form method="post" enctype="multipart/form-data" id="heroImageForm">
                        <input type="hidden" name="settings_action" value="save_hero_image">

                        <div class="settings-block__heading settings-block__heading--split">
                            <div>
                                <h3>Hero Image</h3>
                                <p>Update the main visual shown in the hero section without changing the current public-page layout.</p>
                            </div>
                            <button class="action-btn action-btn--primary" type="button" id="heroImageSaveButton" data-bs-toggle="modal" data-bs-target="#heroImageConfirmModal"><i class="bi bi-image"></i><span>Save Hero Image</span></button>
                        </div>

                        <?php if ($heroNoticeMessage !== ''): ?>
                            <div class="alert alert-<?= $escape($heroNoticeClass) ?> mb-4" role="alert">
                                <?= $escape($heroNoticeMessage) ?>
                            </div>
                        <?php endif; ?>

                        <div class="settings-field-grid settings-field-grid--public-assets">
                                <label class="settings-field">
                                    <span class="settings-field__label">Current Image</span>
                                    <input class="settings-input" type="text" value="<?= $escape($heroImageFileName) ?>" readonly>
                                    <span class="settings-field__hint">
                                        <?php if ($hasHeroImage): ?>
                                            Current live file: <a href="<?= $escape($heroImageUrl) ?>" target="_blank" rel="noopener"><?= $escape($heroImageFileName) ?></a>
                                        <?php else: ?>
                                            No live hero image uploaded yet.
                                        <?php endif; ?>
                                    </span>
                                </label>
                            <label class="settings-field">
                                <span class="settings-field__label">Choose Image</span>
                                <input class="settings-input" id="heroImageInput" type="file" name="hero_image" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif" required>
                                <span class="settings-field__hint">Accepted formats: JPG, PNG, WEBP, GIF. Maximum file size: 5 MB.</span>
                            </label>
                        </div>
                    </form>
                </article>

                <article class="settings-block">
                    <form method="post" enctype="multipart/form-data" id="serviceCardsForm">
                        <input type="hidden" name="settings_action" value="save_service_cards">

                        <div class="settings-block__heading settings-block__heading--split">
                            <div>
                                <h3>Services With Images</h3>
                                <p>Edit only the three service cards currently displayed on the public page.</p>
                            </div>
                            <button class="action-btn action-btn--primary" type="button" id="serviceCardsSaveButton"><i class="bi bi-stars"></i><span>Save Services</span></button>
                        </div>

                        <?php if ($servicesNoticeMessage !== ''): ?>
                            <div class="alert alert-<?= $escape($servicesNoticeClass) ?> mb-4" role="alert">
                                <?= $escape($servicesNoticeMessage) ?>
                            </div>
                        <?php endif; ?>

                        <div class="public-page-services-grid">
                            <?php foreach ($serviceCardsForm as $slotKey => $serviceCard): ?>
                                <?php
                                    $serviceNumber = (int) ($serviceCard['sort_order'] ?? 0);
                                    $serviceLabel = $serviceNumber > 0 ? 'Service ' . $serviceNumber : ucfirst(str_replace('_', ' ', (string) $slotKey));
                                    $serviceImageMeta = emarioh_admin_settings_public_asset_meta((string) ($publicServiceCards[$slotKey]['image_path'] ?? ''));
                                ?>
                                <article class="public-service-editor" data-service-card-editor>
                                    <h4 class="public-service-editor__title"><?= $escape($serviceLabel) ?></h4>

                                    <div class="settings-field-grid">
                                        <label class="settings-field">
                                            <span class="settings-field__label">Title</span>
                                            <input
                                                class="settings-input"
                                                type="text"
                                                name="service_cards[<?= $escape($slotKey) ?>][title]"
                                                value="<?= $escape((string) ($serviceCard['title'] ?? '')) ?>"
                                                maxlength="150"
                                                required
                                            >
                                        </label>
                                        <label class="settings-field">
                                            <span class="settings-field__label">Current Image</span>
                                            <input class="settings-input" type="text" value="<?= $escape($serviceImageMeta['file_name']) ?>" readonly>
                                        </label>
                                        <label class="settings-field settings-field--full">
                                            <span class="settings-field__label">Choose Image</span>
                                            <input
                                                class="settings-input"
                                                type="file"
                                                name="service_images[<?= $escape($slotKey) ?>]"
                                                accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif"
                                            >
                                            <span class="settings-field__hint">Accepted formats: JPG, PNG, WEBP, GIF. Maximum file size: 5 MB.</span>
                                        </label>
                                        <label class="settings-field settings-field--full">
                                            <span class="settings-field__label">Description</span>
                                            <textarea class="settings-textarea" name="service_cards[<?= $escape($slotKey) ?>][description]" required><?= $escape((string) ($serviceCard['description'] ?? '')) ?></textarea>
                                        </label>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </form>
                </article>

                <div class="settings-communications-grid settings-communications-grid--public-page">
                    <article class="settings-block">
                        <form method="post" enctype="multipart/form-data" id="galleryUploadForm">
                            <input type="hidden" name="settings_action" value="save_gallery_image">

                            <div class="settings-block__heading settings-block__heading--split">
                                <div>
                                    <h3>Gallery</h3>
                                    <p>Add new gallery images here, then delete an existing one below when needed.</p>
                                </div>
                                <button class="action-btn action-btn--primary" type="button" id="gallerySaveButton"><i class="bi bi-images"></i><span>Add To Gallery</span></button>
                            </div>

                            <?php if ($galleryNoticeMessage !== ''): ?>
                                <div class="alert alert-<?= $escape($galleryNoticeClass) ?> mb-4" role="alert">
                                    <?= $escape($galleryNoticeMessage) ?>
                                </div>
                            <?php endif; ?>

                            <div class="settings-field-grid">
                                <label class="settings-field settings-field--full">
                                    <span class="settings-field__label">Choose New Image</span>
                                    <input
                                        class="settings-input"
                                        id="galleryImageInput"
                                        type="file"
                                        name="gallery_image"
                                        accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif"
                                        required
                                    >
                                    <span class="settings-field__hint">Accepted formats: JPG, PNG, WEBP, GIF. Maximum file size: 5 MB.</span>
                                </label>
                                <label class="settings-field">
                                    <span class="settings-field__label">Category</span>
                                    <select class="settings-select" id="galleryCategoryFilter" name="gallery_category">
                                        <?php foreach ($galleryCategoryOptions as $galleryCategoryValue => $galleryCategoryLabel): ?>
                                            <option value="<?= $escape($galleryCategoryValue) ?>"<?= $galleryForm['category'] === $galleryCategoryValue ? ' selected' : '' ?>><?= $escape($galleryCategoryLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="settings-field">
                                    <span class="settings-field__label">Caption</span>
                                    <input
                                        class="settings-input"
                                        id="galleryCaptionInput"
                                        type="text"
                                        name="gallery_caption"
                                        value="<?= $escape((string) ($galleryForm['caption'] ?? '')) ?>"
                                        maxlength="150"
                                        placeholder="Enter image caption"
                                    >
                                </label>
                            </div>
                        </form>

                        <form method="post" id="galleryDeleteForm">
                            <input type="hidden" name="settings_action" value="delete_gallery_image">
                            <input type="hidden" name="gallery_item_id" id="galleryDeleteItemId" value="">
                        </form>

                        <div class="public-gallery-list" id="galleryManagerList">
                            <?php foreach ($galleryItems as $galleryItem): ?>
                                <article
                                    class="public-gallery-card"
                                    data-gallery-manager-category="<?= $escape((string) ($galleryItem['category'] ?? '')) ?>"
                                    data-gallery-item-id="<?= (int) ($galleryItem['id'] ?? 0) ?>"
                                >
                                    <div class="public-gallery-card__meta">
                                        <strong><?= $escape((string) ($galleryItem['title'] ?? 'Gallery image')) ?></strong>
                                        <span><?= $escape((string) ($galleryItem['file_name'] ?? 'Uploaded image')) ?></span>
                                    </div>
                                    <div class="public-gallery-card__controls">
                                        <div class="public-gallery-card__actions">
                                            <button class="action-btn action-btn--danger" type="button" data-gallery-delete>
                                                <i class="bi bi-trash"></i>
                                                <span>Delete</span>
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        <p class="public-gallery-empty" id="galleryManagerEmptyState" hidden>No gallery images available for this category yet.</p>
                    </article>

                    <article class="settings-block">
                        <form method="post" id="contactDetailsForm">
                            <input type="hidden" name="settings_action" value="save_contact_details">

                            <div class="settings-block__heading settings-block__heading--split">
                                <div>
                                    <h3>Contacts</h3>
                                    <p>Keep only the contact details that appear on the public page updated.</p>
                                </div>
                                <button class="action-btn action-btn--primary" type="button" id="contactDetailsSaveButton"><i class="bi bi-telephone"></i><span>Save Contacts</span></button>
                            </div>

                            <?php if ($contactsNoticeMessage !== ''): ?>
                                <div class="alert alert-<?= $escape($contactsNoticeClass) ?> mb-4" role="alert">
                                    <?= $escape($contactsNoticeMessage) ?>
                                </div>
                            <?php endif; ?>

                            <div class="settings-field-grid settings-field-grid--contacts">
                                <label class="settings-field">
                                    <span class="settings-field__label">Service Area</span>
                                    <input
                                        class="settings-input"
                                        id="contactServiceAreaInput"
                                        type="text"
                                        name="contact_service_area"
                                        value="<?= $escape($contactDetailsForm['service_area']) ?>"
                                        placeholder="Enter service area"
                                        maxlength="255"
                                        required
                                    >
                                </label>
                                <label class="settings-field">
                                    <span class="settings-field__label">Public Email</span>
                                    <input
                                        class="settings-input"
                                        id="contactPublicEmailInput"
                                        type="email"
                                        name="contact_public_email"
                                        value="<?= $escape($contactDetailsForm['public_email']) ?>"
                                        placeholder="Enter public email"
                                        maxlength="190"
                                        required
                                    >
                                </label>
                                <label class="settings-field">
                                    <span class="settings-field__label">Mobile Number</span>
                                    <input
                                        class="settings-input"
                                        id="contactPrimaryMobileInput"
                                        type="text"
                                        name="contact_primary_mobile"
                                        value="<?= $escape($contactDetailsForm['primary_mobile']) ?>"
                                        placeholder="Enter public mobile number"
                                        maxlength="20"
                                        required
                                    >
                                </label>
                                <label class="settings-field">
                                    <span class="settings-field__label">Business Hours</span>
                                    <input
                                        class="settings-input"
                                        id="contactBusinessHoursInput"
                                        type="text"
                                        name="contact_business_hours"
                                        value="<?= $escape($contactDetailsForm['business_hours']) ?>"
                                        placeholder="Enter business hours"
                                        maxlength="190"
                                        required
                                    >
                                </label>
                            </div>
                        </form>
                    </article>
                </div>
            </div>
        </section>
        <section class="settings-section" id="content-management" data-settings-section="content-management"<?= $settingsSectionHiddenAttr('content-management') ?>>
            <div class="settings-section__intro">
                <div>
                    <h2>Content</h2>
                </div>
            </div>

            <div class="settings-communications-grid">
                <article class="settings-block">
                    <div class="settings-block__heading">
                        <h3>Public Page Text</h3>
                    </div>
                    <div class="settings-field-grid">
                        <label class="settings-field">
                            <span class="settings-field__label">Hero Title</span>
                            <input class="settings-input" type="text" value="Elegant Catering for Weddings, Debuts, and Corporate Events">
                        </label>
                        <label class="settings-field">
                            <span class="settings-field__label">Hero Button Text</span>
                            <input class="settings-input" type="text" value="Request a Quote">
                        </label>
                        <label class="settings-field settings-field--full">
                            <span class="settings-field__label">Hero Subtitle</span>
                            <textarea class="settings-textarea">Premium buffet styling, complete event setup, and reliable catering service for your special occasion.</textarea>
                        </label>
                        <label class="settings-field">
                            <span class="settings-field__label">About Section Title</span>
                            <input class="settings-input" type="text" value="About Emarioh Catering Services">
                        </label>
                        <label class="settings-field">
                            <span class="settings-field__label">Packages Section Title</span>
                            <input class="settings-input" type="text" value="Our Catering Packages">
                        </label>
                        <label class="settings-field">
                            <span class="settings-field__label">Gallery Section Title</span>
                            <input class="settings-input" type="text" value="Event Gallery">
                        </label>
                        <label class="settings-field">
                            <span class="settings-field__label">Contact CTA Title</span>
                            <input class="settings-input" type="text" value="Talk to Our Catering Team">
                        </label>
                        <label class="settings-field settings-field--full">
                            <span class="settings-field__label">About Description</span>
                            <textarea class="settings-textarea">We provide full-service catering with menu planning, buffet styling, table setup, and event coordination support for clients across Bulacan and nearby cities.</textarea>
                        </label>
                    </div>
                </article>

                <article class="settings-block">
                    <div class="settings-block__heading">
                        <h3>Section Visibility</h3>
                    </div>
                    <div class="settings-toggle-list">
                        <div class="settings-toggle-row">
                            <div class="settings-toggle-copy"><strong>Show Packages section on public page</strong></div>
                            <span class="settings-switch is-on" aria-hidden="true"></span>
                        </div>
                        <div class="settings-toggle-row">
                            <div class="settings-toggle-copy"><strong>Show Gallery section on public page</strong></div>
                            <span class="settings-switch is-on" aria-hidden="true"></span>
                        </div>
                        <div class="settings-toggle-row">
                            <div class="settings-toggle-copy"><strong>Show Contact section on public page</strong></div>
                            <span class="settings-switch is-on" aria-hidden="true"></span>
                        </div>
                        <div class="settings-toggle-row">
                            <div class="settings-toggle-copy"><strong>Show inquiry button in hero</strong></div>
                            <span class="settings-switch is-on" aria-hidden="true"></span>
                        </div>
                    </div>
                    <div class="settings-field-grid">
                        <label class="settings-field settings-field--full">
                            <span class="settings-field__label">Footer Note</span>
                            <textarea class="settings-textarea">Serving weddings, birthdays, debuts, and corporate events with complete buffet setup and event-ready catering service.</textarea>
                        </label>
                    </div>
                </article>
            </div>
        </section>
<section class="settings-section" id="package-management" data-settings-section="package-management"<?= $settingsSectionHiddenAttr('package-management') ?>>
                            <div class="settings-section__intro">
                                <div>
                                    <p class="settings-section__eyebrow">Package Catalog</p>
                                    <h2>Packages</h2>
                                    <p>Manage the package offers shown on the public page and inside the client booking form without leaving Settings.</p>
                                </div>
</div>

                            <section class="summary-grid summary-grid--packages">
                                <article class="summary-card">
                                    <span class="summary-card__icon"><i class="bi bi-journal-richtext"></i></span>
                                    <div class="summary-card__body">
                                        <p class="summary-card__label">Total Packages</p>
                                        <p class="summary-card__value" id="packageSummaryTotal">0</p>
                                    </div>
                                </article>
                                <article class="summary-card">
                                    <span class="summary-card__icon"><i class="bi bi-patch-check"></i></span>
                                    <div class="summary-card__body">
                                        <p class="summary-card__label">Active</p>
                                        <p class="summary-card__value" id="packageSummaryActive">0</p>
                                    </div>
                                </article>
                                <article class="summary-card">
                                    <span class="summary-card__icon"><i class="bi bi-hourglass-split"></i></span>
                                    <div class="summary-card__body">
                                        <p class="summary-card__label">Review</p>
                                        <p class="summary-card__value" id="packageSummaryReview">0</p>
                                    </div>
                                </article>
                                <article class="summary-card">
                                    <span class="summary-card__icon"><i class="bi bi-pause-circle"></i></span>
                                    <div class="summary-card__body">
                                        <p class="summary-card__label">Inactive</p>
                                        <p class="summary-card__value" id="packageSummaryInactive">0</p>
                                    </div>
                                </article>
                            </section>

                            <section class="package-admin-layout">
                                <section class="surface-card package-form-card">
                                    <div class="panel-heading">
                                        <div>
                                            <p class="panel-heading__eyebrow">Package Manager</p>
                                            <h2 id="packageFormHeading">Add Package</h2>
                                        </div>
                                    </div>

                                    <form id="packageManagerForm" class="package-form" autocomplete="off">
                                        <input type="hidden" id="packageId">

                                        <div class="package-form-grid">
                                            <div class="package-form-field package-form-field--full">
                                                <label for="packageName" class="form-label">Package Name</label>
                                                <input id="packageName" class="form-control" type="text" placeholder="Example: Wedding Classic Package" required>
                                            </div>

                                            <div class="package-form-field">
                                                <label for="packageGroup" class="form-label">Offer Group</label>
                                                <select id="packageGroup" class="form-select" required>
                                                    <option value="per-head">Per-Head Packages</option>
                                                    <option value="celebration">Celebration Packages</option>
                                                </select>
                                            </div>

                                            <div class="package-form-field">
                                                <label for="packageStatus" class="form-label">Status</label>
                                                <select id="packageStatus" class="form-select" required>
                                                    <option value="active">Active</option>
                                                    <option value="review">Review</option>
                                                    <option value="inactive">Inactive</option>
                                                </select>
                                            </div>

                                            <div class="package-form-field">
                                                <label for="packageCategory" class="form-label">Category</label>
                                                <input id="packageCategory" class="form-control" type="text" placeholder="Wedding, Corporate, Outdoor" required>
                                            </div>

                                            <div class="package-form-field">
                                                <label for="packageGuests" class="form-label">Guest Label</label>
                                                <input id="packageGuests" class="form-control" type="text" placeholder="150 pax or 50 pax minimum" required>
                                            </div>

                                            <div class="package-form-field">
                                                <label for="packageRate" class="form-label">Rate Label</label>
                                                <input id="packageRate" class="form-control" type="text" placeholder="PHP 85,000 or PHP 350 / head" required>
                                            </div>

                                            <div class="package-form-field package-form-field--full">
                                                <label for="packageDescription" class="form-label">Short Description</label>
                                                <textarea id="packageDescription" class="form-control" rows="3" placeholder="Short client-facing description of the package." required></textarea>
                                            </div>

                                            <div class="package-form-field package-form-field--full">
                                                <label for="packageTags" class="form-label">Tags</label>
                                                <input id="packageTags" class="form-control" type="text" placeholder="Comma-separated tags like Styled buffet, Wedding-ready">
                                            </div>

                                            <div class="package-form-field package-form-field--full">
                                                <label for="packageInclusions" class="form-label">Inclusions</label>
                                                <textarea id="packageInclusions" class="form-control" rows="6" placeholder="One inclusion per line." required></textarea>
                                            </div>
                                        </div>

                                        <div class="package-form-actions">
                                            <button class="action-btn action-btn--primary" type="submit" id="packageSaveButton">Save package</button>
                                        </div>

                                        <p class="package-form-feedback" id="packageFormFeedback" aria-live="polite"></p>
                                    </form>
                                </section>

                                <section class="surface-card surface-card--booking-queue">
                                    <div class="panel-heading">
                                        <div>
                                            <p class="panel-heading__eyebrow">Published Offers</p>
                                            <h2>Package List</h2>
                                        </div>
                                    </div>

                                    <div class="admin-toolbar">
                                        <label class="admin-search package-search" for="packageSearchInput">
                                            <span class="admin-search__icon"><i class="bi bi-search"></i></span>
                                            <input id="packageSearchInput" class="admin-search__input" type="search" placeholder="Search package name, category, rate, or tag">
                                        </label>
                                    </div>

                                    <div class="booking-filters booking-filters--compact" id="packageStatusFilters" aria-label="Package filters">
                                        <button class="booking-filter-chip is-active" type="button" data-package-filter="all" aria-pressed="true">All</button>
                                        <button class="booking-filter-chip" type="button" data-package-filter="active" aria-pressed="false">Active</button>
                                        <button class="booking-filter-chip" type="button" data-package-filter="review" aria-pressed="false">Review</button>
                                        <button class="booking-filter-chip" type="button" data-package-filter="inactive" aria-pressed="false">Inactive</button>
                                    </div>

                                    <div class="table-responsive dashboard-table-wrap">
                                        <table class="admin-table admin-table--packages">
                                            <thead>
                                                <tr>
                                                    <th>Package</th>
                                                    <th>Group / Category</th>
                                                    <th>Guests</th>
                                                    <th>Rate</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="packageTableBody"></tbody>
                                        </table>
                                    </div>

                                    <p class="booking-filter-empty" id="packageEmptyState" hidden>No packages found.</p>
                                </section>
                            </section>
                        </section>

                                <section class="settings-section" id="gallery-management" data-settings-section="gallery-management"<?= $settingsSectionHiddenAttr('gallery-management') ?>>
            <div class="settings-section__intro">
                <div>
                    <h2>Gallery</h2>
                </div>
            </div>

            <div class="settings-communications-grid settings-gallery-layout">
                <article class="settings-block">
                    <div class="settings-block__heading">
                        <h3>Upload New Image</h3>
                    </div>
                    <div class="settings-field-grid">
                        <label class="settings-field settings-field--full">
                            <span class="settings-field__label">Image File</span>
                            <input class="settings-input" type="file" accept="image/*">
                        </label>
                        <label class="settings-field">
                            <span class="settings-field__label">Category</span>
                            <select class="settings-select">
                                <option selected>Wedding</option>
                                <option>Birthday</option>
                                <option>Debut</option>
                                <option>Corporate</option>
                            </select>
                        </label>
                        <label class="settings-field">
                            <span class="settings-field__label">Display Order</span>
                            <input class="settings-input" type="number" value="1">
                        </label>
                        <label class="settings-field settings-field--full">
                            <span class="settings-field__label">Image Caption</span>
                            <input class="settings-input" type="text" value="Elegant buffet setup for wedding reception">
                        </label>
                        <label class="settings-field settings-field--full">
                            <span class="settings-field__label">Alt Text</span>
                            <input class="settings-input" type="text" value="Wedding catering buffet setup with floral styling">
                        </label>
                    </div>
                    <div class="package-form-actions">
                        <button class="action-btn action-btn--primary" type="button">Upload image</button>
                    </div>
                </article>

                <article class="settings-block">
                    <div class="settings-block__heading">
                        <h3>Current Gallery</h3>
                    </div>
                    <div class="settings-gallery-grid">
                        <article class="settings-gallery-card">
                            <div class="settings-gallery-card__preview">Wedding</div>
                            <div class="settings-gallery-card__body">
                                <strong>Elegant Wedding Setup</strong>
                                <span>Hero slider image</span>
                            </div>
                        </article>
                        <article class="settings-gallery-card">
                            <div class="settings-gallery-card__preview">Debut</div>
                            <div class="settings-gallery-card__body">
                                <strong>Debut Dessert Station</strong>
                                <span>Public gallery</span>
                            </div>
                        </article>
                        <article class="settings-gallery-card">
                            <div class="settings-gallery-card__preview">Birthday</div>
                            <div class="settings-gallery-card__body">
                                <strong>Birthday Buffet Styling</strong>
                                <span>Public gallery</span>
                            </div>
                        </article>
                        <article class="settings-gallery-card">
                            <div class="settings-gallery-card__preview">Corporate</div>
                            <div class="settings-gallery-card__body">
                                <strong>Corporate Catering Setup</strong>
                                <span>Homepage featured image</span>
                            </div>
                        </article>
                    </div>
                </article>
            </div>
        </section>
        <section class="settings-section" id="contact-settings" data-settings-section="contact-settings"<?= $settingsSectionHiddenAttr('contact-settings') ?>>
            <div class="settings-section__intro">
                <div>
                    <h2>Contacts</h2>
                </div>
            </div>

            <article class="settings-block">
                <div class="settings-block__heading">
                    <h3>Public Contact Information</h3>
                </div>
                <div class="settings-field-grid">
                    <label class="settings-field">
                        <span class="settings-field__label">Primary Mobile</span>
                        <input class="settings-input" type="text" value="<?= $escape($contactDetailsForm['primary_mobile']) ?>">
                    </label>
                    <label class="settings-field">
                        <span class="settings-field__label">Secondary Mobile</span>
                        <input class="settings-input" type="text" value="<?= $escape($contactDetailsDisplay['secondary_mobile']) ?>">
                    </label>
                    <label class="settings-field">
                        <span class="settings-field__label">Public Email</span>
                        <input class="settings-input" type="email" value="<?= $escape($contactDetailsForm['public_email']) ?>">
                    </label>
                    <label class="settings-field">
                        <span class="settings-field__label">Facebook Page</span>
                        <input class="settings-input" type="text" value="<?= $escape($contactDetailsDisplay['facebook_url']) ?>">
                    </label>
                    <label class="settings-field">
                        <span class="settings-field__label">Messenger Link</span>
                        <input class="settings-input" type="text" value="<?= $escape($contactDetailsDisplay['messenger_url']) ?>">
                    </label>
                    <label class="settings-field settings-field--full">
                        <span class="settings-field__label">Business Address</span>
                        <input class="settings-input" type="text" value="<?= $escape($contactDetailsDisplay['business_address']) ?>">
                    </label>
                    <label class="settings-field settings-field--full">
                        <span class="settings-field__label">Google Map Embed Link</span>
                        <textarea class="settings-textarea"><?= $escape($contactDetailsDisplay['map_embed_url']) ?></textarea>
                    </label>
                </div>
            </article>
        </section>

        <section class="settings-section" id="payment-settings" data-settings-section="payment-settings"<?= $settingsSectionHiddenAttr('payment-settings') ?>>
            <div class="settings-section__intro">
                <div>
                    <p class="settings-section__eyebrow">Payment</p>
                    <h2>Payment</h2>
                    <p>Set which services allow down payment.</p>
                </div>
            </div>

            <article class="settings-block settings-block--package-down-payments">
                <div class="settings-block__heading settings-block__heading--split">
                    <div>
                        <h3>Service Down Payment Rules</h3>
                        <p>Turn on or off per service, then enter the amount.</p>
                    </div>
                    <div class="package-down-payment-actions">
                        <p class="package-down-payment-feedback package-down-payment-feedback--inline" id="packageDownPaymentFeedback" aria-live="polite"></p>
                        <button class="action-btn action-btn--primary" type="submit" form="packageDownPaymentForm" id="packageDownPaymentSaveButton"><i class="bi bi-save"></i><span>Save Service Rules</span></button>
                    </div>
                </div>

                <form id="packageDownPaymentForm" class="package-down-payment-form" autocomplete="off">
                    <div class="package-down-payment-list" id="packageDownPaymentList"></div>
                </form>
            </article>
        </section>

                                <section class="settings-section" id="sms-templates" data-settings-section="sms-templates"<?= $settingsSectionHiddenAttr('sms-templates') ?>>
            <div class="settings-section__intro">
                <div>
                    <p class="settings-section__eyebrow">SMS</p>
                    <h2>SMS Templates</h2>
                    <p>Keep this section focused on ready-to-send SMS messages only: inquiry updates, booking updates, payment follow-ups, and the final event reminder.</p>
                </div>
            </div>

            <div class="settings-field-stack">
                <article class="settings-block">
                    <form method="post" id="smsTemplatesForm">
                        <input type="hidden" name="settings_action" value="save_sms_templates">

                        <div class="settings-block__heading">
                            <div>
                                <h3>Core Templates</h3>
                                <p>These six templates are enough for the usual admin flow and keep the SMS section easy to manage.</p>
                                <?php if ($smsTemplatePlaceholderTokens !== []): ?>
                                    <div class="sms-placeholder-inline" aria-label="Supported placeholders">
                                        <span class="sms-placeholder-inline__label">Use placeholders:</span>
                                        <div class="sms-placeholder-inline__chips">
                                            <?php foreach ($smsTemplatePlaceholderTokens as $placeholderToken): ?>
                                                <span class="sms-placeholder-chip"><?= $escape($placeholderToken) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($smsTemplatesNoticeMessage !== ''): ?>
                            <div class="alert alert-<?= $escape($smsTemplatesNoticeClass) ?> mb-4" role="alert">
                                <?= $escape($smsTemplatesNoticeMessage) ?>
                            </div>
                        <?php endif; ?>

                        <div class="settings-field-grid">
                            <?php foreach ($smsTemplatesForm as $templateKey => $template): ?>
                                <label class="settings-field settings-field--full">
                                    <span class="settings-field__label"><?= $escape((string) ($template['template_name'] ?? 'SMS Template')) ?></span>
                                    <?php if (trim((string) ($template['use_case'] ?? '')) !== ''): ?>
                                        <span class="settings-field__hint"><?= $escape((string) ($template['use_case'] ?? '')) ?></span>
                                    <?php endif; ?>
                                    <textarea
                                        class="settings-textarea"
                                        name="sms_templates[<?= $escape($templateKey) ?>]"
                                        rows="3"
                                        required
                                    ><?= $escape((string) ($template['template_body'] ?? '')) ?></textarea>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="settings-form-actions settings-form-actions--bottom">
                            <button class="action-btn action-btn--primary" type="button" id="smsTemplatesSaveButton"><i class="bi bi-save"></i><span>Save SMS Templates</span></button>
                        </div>
                    </form>
                </article>

            </div>
        </section>
</section>
</main>

                <?= emarioh_render_admin_mobile_nav(basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'))) ?>
            </div>
        </div>
    </div>

    <div class="modal fade booking-modal gallery-delete-modal" id="adminAccountConfirmModal" tabindex="-1" aria-labelledby="adminAccountConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content booking-modal__content">
                <div class="modal-header booking-modal__header">
                    <div>
                        <h2 class="booking-modal__title" id="adminAccountConfirmModalLabel">Save Changes</h2>
                        <p class="gallery-delete-modal__subtitle" id="adminAccountConfirmModalText">Save these admin account changes?</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body booking-modal__body">
                    <div class="service-confirm-summary" id="adminAccountConfirmSummary"></div>
                </div>
                <div class="modal-footer booking-modal__footer">
                    <button class="action-btn action-btn--ghost" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="action-btn action-btn--primary" type="button" id="adminAccountConfirmButton">Yes, Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade booking-modal gallery-delete-modal" id="serviceCardsConfirmModal" tabindex="-1" aria-labelledby="serviceCardsConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content booking-modal__content">
                <div class="modal-header booking-modal__header">
                    <div>
                        <h2 class="booking-modal__title" id="serviceCardsConfirmModalLabel">Save Services</h2>
                        <p class="gallery-delete-modal__subtitle" id="serviceCardsConfirmModalText">Save these service card updates to the public page?</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body booking-modal__body">
                    <div class="service-confirm-summary" id="serviceCardsConfirmSummary"></div>
                </div>
                <div class="modal-footer booking-modal__footer">
                    <button class="action-btn action-btn--ghost" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="action-btn action-btn--primary" type="button" id="serviceCardsConfirmButton">Yes, Save Services</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade booking-modal settings-mobile-modal" id="adminMobileChangeModal" tabindex="-1" aria-labelledby="adminMobileChangeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content booking-modal__content">
                <div class="modal-header booking-modal__header">
                    <div>
                        <h2 class="booking-modal__title" id="adminMobileChangeModalLabel">Change Mobile Number</h2>
                        <p class="settings-mobile-modal__subtitle">Enter a new number and verify the OTP.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body booking-modal__body">
                    <div class="settings-mobile-modal__content">
                        <div class="settings-mobile-modal__intro">
                            <div class="settings-mobile-modal__current">
                                <span class="settings-mobile-modal__current-label">Current mobile number</span>
                                <strong id="adminMobileCurrentDisplay"><?= $escape($adminAccountForm['mobile']) ?></strong>
                            </div>
                        </div>

                        <label class="settings-field settings-field--full">
                            <span class="settings-field__label">New Mobile Number</span>
                            <input
                                class="settings-input"
                                type="tel"
                                id="adminMobileDraftInput"
                                maxlength="20"
                                inputmode="numeric"
                                autocomplete="tel"
                                placeholder="Enter new mobile number"
                            >
                            <span class="settings-field__hint" id="adminMobileDraftHelp">Example: 0917 123 4567</span>
                        </label>

                        <p class="settings-mobile-modal__status is-info" id="adminMobileDraftStatus">Enter a new number.</p>

                        <div class="settings-mobile-otp" id="adminMobileOtpPanel">
                            <div class="settings-mobile-otp__header">
                                <strong>OTP Verification</strong>
                                <span>Send OTP to continue.</span>
                            </div>
                            <div class="settings-mobile-otp__actions">
                                <button class="action-btn action-btn--soft" type="button" id="adminMobileOtpSendButton">Send OTP</button>
                                <button class="action-btn action-btn--ghost" type="button" id="adminMobileOtpResendButton" hidden>Resend</button>
                            </div>
                            <div class="settings-mobile-otp__entry" id="adminMobileOtpEntry" hidden>
                                <div class="settings-mobile-otp__row">
                                    <input
                                        class="settings-input settings-input--otp"
                                        type="text"
                                        id="adminMobileOtpInput"
                                        inputmode="numeric"
                                        maxlength="6"
                                        placeholder="Enter 6-digit OTP"
                                        autocomplete="one-time-code"
                                    >
                                    <button class="action-btn action-btn--primary" type="button" id="adminMobileOtpVerifyButton">Verify</button>
                                </div>
                                <span class="settings-field__hint">Sent to <strong id="adminMobileOtpTarget">your new mobile number</strong>.</span>
                            </div>
                            <p class="settings-mobile-otp__feedback is-hidden" id="adminMobileOtpFeedback"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer booking-modal__footer">
                    <button class="action-btn action-btn--ghost" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="action-btn action-btn--primary" type="button" id="adminMobileApplyButton" disabled>Use &amp; Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade booking-modal gallery-delete-modal" id="heroImageConfirmModal" tabindex="-1" aria-labelledby="heroImageConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content booking-modal__content">
                <div class="modal-header booking-modal__header">
                    <div>
                        <h2 class="booking-modal__title" id="heroImageConfirmModalLabel">Save Hero Image</h2>
                        <p class="gallery-delete-modal__subtitle" id="heroImageConfirmModalText">Save this new hero image to the public page?</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body booking-modal__body">
                    <div class="gallery-delete-modal__target">
                        <strong id="heroImageConfirmTargetTitle">Selected file</strong>
                        <span id="heroImageConfirmTargetFile">No file selected</span>
                    </div>
                </div>
                <div class="modal-footer booking-modal__footer">
                    <button class="action-btn action-btn--ghost" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="action-btn action-btn--primary" type="button" id="heroImageConfirmButton">Yes, Save Hero Image</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade booking-modal gallery-delete-modal" id="gallerySaveConfirmModal" tabindex="-1" aria-labelledby="gallerySaveConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content booking-modal__content">
                <div class="modal-header booking-modal__header">
                    <div>
                        <h2 class="booking-modal__title" id="gallerySaveConfirmModalLabel">Add Gallery Image</h2>
                        <p class="gallery-delete-modal__subtitle" id="gallerySaveConfirmModalText">Save this new gallery image to the page manager?</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body booking-modal__body">
                    <div class="service-confirm-summary" id="gallerySaveConfirmSummary">
                        <div class="gallery-delete-modal__target">
                            <strong>No image selected</strong>
                            <span>Choose an image above, then save again.</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer booking-modal__footer">
                    <button class="action-btn action-btn--ghost" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="action-btn action-btn--primary" type="button" id="gallerySaveConfirmButton">Yes, Add To Gallery</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade booking-modal gallery-delete-modal" id="galleryDeleteModal" tabindex="-1" aria-labelledby="galleryDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content booking-modal__content">
                <div class="modal-header booking-modal__header">
                    <div>
                        <p class="panel-heading__eyebrow">Delete Image</p>
                        <h2 class="booking-modal__title" id="galleryDeleteModalLabel">Delete Gallery Image</h2>
                        <p class="gallery-delete-modal__subtitle" id="galleryDeleteModalText">Remove this image from the gallery list?</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body booking-modal__body">
                    <div class="gallery-delete-modal__target">
                        <strong id="galleryDeleteModalTargetTitle">Wedding Reception</strong>
                        <span id="galleryDeleteModalTargetFile">No file selected</span>
                    </div>
                </div>
                <div class="modal-footer booking-modal__footer">
                    <button class="action-btn action-btn--ghost" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="action-btn action-btn--danger" type="button" id="galleryDeleteConfirmButton">Yes, Delete Image</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade booking-modal gallery-delete-modal" id="contactDetailsConfirmModal" tabindex="-1" aria-labelledby="contactDetailsConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content booking-modal__content">
                <div class="modal-header booking-modal__header">
                    <div>
                        <h2 class="booking-modal__title" id="contactDetailsConfirmModalLabel">Save Contacts</h2>
                        <p class="gallery-delete-modal__subtitle" id="contactDetailsConfirmModalText">Save these contact details to the public page?</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body booking-modal__body">
                    <div class="service-confirm-summary" id="contactDetailsConfirmSummary"></div>
                </div>
                <div class="modal-footer booking-modal__footer">
                    <button class="action-btn action-btn--ghost" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="action-btn action-btn--primary" type="button" id="contactDetailsConfirmButton">Yes, Save Contacts</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade booking-modal gallery-delete-modal" id="packageDownPaymentConfirmModal" tabindex="-1" aria-labelledby="packageDownPaymentConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content booking-modal__content">
                <div class="modal-header booking-modal__header">
                    <div>
                        <h2 class="booking-modal__title" id="packageDownPaymentConfirmModalLabel">Save Service Rules</h2>
                        <p class="gallery-delete-modal__subtitle" id="packageDownPaymentConfirmModalText">Save these down payment rules?</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body booking-modal__body">
                    <div class="service-confirm-summary" id="packageDownPaymentConfirmSummary"></div>
                </div>
                <div class="modal-footer booking-modal__footer">
                    <button class="action-btn action-btn--ghost" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="action-btn action-btn--primary" type="button" id="packageDownPaymentConfirmButton">Yes, Save Rules</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade booking-modal gallery-delete-modal" id="smsTemplatesConfirmModal" tabindex="-1" aria-labelledby="smsTemplatesConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content booking-modal__content">
                <div class="modal-header booking-modal__header">
                    <div>
                        <h2 class="booking-modal__title" id="smsTemplatesConfirmModalLabel">Save SMS Templates</h2>
                        <p class="gallery-delete-modal__subtitle" id="smsTemplatesConfirmModalText">Save these SMS template updates?</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body booking-modal__body">
                    <div class="service-confirm-summary" id="smsTemplatesConfirmSummary"></div>
                </div>
                <div class="modal-footer booking-modal__footer">
                    <button class="action-btn action-btn--ghost" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="action-btn action-btn--primary" type="button" id="smsTemplatesConfirmButton">Yes, Save Templates</button>
                </div>
            </div>
        </div>
    </div>

    <?php if ($smsTemplatesNoticeMessage !== ''): ?>
        <script>
            window.location.hash = "#sms-templates";
        </script>
    <?php elseif ($heroNoticeMessage !== '' || $servicesNoticeMessage !== '' || $galleryNoticeMessage !== '' || $contactsNoticeMessage !== ''): ?>
        <script>
            window.location.hash = "#manage-public-page";
        </script>
    <?php endif; ?>
    <script>
        window.EmariohServerPackageCatalog = <?= $packageCatalogJson ?>;
    </script>
    <?= emarioh_render_vendor_runtime_assets(true); ?>
    <script src="assets/js/auth-api.js"></script>
    <script src="assets/js/logout-confirmation.js?v=20260418a"></script>
    <script src="assets/js/package-catalog.js?v=20260413a"></script>
    <script src="assets/js/pages/index.js?v=20260417c"></script>
    <script src="assets/js/pages/admin-packages.js"></script>
    <script src="assets/js/pages/admin-settings.js?v=20260418h"></script>
</body>
</html>
















