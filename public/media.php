<?php
declare(strict_types=1);

define('EMARIOH_SKIP_SESSION_BOOTSTRAP', true);

require dirname(__DIR__) . '/app/bootstrap.php';

$requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if (!in_array($requestMethod, ['GET', 'HEAD'], true)) {
    http_response_code(405);
    header('Allow: GET, HEAD');
    exit;
}

$asset = emarioh_normalize_public_asset_path((string) ($_GET['asset'] ?? ''), '');

if ($asset === '' || !emarioh_public_asset_is_storage_upload($asset)) {
    http_response_code(404);
    exit;
}

$absolutePath = emarioh_public_asset_absolute_path($asset);

if ($absolutePath === null || !is_file($absolutePath) || !is_readable($absolutePath)) {
    http_response_code(404);
    exit;
}

$mimeType = '';

if (class_exists('finfo')) {
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = strtolower((string) $fileInfo->file($absolutePath));
}

if ($mimeType === '') {
    $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
    $mimeType = match ($extension) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        default => 'application/octet-stream',
    };
}

$allowedMimeTypes = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
];

if (!in_array($mimeType, $allowedMimeTypes, true)) {
    http_response_code(404);
    exit;
}

$fileSize = filesize($absolutePath);
$lastModified = filemtime($absolutePath);

header('Content-Type: ' . $mimeType);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=86400');

if ($fileSize !== false) {
    header('Content-Length: ' . (string) $fileSize);
}

if ($lastModified !== false) {
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
}

if ($requestMethod === 'HEAD') {
    exit;
}

readfile($absolutePath);
exit;
