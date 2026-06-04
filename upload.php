<?php
declare(strict_types=1);

/**
 * Image upload handler for TinyMCE 7
 * Saves uploaded images to the upload/ directory and creates thumbnails.
 */

// Auth check
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

// Validate
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed_types, true)) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid file type: ' . $mime]));
}

if ($file['size'] > 10 * 1024 * 1024) { // 10MB max
    http_response_code(400);
    die(json_encode(['error' => 'File too large']));
}

// Generate safe filename — use custom title if provided
$ext = match ($mime) {
    'image/jpeg' => '.jpg',
    'image/png'  => '.png',
    'image/gif'  => '.gif',
    'image/webp' => '.webp',
    'image/svg+xml' => '.svg',
    default => '.bin',
};

$title = $_POST['title'] ?? '';
if ($title !== '') {
    // Sanitize: allow Unicode letters, numbers, dash, underscore, dot
    $title = preg_replace('/[\/\\\\\0<>:"|?*]+/u', '_', $title);
    $title = trim($title, '.');
    // Strip existing extension if it matches the detected one
    if (str_ends_with(strtolower($title), $ext)) {
        $title = substr($title, 0, -strlen($ext));
    }
    if ($title === '') {
        $title = date('Ymd-His-') . bin2hex(random_bytes(4));
    }
    // Ensure unique filename
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

// Create thumbnail (skip for SVG)
$thumb_dest = __DIR__ . '/thumbs/' . $filename;
if ($mime !== 'image/svg+xml' && function_exists('imagecreatetruecolor')) {
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

// Return TinyMCE-compatible response
$url = 'upload/' . $filename;
header('Content-Type: application/json');
echo json_encode(['location' => $url]);
