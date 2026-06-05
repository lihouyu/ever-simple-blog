<?php
declare(strict_types=1);

/**
 * JSON endpoint: list uploaded files for TinyMCE file_picker.
 * Images get preview info; other files get type icon labels.
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
    die('[]');
}

$upload_dir = __DIR__ . '/upload/';
$files = [];

if (is_dir($upload_dir)) {
    $dh = opendir($upload_dir);
    if ($dh) {
        while (false !== ($name = readdir($dh))) {
            if ($name === '.' || $name === '..' || $name === '.gitkeep') continue;
            $path = $upload_dir . $name;
            if (!is_file($path)) continue;

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $is_img = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);

            $files[] = [
                'title' => $name,
                'value' => 'upload/' . $name,
                'type'  => $is_img ? 'image' : 'file',
            ];
        }
        closedir($dh);
    }
}

// Sort by name
usort($files, fn($a, $b) => strcasecmp($a['title'], $b['title']));

header('Content-Type: application/json');
echo json_encode($files);
