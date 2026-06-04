<?php
declare(strict_types=1);

/**
 * JSON endpoint: list uploaded images for TinyMCE image picker.
 * Returns [{title: filename, value: url}, ...]
 */

session_start();
define('SESSION_NS', 'esiblog');

// Auth check (same as upload.php)
$config = json_decode(file_get_contents(__DIR__ . '/secret.json'), true);
$admin_name = (string) ($config['admin_name'] ?? 'admin');
$admin_pwd  = (string) ($config['admin_pwd']  ?? '');

$is_admin = isset($_SESSION[SESSION_NS]['admin_name'])
    && $_SESSION[SESSION_NS]['admin_name'] === $admin_name
    && $_SESSION[SESSION_NS]['admin_pwd'] === $admin_pwd;

if (!$is_admin) {
    http_response_code(403);
    die('[]');
}

$upload_dir = __DIR__ . '/upload/';
$images = [];

if (is_dir($upload_dir)) {
    $dh = opendir($upload_dir);
    if ($dh) {
        while (false !== ($file = readdir($dh))) {
            if ($file === '.' || $file === '..' || $file === '.gitkeep') continue;
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                $images[] = [
                    'title' => $file,
                    'value' => 'upload/' . $file,
                    'menu'  => ['image' => ['src' => 'upload/' . $file, 'alt' => $file]],
                ];
            }
        }
        closedir($dh);
    }
}

header('Content-Type: application/json');
echo json_encode($images);
