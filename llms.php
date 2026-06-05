<?php
declare(strict_types=1);

/**
 * Dynamic llms.txt — outputs markdown with blog content for LLM indexing.
 */

// Minimal bootstrap
define('BLOG_FOLDER', __DIR__ . '/blog');
define('COMMENT_FOLDER', __DIR__ . '/comment');

require_once __DIR__ . '/lib/blog.php';
require_once __DIR__ . '/lib/category.php';
require_once __DIR__ . '/lib/utils.php';

$config = json_decode(file_get_contents(__DIR__ . '/secret.json'), true);
$site_name   = $config['site_name']   ?? 'ESiBlog';
$site_slogan = $config['site_slogan'] ?? '';
$site_url    = ($_SERVER['HTTPS'] ?? '') === 'on' ? 'https://' : 'http://';
$site_url   .= $_SERVER['HTTP_HOST'] . ($config['context_root'] ?? '/');

header('Content-Type: text/plain; charset=UTF-8');

echo "# {$site_name} — {$site_slogan}\n";
echo "# llms.txt — content for LLM indexing\n\n";

$all = blog_list('');
$blogs = blog_sort($all ?: []);

foreach ($blogs as $b) {
    $post = blog_read($b['serial'], $b['category']);
    if (!$post) continue;

    $content = strip_tags($post['content']);
    $content = mb_substr($content, 0, 500, 'UTF-8');

    echo "## {$post['title']}\n\n";
    echo "- Date: " . date('Y-m-d', $post['timestamp']) . "\n";
    echo "- Category: {$post['category']}\n";
    echo "- URL: {$site_url}index.php?b={$b['serial']}&c={$b['category']}\n";
    echo "\n{$content}\n\n---\n\n";
}
