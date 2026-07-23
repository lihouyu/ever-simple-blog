<?php
declare(strict_types=1);

/**
 * One-time migration: convert legacy secret.php to secret.json
 * Usage: php migrate-config.php
 */

// CLI only — web access would let anyone overwrite secret.json with legacy config
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

if (!file_exists(__DIR__ . '/secret.php')) {
    echo "secret.php not found. Nothing to migrate.\n";
    echo "Copy secret-dist.json to secret.json and edit it instead.\n";
    exit(1);
}

// Load legacy config
$site_name       = '';
$site_slogan     = '';
$site_tpl        = '';
$site_lang       = '';
$admin_name      = '';
$admin_pwd       = '';
$admin_email     = '';
$blogs_per_page  = 15;
$enable_uri_tpl  = true;
$context_root    = '/';
$gzip_output     = true;
$your_tz         = '';
$blocks          = [];

include __DIR__ . '/secret.php';

$config = [
    'site_name'      => $site_name,
    'site_slogan'    => $site_slogan,
    'site_tpl'       => $site_tpl,
    'site_lang'      => $site_lang,
    'admin_name'     => $admin_name,
    'admin_pwd'      => $admin_pwd,
    'admin_email'    => $admin_email,
    'blogs_per_page' => $blogs_per_page,
    'enable_uri_tpl' => $enable_uri_tpl,
    'context_root'   => $context_root,
    'gzip_output'    => $gzip_output,
    'your_tz'        => $your_tz,
    'blocks'         => $blocks,
];

$json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
file_put_contents(__DIR__ . '/secret.json', $json . "\n");

echo "Migrated to secret.json\n";
echo "NOTE: Password is still stored as MD5 hash. It will work, but you should\n";
echo "      change the password via the admin panel to upgrade it to bcrypt.\n";
