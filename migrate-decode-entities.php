<?php
declare(strict_types=1);

/**
 * One-time migration: decode HTML entities stored in blog titles and
 * comment author/content. Storage is now raw — escaping happens at output.
 *
 * Usage: php migrate-decode-entities.php
 *
 * Idempotency: run ONCE. Running twice would decode already-raw text
 * (e.g. a literal "&amp;" typed by a user would become "&"). A backup
 * of blog/ and comment/ is recommended before running.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

$decode = fn(string $s): string => html_entity_decode($s, ENT_QUOTES, 'UTF-8');
$changed = 0;

// ── Blog files: only the first line (title) was stored escaped ──────
foreach (glob(__DIR__ . '/blog/cate_*/*') ?: [] as $file) {
    if (!is_file($file)) continue;
    $raw = file_get_contents($file);
    if ($raw === false) continue;

    $parts = explode("\n", $raw, 2);
    $title_decoded = $decode($parts[0]);
    if ($title_decoded !== $parts[0]) {
        file_put_contents(
            $file,
            $title_decoded . (isset($parts[1]) ? "\n" . $parts[1] : '')
        );
        echo "blog title: {$file}\n";
        $changed++;
    }
}

// ── Comment files: author and content were both stored escaped ──────
foreach (glob(__DIR__ . '/comment/*') ?: [] as $file) {
    if (!is_file($file)) continue;
    $raw = file_get_contents($file);
    if ($raw === false) continue;

    $decoded = $decode($raw);
    if ($decoded !== $raw) {
        file_put_contents($file, $decoded);
        echo "comment:    {$file}\n";
        $changed++;
    }
}

echo "Done. {$changed} file(s) updated.\n";
