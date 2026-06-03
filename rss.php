<?php
declare(strict_types=1);

/**
 * RSS 2.0 feed — uses shared lib, no duplicated code.
 */

// Lite bootstrap (enough for blog functions + config)
define('BLOG_FOLDER', __DIR__ . '/blog');

$config_file = __DIR__ . '/secret.json';
if (!file_exists($config_file)) {
    // Fallback: try legacy
    if (file_exists(__DIR__ . '/secret.php')) {
        include __DIR__ . '/secret.php';
    } else {
        http_response_code(500);
        die('Configuration not found.');
    }
} else {
    $config = json_decode(file_get_contents($config_file), true);
    $site_name   = (string) ($config['site_name']   ?? 'ESiBlog');
    $site_slogan = (string) ($config['site_slogan'] ?? '');
    $context_root = (string) ($config['context_root'] ?? '/');
    $blogs_per_page = (int) ($config['blogs_per_page'] ?? 15);
}

require_once __DIR__ . '/lib/blog.php';
require_once __DIR__ . '/lib/utils.php';

// ── Build RSS ────────────────────────────────────────────────────────
$all_blogs = blog_list('');
$page_blogs = blog_page(1, blog_sort($all_blogs ?: []));

header('Content-Type: application/rss+xml; charset=UTF-8');

echo '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
echo "<rss version=\"2.0\">\n";
echo "<channel>\n";
echo "<title><![CDATA[{$site_name}]]></title>\n";
echo "<description><![CDATA[{$site_slogan}]]></description>\n";
echo "<link>http://{$_SERVER['HTTP_HOST']}{$context_root}</link>\n";

foreach ($page_blogs['blogs'] as $blog) {
    $link = "http://{$_SERVER['HTTP_HOST']}{$context_root}index.php?b={$blog['serial']}&amp;c={$blog['category']}";
    echo "<item>\n";
    echo "<title><![CDATA[{$blog['title']}]]></title>\n";
    echo "<link>{$link}</link>\n";
    echo "<description><![CDATA[{$blog['content']}]]></description>\n";
    echo "<category><![CDATA[{$blog['category']}]]></category>\n";
    echo "<comments>{$link}</comments>\n";
    echo "<pubDate>" . date('r', $blog['timestamp']) . "</pubDate>\n";
    echo "</item>\n";
}
echo "</channel>\n";
echo "</rss>";
