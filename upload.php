<?php
declare(strict_types=1);

/**
 * File upload handler — accepts images, documents, archives, etc.
 * Saves to upload/ and creates thumbnails for images.
 */

session_start();
define('SESSION_NS', 'esiblog');

$config = json_decode(file_get_contents(__DIR__ . '/secret.json'), true);
$admin_name = (string) ($config['admin_name'] ?? 'admin');
$admin_pwd  = (string) ($config['admin_pwd']  ?? '');

$is_admin = isset($_SESSION[SESSION_NS]['admin_name'])
    && $_SESSION[SESSION_NS]['admin_name'] === $admin_name
    && $_SESSION[SESSION_NS]['admin_pwd'] === $admin_pwd;

if (!$is_admin) {
    http_response_code(403);
    die(json_encode(['error' => 'Not authenticated']));
}

if (empty($_FILES['file'])) {
    http_response_code(400);
    die(json_encode(['error' => 'No file uploaded']));
}

$file = $_FILES['file'];

// Allowed MIME types
$allowed = [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
    'application/pdf',
    'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed',
    'application/gzip', 'application/x-tar',
    'text/plain', 'text/csv', 'text/markdown',
    'application/json', 'application/xml', 'text/xml',
    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'audio/mpeg', 'audio/wav', 'audio/ogg',
    'video/mp4', 'video/webm',
];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed, true)) {
    http_response_code(400);
    die(json_encode(['error' => 'File type not allowed: ' . $mime]));
}

// 50MB max
if ($file['size'] > 50 * 1024 * 1024) {
    http_response_code(400);
    die(json_encode(['error' => 'File too large (max 50MB)']));
}

// Detect extension from MIME
$ext_map = [
    'image/jpeg' => '.jpg', 'image/png' => '.png', 'image/gif' => '.gif',
    'image/webp' => '.webp', 'image/svg+xml' => '.svg',
    'application/pdf' => '.pdf',
    'application/zip' => '.zip', 'application/x-rar-compressed' => '.rar',
    'application/x-7z-compressed' => '.7z', 'application/gzip' => '.gz',
    'application/x-tar' => '.tar',
    'text/plain' => '.txt', 'text/csv' => '.csv', 'text/markdown' => '.md',
    'application/json' => '.json', 'application/xml' => '.xml', 'text/xml' => '.xml',
    'application/msword' => '.doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '.docx',
    'application/vnd.ms-excel' => '.xls',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '.xlsx',
    'application/vnd.ms-powerpoint' => '.ppt',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation' => '.pptx',
    'audio/mpeg' => '.mp3', 'audio/wav' => '.wav', 'audio/ogg' => '.ogg',
    'video/mp4' => '.mp4', 'video/webm' => '.webm',
];
$ext = $ext_map[$mime] ?? '.bin';

// Custom filename
$title = $_POST['title'] ?? '';
if ($title !== '') {
    $title = preg_replace('/[\/\\\\\0<>:"|?*]+/u', '_', $title);
    $title = trim($title, '.');
    if (str_ends_with(strtolower($title), $ext)) {
        $title = substr($title, 0, -strlen($ext));
    }
    if ($title === '') {
        $title = date('Ymd-His-') . bin2hex(random_bytes(4));
    }
    $base = $title;
    $n = 1;
    while (file_exists(__DIR__ . '/upload/' . $title . $ext)) {
        $title = $base . '-' . $n;
        $n++;
    }
    $filename = $title . $ext;
} else {
    $filename = date('Ymd-His-') . bin2hex(random_bytes(4)) . $ext;
}

$dest = __DIR__ . '/upload/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    http_response_code(500);
    die(json_encode(['error' => 'Failed to save file']));
}

// Thumbnail for images only
$is_image = str_starts_with($mime, 'image/');
if ($is_image && $mime !== 'image/svg+xml' && function_exists('imagecreatetruecolor')) {
    $thumb_dest = __DIR__ . '/thumbs/' . $filename;
    [$src_w, $src_h] = getimagesize($dest);
    $thumb_w = 200;
    $thumb_h = (int) ($src_h * ($thumb_w / $src_w));
    $src_img = match ($mime) {
        'image/jpeg' => imagecreatefromjpeg($dest),
        'image/png'  => imagecreatefrompng($dest),
        'image/gif'  => imagecreatefromgif($dest),
        'image/webp' => imagecreatefromwebp($dest),
        default => null,
    };
    if ($src_img) {
        $thumb_img = imagecreatetruecolor($thumb_w, $thumb_h);
        imagecopyresampled($thumb_img, $src_img, 0, 0, 0, 0, $thumb_w, $thumb_h, $src_w, $src_h);
        match ($mime) {
            'image/jpeg' => imagejpeg($thumb_img, $thumb_dest, 85),
            'image/png'  => imagepng($thumb_img, $thumb_dest, 6),
            'image/gif'  => imagegif($thumb_img, $thumb_dest),
            'image/webp' => imagewebp($thumb_img, $thumb_dest, 85),
            default => null,
        };
        imagedestroy($src_img);
        imagedestroy($thumb_img);
    }
}

header('Content-Type: application/json');
echo json_encode(['location' => 'upload/' . $filename]);
