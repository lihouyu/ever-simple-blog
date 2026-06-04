<?php
declare(strict_types=1);

/**
 * Utility helpers — pager URI building, content extraction, template/lang listing.
 */

/**
 * Build a paginated URI preserving all current GET/POST params.
 */
function pager_build_uri(int $page): string
{
    $uri_string = '&amp;p=' . $page;

    $params = array_merge($_GET ?? [], $_POST ?? []);
    foreach ($params as $key => $value) {
        if ($key !== 'p') {
            $uri_string .= '&amp;' . urlencode((string) $key) . '=' . urlencode((string) $value);
        }
    }

    if ($uri_string === '&amp;p=' . $page) {
        return 'index.php';
    }
    return 'index.php?' . substr($uri_string, 5);
}

/**
 * Extract image src attributes from HTML content.
 */
function get_blog_imgs(string $content): array
{
    $blog_images = [];
    preg_match_all('/<img[^>]+?src=["\'](.+?)["\']/i', $content, $matches);

    if (count($matches[1]) > 0) {
        foreach ($matches[1] as $img_src) {
            $blog_images[] = preg_replace(
                '/^' . preg_quote(UPLOAD_FOLDER, '/') . '/',
                THUMBS_FOLDER,
                $img_src
            );
        }
    }
    return $blog_images;
}

/**
 * Truncate HTML content to plain-text excerpt.
 */
function get_blog_short(string $content, int $short_len = 240): string|false
{
    $content = strip_tags($content);
    if (strlen(trim($content)) === 0) {
        return false;
    }
    return mb_substr($content, 0, $short_len, 'UTF-8');
}

/**
 * List available templates (subdirectories under layout/).
 */
function tpl_list(): array|false
{
    $tpl_path = realpath(TPL_FOLDER);
    if ($tpl_path === false) {
        return false;
    }
    $tpl_folder = opendir($tpl_path);
    if (!$tpl_folder) {
        return false;
    }

    $arr_tpl_names = [];
    while (false !== ($tpl_name = readdir($tpl_folder))) {
        if ($tpl_name === '.' || $tpl_name === '..') {
            continue;
        }
        if (is_dir($tpl_path . '/' . $tpl_name)) {
            $arr_tpl_names[] = $tpl_name;
        }
    }
    closedir($tpl_folder);
    return $arr_tpl_names;
}

/**
 * List available language packs (PHP files under lang/).
 */
function lang_list(): array|false
{
    $lang_path = realpath(LANG_FOLDER);
    if ($lang_path === false) {
        return false;
    }
    $lang_folder = opendir($lang_path);
    if (!$lang_folder) {
        return false;
    }

    $arr_lang_names = [];
    while (false !== ($entry = readdir($lang_folder))) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        if (str_ends_with($entry, '.php')) {
            $arr_lang_names[] = str_replace('.php', '', $entry);
        }
    }
    closedir($lang_folder);
    return $arr_lang_names;
}

/**
 * List available sidebar blocks for the current template.
 */
function block_list(): array|false
{
    global $site_tpl;
    $block_path = realpath(TPL_FOLDER . '/' . $site_tpl . '/' . BLOCK_FOLDER);
    if ($block_path === false) {
        return false;
    }
    $block_folder = opendir($block_path);
    if (!$block_folder) {
        return false;
    }

    $arr_block_names = [];
    while (false !== ($entry = readdir($block_folder))) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        if (str_ends_with($entry, '.php')) {
            $arr_block_names[] = str_replace('.php', '', $entry);
        }
    }
    closedir($block_folder);
    return $arr_block_names;
}

/**
 * Load sidebar blocks — includes the data provider from block/ then the template.
 */
function load_side_blocks(): void
{
    global $site_tpl, $blocks, $lang;

    if (empty($blocks)) {
        return;
    }

    foreach ($blocks as $block) {
        $block_file = BLOCK_FOLDER . '/' . $block . '.php';
        $block_tpl  = TPL_FOLDER . '/' . $site_tpl . '/' . BLOCK_FOLDER . '/' . $block . '.php';

        if (file_exists($block_tpl)) {
            if (file_exists($block_file)) {
                include_once $block_file;
            }
            include_once $block_tpl;
        }
    }
}

/**
 * HTML-escape a string for safe output in templates.
 */
function h(mixed $str): string
{
    if ($str === null) {
        return '';
    }
    return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
}

/**
 * Convert legacy GeSHi <pre class="_geshi_xxx"> blocks to
 * <pre><code class="language-xxx"> format (compatible with
 * highlight.js and TinyMCE codesample).
 */
function convert_geshi_blocks(string $content): string
{
    return preg_replace_callback(
        '/<pre class="_geshi_(.+?)">(.*?)<\/pre>/is',
        function (array $m): string {
            $lang = strtolower($m[1]);
            $code = html_entity_decode($m[2], ENT_QUOTES, 'UTF-8');
            return '<pre><code class="language-' . $lang . '">'
                . htmlspecialchars($code, ENT_QUOTES, 'UTF-8')
                . '</code></pre>';
        },
        $content
    );
}

/**
 * Sanitize user-facing HTML. Strips scripts, event handlers, and
 * malicious attributes while preserving formatting and images.
 */
/**
 * Recode content inside <pre><code> blocks so that HTML-like characters
 * survive TinyMCE's parser (e.g. <?php, <div> inside code blocks).
 * Applied when loading content for editing.
 */
function recode_code_blocks(string $html): string
{
    return preg_replace_callback(
        '/<pre[^>]*><code[^>]*>(.*?)<\/code><\/pre>/is',
        function (array $m): string {
            $inner = $m[1];
            // Double-encode &lt; &gt; inside code blocks for TinyMCE editor.
            // TinyMCE decodes once → visible &lt;text&gt; in the DOM,
            // which won't be parsed as HTML tags/processing instructions.
            $inner = str_replace(
                ['&lt;', '&gt;'],
                ['&amp;lt;', '&amp;gt;'],
                $inner
            );
            return str_replace($m[1], $inner, $m[0]);
        },
        $html
    );
}

function sanitize_html(string $html): string
{
    // Remove script/style/iframe/object/embed entirely
    $html = preg_replace(
        '/<(script|style|iframe|object|embed|form|input|button|select|textarea|link|meta|applet)(\s|>)/is',
        '&lt;$1$2',
        $html
    );

    // Strip JS event handlers (onclick, onload, etc.)
    $html = preg_replace(
        '/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i',
        '',
        $html
    );

    // Strip javascript: URLs
    $html = preg_replace(
        '/href\s*=\s*["\']javascript:[^"\']*["\']/i',
        'href="#"',
        $html
    );
    $html = preg_replace(
        '/src\s*=\s*["\']javascript:[^"\']*["\']/i',
        'src=""',
        $html
    );

    return $html;
}
