# ESiBlog Modernization Plan

> **For Hermes:** Execute task-by-task using subagent-driven-development or direct implementation.

**Goal:** Modernize ESiBlog PHP blog system for PHP 8.x while preserving the flat-file architecture. Eliminate code duplication, upgrade third-party dependencies, and fix security issues.

**Architecture:** Keep the single-file-router pattern but extract shared library functions into `lib/`. Replace GeSHi + TinyMCE 4 with highlight.js + TinyMCE 7. Switch config from executable PHP to JSON.

**Tech Stack:** PHP 8.1+, highlight.js 11.x, TinyMCE 7.x, jQuery 3.7.x (or drop jQuery entirely for vanilla JS)

---

## Phase 1: Foundation — Extract Library & PHP 8.x Compatibility

### Task 1.1: Create directory structure

**Objective:** Set up the new `lib/` directory for shared functions.

**Files:**
- Create: `lib/.gitkeep`
- Update: `.gitignore` (add `secret.json`)

**Step 1:** Create lib directory
```bash
mkdir -p lib
```

**Step 2:** Update .gitignore
```bash
echo "/secret.json" >> .gitignore
```

---

### Task 1.2: Extract core blog functions into `lib/blog.php`

**Objective:** Move all blog-related functions from `index.php` into `lib/blog.php` with PHP 8.x syntax.

**Files:**
- Create: `lib/blog.php`
- Modify: `index.php` (remove functions, add require_once)

**Functions to extract (with PHP 8 modernization):**
- `blog_parse_serial()` — add return type `array`
- `blog_save()` — add param types `string $blog_serial, string $blog_title, string $blog_content, string $category_name = 'general'`, return type `int|false`
- `blog_create()` — return type `string|false`
- `blog_delete()` — return type `bool`
- `blog_read()` — return type `array|false`
- `blog_move()` — return type `bool`
- `blog_list()` — return type `array|false`
- `blog_list_by_category()` — return type `array|false`
- `blog_list_by_archive()` — return type `array|false`
- `blog_sort()` — use `usort()` instead of bubble sort
- `blog_page()` — keep for now
- `archive_list()` — return type `array|false`
- `archive_sort()` — use `usort()`

Also: make `page_*` functions share the same `parse_serial`/`sort` patterns from the generic sort function.

**Details for `lib/blog.php`:**
```php
<?php
declare(strict_types=1);

define('BLOG_FOLDER', 'blog');

function blog_parse_serial(string $blog_serial): array
{
    [$microsecond, $serial] = explode('-', $blog_serial, 2);
    [$timestamp, $archivedate] = explode('.', $serial, 2);

    return [
        'microsecond' => $microsecond,
        'timestamp'   => (int) $timestamp,
        'publishtime' => date('Y-m-d H:i:s', (int) $timestamp),
        'archivedate' => $archivedate,
    ];
}

function blog_save(string $blog_serial, string $blog_title, string $blog_content, string $category_name = 'general'): int|false
{
    $blog_file = BLOG_FOLDER . '/cate_' . base64_encode($category_name) . '/' . $blog_serial;
    $blog_title = htmlspecialchars($blog_title, ENT_QUOTES, 'UTF-8');
    return file_put_contents($blog_file, $blog_title . "\n\n" . $blog_content);
}

function blog_create(string $blog_title, string $blog_content, string $category_name = 'general'): string|false
{
    $blog_serial = str_replace(' ', '-', microtime()) . '.' . date('Y-m-d');
    $result = blog_save($blog_serial, $blog_title, $blog_content, $category_name);
    return $result !== false ? $blog_serial : false;
}

function blog_delete(string $blog_serial, string $category_name): bool
{
    $path = BLOG_FOLDER . '/cate_' . base64_encode($category_name) . '/' . $blog_serial;
    // Prevent path traversal
    if (str_contains($blog_serial, '..') || str_contains($blog_serial, '/')) {
        return false;
    }
    return @unlink($path);
}

function blog_read(string $blog_serial, string $category_name): array|false
{
    $blog_file = BLOG_FOLDER . '/cate_' . base64_encode($category_name) . '/' . $blog_serial;
    if (!file_exists($blog_file)) {
        return false;
    }

    $blog_string = file_get_contents($blog_file);
    if ($blog_string === false) {
        return false;
    }

    [$title, $content] = explode("\n\n", $blog_string, 2);
    $arr_serial = blog_parse_serial($blog_serial);

    return [
        'title'       => $title,
        'content'     => $content,
        'serial'      => $blog_serial,
        'publishtime' => $arr_serial['publishtime'],
        'timestamp'   => $arr_serial['timestamp'],
        'category'    => $category_name,
    ];
}

function blog_move(string $blog_serial, string $src_category_name, string $dst_category_name): bool
{
    $src = BLOG_FOLDER . '/cate_' . base64_encode($src_category_name) . '/' . $blog_serial;
    $dst = BLOG_FOLDER . '/cate_' . base64_encode($dst_category_name) . '/' . $blog_serial;
    if (copy($src, $dst)) {
        return blog_delete($blog_serial, $src_category_name);
    }
    return false;
}

function blog_list(string $search_key = ''): array|false
{
    $search_key = trim($search_key);
    $blogs = [];

    $blog_folder_path = realpath(BLOG_FOLDER);
    if ($blog_folder_path === false) return false;

    $blog_folder = opendir($blog_folder_path);
    if (!$blog_folder) return false;

    while (false !== ($category_name = readdir($blog_folder))) {
        if (!str_starts_with($category_name, 'cate_')) continue;

        $cat_path = realpath(BLOG_FOLDER . '/' . $category_name);
        if ($cat_path === false) continue;

        $category_folder = opendir($cat_path);
        if (!$category_folder) continue;

        while (false !== ($blog_serial = readdir($category_folder))) {
            if (str_starts_with($blog_serial, '.')) continue;

            $arr_serial = blog_parse_serial($blog_serial);
            $decoded_cat = base64_decode(substr($category_name, 5));

            if (!empty($search_key)) {
                $blog = blog_read($blog_serial, $decoded_cat);
                if ($blog === false) continue;
                // Escape regex special chars properly
                $pattern = '/' . preg_quote($search_key, '/') . '/';
                if (preg_match($pattern, $blog['title']) || preg_match($pattern, $blog['content'])) {
                    $blogs[] = ['serial' => $blog['serial'], 'timestamp' => $arr_serial['timestamp'], 'category' => $blog['category']];
                }
            } else {
                $blogs[] = ['serial' => $blog_serial, 'timestamp' => $arr_serial['timestamp'], 'category' => $decoded_cat];
            }
        }
        closedir($category_folder);
    }
    closedir($blog_folder);

    return $blogs;
}

function blog_list_by_category(string $category_name): array|false
{
    $blogs = [];
    $cat_path = realpath(BLOG_FOLDER . '/cate_' . base64_encode($category_name));
    if ($cat_path === false) return false;

    $category_folder = opendir($cat_path);
    if (!$category_folder) return false;

    while (false !== ($blog_serial = readdir($category_folder))) {
        if (str_starts_with($blog_serial, '.')) continue;
        $arr_serial = blog_parse_serial($blog_serial);
        $blogs[] = ['serial' => $blog_serial, 'timestamp' => $arr_serial['timestamp'], 'category' => $category_name];
    }
    closedir($category_folder);
    return $blogs;
}

function blog_list_by_archive(string $archive_date): array|false
{
    $blogs = [];
    $start_stamp = strtotime(date('Y-m-01 00:00:00', strtotime($archive_date)));
    $end_stamp = mktime(0, 0, 0, (int) date('n', strtotime($archive_date)) + 1, 1, (int) date('Y', strtotime($archive_date)));

    $blog_folder_path = realpath(BLOG_FOLDER);
    if ($blog_folder_path === false) return false;
    $blog_folder = opendir($blog_folder_path);
    if (!$blog_folder) return false;

    while (false !== ($category_name = readdir($blog_folder))) {
        if (!str_starts_with($category_name, 'cate_')) continue;
        $cat_path = realpath(BLOG_FOLDER . '/' . $category_name);
        if ($cat_path === false) continue;
        $category_folder = opendir($cat_path);
        if (!$category_folder) continue;

        while (false !== ($blog_serial = readdir($category_folder))) {
            if (str_starts_with($blog_serial, '.')) continue;
            $arr_serial = blog_parse_serial($blog_serial);
            if ((int) $arr_serial['timestamp'] >= $start_stamp && (int) $arr_serial['timestamp'] < $end_stamp) {
                $blogs[] = ['serial' => $blog_serial, 'timestamp' => $arr_serial['timestamp'], 'category' => base64_decode(substr($category_name, 5))];
            }
        }
        closedir($category_folder);
    }
    closedir($blog_folder);
    return $blogs;
}

function archive_list(): array|false
{
    $hash_archive_list = [];
    $arr_archive_list = [];

    $blog_folder_path = realpath(BLOG_FOLDER);
    if ($blog_folder_path === false) return false;
    $blog_folder = opendir($blog_folder_path);
    if (!$blog_folder) return false;

    while (false !== ($category_name = readdir($blog_folder))) {
        if (!str_starts_with($category_name, 'cate_')) continue;
        $cat_path = realpath(BLOG_FOLDER . '/' . $category_name);
        if ($cat_path === false) continue;
        $category_folder = opendir($cat_path);
        if (!$category_folder) continue;

        while (false !== ($blog_serial = readdir($category_folder))) {
            if (str_starts_with($blog_serial, '.')) continue;
            $arr_serial = blog_parse_serial($blog_serial);
            $hash_archive_list[date('M Y', $arr_serial['timestamp'])] = date('M Y', $arr_serial['timestamp']);
        }
        closedir($category_folder);
    }
    closedir($blog_folder);

    foreach ($hash_archive_list as $archive_date) {
        $arr_archive_list[] = $archive_date;
    }
    return $arr_archive_list;
}

/**
 * Generic sort helper: sorts an array of items by 'timestamp' key descending.
 */
function sort_by_timestamp(array $items): array
{
    usort($items, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
    return $items;
}

// Keep these as aliases for backward compat in templates
function blog_sort(array $arr_blog_list): array { return sort_by_timestamp($arr_blog_list); }
function archive_sort(array $arr_archive_list): array
{
    // Archive sort uses strtotime comparison
    usort($arr_archive_list, fn($a, $b) => strtotime($b) <=> strtotime($a));
    return $arr_archive_list;
}
```

---

### Task 1.3: Extract category functions into `lib/category.php`

**Files:**
- Create: `lib/category.php`
- Modify: `index.php` (remove functions)

```php
<?php
declare(strict_types=1);

function category_exists(string $category_name): bool
{
    return is_dir(BLOG_FOLDER . '/cate_' . base64_encode($category_name));
}

function category_create(string $category_name): bool
{
    if (category_exists($category_name)) return false;
    return mkdir(BLOG_FOLDER . '/cate_' . base64_encode($category_name));
}

function category_delete(string $category_name): bool
{
    if ($category_name === 'general') return false;
    $base64_name = base64_encode($category_name);
    $cat_path = BLOG_FOLDER . '/cate_' . $base64_name;

    // Move all blogs to general first
    if ($dh = opendir($cat_path)) {
        while (false !== ($blog_serial = readdir($dh))) {
            if (str_starts_with($blog_serial, '.')) continue;
            blog_move($blog_serial, $category_name, 'general');
        }
        closedir($dh);
    }
    return rmdir($cat_path);
}

function category_list(): array|false
{
    $blog_folder_path = realpath(BLOG_FOLDER);
    if ($blog_folder_path === false) return false;
    $blog_folder = opendir($blog_folder_path);
    if (!$blog_folder) return false;

    $arr_category_names = [];
    while (false !== ($category_name = readdir($blog_folder))) {
        if (str_starts_with($category_name, 'cate_')) {
            $arr_category_names[] = base64_decode(substr($category_name, 5));
        }
    }
    closedir($blog_folder);
    return $arr_category_names;
}
```

---

### Task 1.4: Extract comment functions into `lib/comment.php`

```php
<?php
declare(strict_types=1);

define('COMMENT_FOLDER', 'comment');

function comment_parse_serial(string $comment_serial): array
{
    [$blog_serial, $microtime] = explode('#', $comment_serial);
    [$microsecond, $timestamp] = explode('-', $microtime);

    return [
        'microsecond' => $microsecond,
        'timestamp'   => (int) $timestamp,
        'commenttime' => date('Y-m-d H:i:s', (int) $timestamp),
        'blogserial'  => $blog_serial,
    ];
}

function comment_save(string $comment_serial, string $comment_author, string $comment_content): int|false
{
    $comment_file = COMMENT_FOLDER . '/' . $comment_serial;
    $comment_author = htmlspecialchars($comment_author, ENT_QUOTES, 'UTF-8');
    $comment_content = htmlspecialchars($comment_content, ENT_QUOTES, 'UTF-8');
    return file_put_contents($comment_file, $comment_author . "\n\n" . $comment_content);
}

function comment_create(string $blog_serial, string $comment_author, string $comment_content, string $comment_vcode): int|false
{
    // Vcode check done in calling code
    $comment_serial = $blog_serial . '#' . str_replace(' ', '-', microtime());
    return comment_save($comment_serial, $comment_author, $comment_content);
}

function comment_read(string $comment_serial): array|false
{
    $comment_file = COMMENT_FOLDER . '/' . $comment_serial;
    if (!file_exists($comment_file)) return false;

    $comment_string = file_get_contents($comment_file);
    if ($comment_string === false) return false;

    [$author, $content] = explode("\n\n", $comment_string, 2);
    $arr_serial = comment_parse_serial($comment_serial);

    return [
        'author'      => $author,
        'content'     => $content,
        'serial'      => $comment_serial,
        'commenttime' => $arr_serial['commenttime'],
        'timestamp'   => $arr_serial['timestamp'],
        'blogserial'  => $arr_serial['blogserial'],
    ];
}

function comment_delete(string $comment_serial): bool
{
    if (str_contains($comment_serial, '..') || str_contains($comment_serial, '/')) {
        return false;
    }
    return @unlink(COMMENT_FOLDER . '/' . $comment_serial);
}

function comment_list_by_blog(string $blog_serial): array|false
{
    $comments = [];
    $comment_folder_path = realpath(COMMENT_FOLDER);
    if ($comment_folder_path === false) return false;
    $comment_folder = opendir($comment_folder_path);
    if (!$comment_folder) return false;

    $escaped_serial = preg_quote($blog_serial, '/');
    while (false !== ($comment_serial = readdir($comment_folder))) {
        if (preg_match('/^' . $escaped_serial . '#/', $comment_serial)) {
            $comment = comment_read($comment_serial);
            if ($comment !== false) {
                $comments[] = $comment;
            }
        }
    }
    closedir($comment_folder);
    return $comments;
}

function comment_sort(array $arr_comment_list): array
{
    usort($arr_comment_list, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
    return $arr_comment_list;
}
```

---

### Task 1.5: Extract auth functions into `lib/auth.php`

```php
<?php
declare(strict_types=1);

function admin_check(): bool
{
    global $admin_name, $admin_pwd;

    if (!isset($_SESSION[$_SERVER['HTTP_HOST']]['admin_name'])
        || !isset($_SESSION[$_SERVER['HTTP_HOST']]['admin_pwd'])) {
        return false;
    }

    return $_SESSION[$_SERVER['HTTP_HOST']]['admin_name'] === $admin_name
        && $_SESSION[$_SERVER['HTTP_HOST']]['admin_pwd'] === $admin_pwd;
}

function admin_login(string $login_admin_name, string $login_admin_pwd, string $login_admin_vcode): bool
{
    global $admin_name, $admin_pwd;

    if ($login_admin_vcode !== ($_SESSION[$_SERVER['HTTP_HOST']]['vcode'] ?? '')) {
        return false;
    }

    // Use password_verify for bcrypt migration support
    if ($admin_name === $login_admin_name) {
        // Support both MD5 (legacy) and bcrypt
        if (strlen($admin_pwd) === 32 && ctype_xdigit($admin_pwd)) {
            // Legacy MD5 check
            if (md5($login_admin_pwd) === $admin_pwd) {
                $_SESSION[$_SERVER['HTTP_HOST']]['admin_name'] = $admin_name;
                $_SESSION[$_SERVER['HTTP_HOST']]['admin_pwd'] = $admin_pwd;
                return true;
            }
        } else if (password_verify($login_admin_pwd, $admin_pwd)) {
            $_SESSION[$_SERVER['HTTP_HOST']]['admin_name'] = $admin_name;
            $_SESSION[$_SERVER['HTTP_HOST']]['admin_pwd'] = $admin_pwd;
            return true;
        }
    }
    return false;
}

function admin_logout(): void
{
    unset($_SESSION[$_SERVER['HTTP_HOST']]);
    session_destroy();
}

function referer_check(): bool
{
    if (!isset($_SERVER['HTTP_REFERER'])) return false;

    $referer_host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    if ($referer_host === null) return false;

    if ($referer_host === $_SERVER['HTTP_HOST']) return true;
    if (isset($_SERVER['HTTP_X_FORWARDED_HOST']) && $referer_host === $_SERVER['HTTP_X_FORWARDED_HOST']) return true;

    return false;
}

/**
 * Generate a CSRF token for form protection.
 */
function csrf_token(): string
{
    if (empty($_SESSION[$_SERVER['HTTP_HOST']]['csrf_token'])) {
        $_SESSION[$_SERVER['HTTP_HOST']]['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION[$_SERVER['HTTP_HOST']]['csrf_token'];
}

function csrf_verify(string $token): bool
{
    return hash_equals(
        $_SESSION[$_SERVER['HTTP_HOST']]['csrf_token'] ?? '',
        $token
    );
}
```

---

### Task 1.6: Extract utility functions into `lib/utils.php`

```php
<?php
declare(strict_types=1);

function pager_build_uri(int $page): string
{
    $uri_string = '&amp;p=' . $page;

    // This is called from templates where $_GET/$_POST are available
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

function get_blog_imgs(string $content): array
{
    $content_images = [];
    $blog_images = [];
    preg_match_all('/<img[^>]+?src=["\'](.+?)["\']/i', $content, $content_images);

    if (count($content_images[1]) > 0) {
        foreach ($content_images[1] as $img_src) {
            $blog_images[] = preg_replace('/^' . preg_quote(UPLOAD_FOLDER, '/') . '/', THUMBS_FOLDER, $img_src);
        }
    }
    return $blog_images;
}

function get_blog_short(string $content, int $short_len = 240): string|false
{
    $content = strip_tags($content);
    if (strlen(trim($content)) === 0) return false;
    return mb_substr($content, 0, $short_len, 'UTF-8');
}

function tpl_list(): array|false
{
    $tpl_path = realpath(TPL_FOLDER);
    if ($tpl_path === false) return false;
    $tpl_folder = opendir($tpl_path);
    if (!$tpl_folder) return false;

    $arr_tpl_names = [];
    while (false !== ($tpl_name = readdir($tpl_folder))) {
        if ($tpl_name === '.' || $tpl_name === '..') continue;
        if (is_dir($tpl_path . '/' . $tpl_name)) {
            $arr_tpl_names[] = $tpl_name;
        }
    }
    closedir($tpl_folder);
    return $arr_tpl_names;
}

function lang_list(): array|false
{
    $lang_path = realpath(LANG_FOLDER);
    if ($lang_path === false) return false;
    $lang_folder = opendir($lang_path);
    if (!$lang_folder) return false;

    $arr_lang_names = [];
    while (false !== ($lang_name = readdir($lang_folder))) {
        if ($lang_name === '.' || $lang_name === '..') continue;
        $arr_lang_names[] = str_replace('.php', '', $lang_name);
    }
    closedir($lang_folder);
    return $arr_lang_names;
}

function block_list(): array|false
{
    global $site_tpl;
    $block_path = realpath(TPL_FOLDER . '/' . $site_tpl . '/' . BLOCK_FOLDER);
    if ($block_path === false) return false;
    $block_folder = opendir($block_path);
    if (!$block_folder) return false;

    $arr_block_names = [];
    while (false !== ($block_name = readdir($block_folder))) {
        if ($block_name === '.' || $block_name === '..') continue;
        $arr_block_names[] = str_replace('.php', '', $block_name);
    }
    closedir($block_folder);
    return $arr_block_names;
}

/**
 * Sanitize output for HTML context.
 */
function h(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
```

---

### Task 1.7: Rewrite `index.php` as thin router

**Objective:** index.php becomes a thin router that loads config, includes lib/, and dispatches actions.

```php
<?php
declare(strict_types=1);

// --- Load configuration ---
$config_file = __DIR__ . '/secret.json';
if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
    extract($config);
} else {
    die('Configuration file not found. Copy secret-dist.json to secret.json and edit it.');
}

// Define constants
define('BLOG_FOLDER', __DIR__ . '/blog');
define('COMMENT_FOLDER', __DIR__ . '/comment');
define('TPL_FOLDER', __DIR__ . '/layout');
define('LANG_FOLDER', __DIR__ . '/lang');
define('BLOCK_FOLDER', 'block');
define('PAGE_FOLDER', __DIR__ . '/page');
define('UPLOAD_FOLDER', 'upload');
define('THUMBS_FOLDER', 'thumbs');

// --- Load library ---
require_once __DIR__ . '/lib/blog.php';
require_once __DIR__ . '/lib/category.php';
require_once __DIR__ . '/lib/comment.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/utils.php';

// --- Error handling ---
$sys_err = false;
$sys_err_msg = '';

if (!empty($gzip_output)) {
    ob_start("ob_gzhandler");
}

// --- Session ---
session_start();
if (!isset($_SESSION[$_SERVER['HTTP_HOST']])) {
    $_SESSION[$_SERVER['HTTP_HOST']] = [];
}

// --- Load language ---
require_once LANG_FOLDER . '/' . $site_lang . '.php';

// --- Timezone ---
if (version_compare(PHP_VERSION, '5.1.0', '>') && !empty($your_tz)) {
    if (!date_default_timezone_set($your_tz)) {
        $sys_err = true;
        $sys_err_msg = $lang['tz_error'];
    }
}

// --- Strip magic quotes ---
// (kept for legacy compat but PHP 8 doesn't have magic_quotes anyway)

// --- Ensure general category exists ---
if (!category_exists('general')) {
    category_create('general');
}

// --- Parse parameters ---
$category_name = $_POST['c'] ?? $_GET['c'] ?? false;
$previous_category_name = $_POST['pc'] ?? $_GET['pc'] ?? false;
$archive_date = $_POST['a'] ?? $_GET['a'] ?? false;
$action = $_POST['ac'] ?? $_GET['ac'] ?? false;
$search_key = $_POST['s'] ?? $_GET['s'] ?? false;
$blog_serial = $_POST['b'] ?? $_GET['b'] ?? false;
$forward_page = $_POST['f'] ?? $_GET['f'] ?? false;
$comment_serial = $_POST['bc'] ?? $_GET['bc'] ?? false;
$template_name = $_POST['t'] ?? $_GET['t'] ?? false;
$csrf_input = $_POST['csrf_token'] ?? '';
$curr_page = (int) ($_POST['p'] ?? $_GET['p'] ?? 1);

// --- Optional template override ---
if ($enable_uri_tpl && !empty($template_name)) {
    $tpl_path = realpath(TPL_FOLDER . '/' . $template_name);
    if ($tpl_path !== false && is_dir($tpl_path)) {
        $site_tpl = $template_name;
    }
}

// --- Dispatch ---
switch ($action) {
    case '1': // show login page
        if (admin_check()) {
            header('Location: index.php');
            exit;
        }
        $has_error = isset($_GET['e']);
        $page_title = $lang['admin_login'];
        $page_body = TPL_FOLDER . '/' . $site_tpl . '/login.php';
        break;

    case '2': // do login
        if (!referer_check()) die('Invalid referer');
        if (!csrf_verify($csrf_input)) die('Invalid CSRF token');

        if (admin_login($_POST['admin_name'], $_POST['admin_pwd'], $_POST['admin_vcode'])) {
            if (empty($forward_page)) {
                header('Location: index.php');
            } else {
                header('Location: ' . $forward_page);
            }
            exit;
        }
        header('Location: index.php?ac=1&e=');
        exit;

    case '3': // category form
        if (!admin_check()) {
            header('Location: index.php?ac=1&f=' . urlencode('index.php?ac=3'));
            exit;
        }
        $categories = category_list();
        $page_title = $lang['new_category'];
        $page_body = TPL_FOLDER . '/' . $site_tpl . '/category.php';
        break;

    case '4': // blog form
        if (!admin_check()) {
            header('Location: index.php?ac=1&f=' . urlencode('index.php?ac=4'));
            exit;
        }
        $blog = false;
        if (!empty($blog_serial)) {
            $blog = blog_read($blog_serial, $category_name);
        }
        $categories = category_list();
        $page_blogs = blog_page($curr_page, blog_sort(blog_list('')));
        $blogs = $page_blogs['blogs'];
        $pager_html = $page_blogs['pager'];
        $page_title = $blog ? ($lang['edit'] . ' :: ' . $blog['title']) : $lang['new_blog'];
        $page_body = TPL_FOLDER . '/' . $site_tpl . '/blog.php';
        break;

    case '5': // logout
        if (!referer_check()) die('Invalid referer');
        admin_logout();
        header('Location: index.php');
        exit;

    case '6': // delete category
        if (!referer_check()) die('Invalid referer');
        if (!csrf_verify($csrf_input)) die('Invalid CSRF token');
        if (admin_check() && $category_name !== 'general') {
            category_delete($category_name);
        }
        header('Location: index.php?ac=3');
        exit;

    case '7': // create category
        if (!referer_check()) die('Invalid referer');
        if (!csrf_verify($csrf_input)) die('Invalid CSRF token');
        if (admin_check() && $category_name !== 'general') {
            category_create($category_name);
        }
        header('Location: index.php?ac=3');
        exit;

    case '8': // save blog
        if (!referer_check()) die('Invalid referer');
        if (!csrf_verify($csrf_input)) die('Invalid CSRF token');
        if (admin_check()) {
            if (!empty($blog_serial)) {
                blog_save($blog_serial, $_POST['title'], $_POST['content'], $previous_category_name);
                if ($category_name !== $previous_category_name) {
                    blog_move($blog_serial, $previous_category_name, $category_name);
                }
            } else {
                $blog_serial = blog_create($_POST['title'], $_POST['content'], $category_name);
            }
            header('Location: index.php?b=' . $blog_serial . '&c=' . urlencode($category_name));
            exit;
        }
        header('Location: index.php?ac=1&f=' . urlencode('index.php?ac=4'));
        exit;

    case '9': // delete blog
        if (!referer_check()) die('Invalid referer');
        if (!csrf_verify($csrf_input)) die('Invalid CSRF token');
        if (admin_check()) {
            blog_delete($blog_serial, $category_name);
        }
        header('Location: index.php?ac=4');
        exit;

    case '10': // add comment
        if (!referer_check()) die('Invalid referer');
        if (!csrf_verify($csrf_input)) die('Invalid CSRF token');

        $err_code = '';
        $comment_vcode = $_POST['comment_vcode'] ?? '';
        if ($comment_vcode !== ($_SESSION[$_SERVER['HTTP_HOST']]['vcode'] ?? '')) {
            $err_code = '&e=comment_failed';
        } elseif ($blog_serial && !comment_create($blog_serial, $_POST['comment_author'], $_POST['comment_content'], $comment_vcode)) {
            $err_code = '&e=comment_failed';
        }
        header('Location: index.php?b=' . $blog_serial . '&c=' . $category_name . $err_code);
        exit;

    case '11': // delete comment
        if (!referer_check()) die('Invalid referer');
        if (!csrf_verify($csrf_input)) die('Invalid CSRF token');
        if (admin_check()) {
            comment_delete($comment_serial);
        }
        header('Location: index.php?b=' . $blog_serial . '&c=' . $category_name);
        exit;

    case '12': // settings form
        if (!admin_check()) {
            header('Location: index.php?ac=1&f=' . urlencode('index.php?ac=12'));
            exit;
        }
        $has_error = isset($_GET['e']);
        $tpls = tpl_list();
        $langs = lang_list();
        $avail_blocks = block_list();
        $avail_blocks_str = $avail_blocks ? implode(',', $avail_blocks) : '';
        $block_str = !empty($blocks) ? implode(',', $blocks) : '';
        $page_title = $lang['general_settings'];
        $page_body = TPL_FOLDER . '/' . $site_tpl . '/settings.php';
        break;

    case '13': // save settings
        if (!referer_check()) die('Invalid referer');
        if (!csrf_verify($csrf_input)) die('Invalid CSRF token');
        if (admin_check()) {
            $new_blocks = array_map('trim', explode(',', $_POST['blocks']));

            $new_admin_pwd = $admin_pwd; // keep existing
            if (!empty($_POST['admin_pwd'])) {
                if ($_POST['admin_pwd'] === $_POST['confirm_admin_pwd']) {
                    $new_admin_pwd = password_hash($_POST['admin_pwd'], PASSWORD_BCRYPT);
                } else {
                    header('Location: index.php?ac=12&e=');
                    exit;
                }
            }

            $new_config = [
                'site_name'       => $_POST['site_name'],
                'site_slogan'     => $_POST['site_slogan'],
                'site_tpl'        => $_POST['site_tpl'],
                'site_lang'       => $_POST['site_lang'],
                'admin_name'      => $_POST['admin_name'],
                'admin_pwd'       => $new_admin_pwd,
                'admin_email'     => $_POST['admin_email'],
                'blogs_per_page'  => (int) $_POST['blogs_per_page'],
                'enable_uri_tpl'  => ($_POST['enable_uri_tpl'] ?? '0') === '1',
                'context_root'    => $_POST['context_root'],
                'gzip_output'     => ($_POST['gzip_output'] ?? '0') === '1',
                'your_tz'         => trim($_POST['your_tz']),
                'blocks'          => $new_blocks,
            ];

            file_put_contents('secret.json', json_encode($new_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: index.php?ac=12');
            exit;
        }
        header('Location: index.php?ac=1&f=' . urlencode('index.php?ac=12'));
        exit;

    default: // blog list / single blog view
        $has_error = isset($_GET['e']);
        $error_msg = $_GET['e'] ?? '';

        if (empty($blog_serial)) {
            // Blog list
            $page_title = $lang['home'];
            if (empty($category_name) && empty($archive_date)) {
                $blogs = blog_list($search_key);
                if (strlen(trim($search_key)) > 0) {
                    $page_title = $lang['search'] . ' :: ' . h($search_key);
                }
            } elseif (!empty($category_name)) {
                $blogs = blog_list_by_category($category_name);
                $page_title = $lang['category'] . ' :: ' . h($category_name);
            } elseif (!empty($archive_date)) {
                $blogs = blog_list_by_archive($archive_date);
                $page_title = $lang['archive'] . ' :: ' . h($archive_date);
            }

            $page_blogs = blog_page($curr_page, blog_sort($blogs));
            $blogs = $page_blogs['blogs'];
            $pager_html = $page_blogs['pager'];
            $page_body = TPL_FOLDER . '/' . $site_tpl . '/main.php';
        } else {
            // Single blog view — use highlight.js instead of GeSHi
            $blog = blog_read($blog_serial, $category_name);

            $comments = comment_list_by_blog($blog_serial);
            $comments = comment_sort($comments);

            $page_title = $blog['title'];
            $page_body = TPL_FOLDER . '/' . $site_tpl . '/blog_comment.php';
        }
        break;
}

// --- Render ---
require_once TPL_FOLDER . '/' . $site_tpl . '/layout.php';
```

---

### Task 1.8: Create JSON config template and migration script

**Create `secret-dist.json`:**
```json
{
    "site_name": "ESiBlog",
    "site_slogan": "Ever Simple Blog",
    "site_tpl": "metrohacker",
    "site_lang": "en",
    "admin_name": "admin",
    "admin_pwd": "$2y$10$...generated...",
    "admin_email": "admin@yourdomain.tld",
    "blogs_per_page": 15,
    "enable_uri_tpl": true,
    "context_root": "/",
    "gzip_output": true,
    "your_tz": "",
    "blocks": ["category", "archive", "search", "syndication"]
}
```

**Create migration script `migrate-config.php`:**
```php
<?php
// One-time script: migrates secret.php to secret.json
if (!file_exists('secret.php')) {
    die("secret.php not found. Nothing to migrate.\n");
}
include 'secret.php';

$config = [
    'site_name'       => $site_name,
    'site_slogan'     => $site_slogan,
    'site_tpl'        => $site_tpl,
    'site_lang'       => $site_lang,
    'admin_name'      => $admin_name,
    'admin_pwd'       => $admin_pwd,
    'admin_email'     => $admin_email,
    'blogs_per_page'  => $blogs_per_page,
    'enable_uri_tpl'  => $enable_uri_tpl,
    'context_root'    => $context_root,
    'gzip_output'     => $gzip_output,
    'your_tz'         => $your_tz,
    'blocks'          => $blocks,
];

file_put_contents('secret.json', json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Migrated to secret.json\n";
echo "Password is still MD5. It will work, but you should change password via admin panel to upgrade to bcrypt.\n";
```

---

### Task 1.9: Rewrite `rss.php` to use lib

**Objective:** Remove all duplicated functions from `rss.php`, include lib instead.

```php
<?php
declare(strict_types=1);

define('BLOG_FOLDER', __DIR__ . '/blog');
require_once __DIR__ . '/lib/blog.php';
require_once __DIR__ . '/lib/utils.php';

$config = json_decode(file_get_contents(__DIR__ . '/secret.json'), true);
extract($config);

$page_blogs = blog_page(1, blog_sort(blog_list()));
rss_create($page_blogs['blogs']);

function rss_create(array $arr_blog_list): void
{
    global $site_name, $site_slogan, $context_root;

    header('Content-Type: application/rss+xml; charset=UTF-8');
    echo '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
    echo "<rss version=\"2.0\">\n";
    echo "<channel>\n";
    echo "<title><![CDATA[$site_name]]></title>\n";
    echo "<description><![CDATA[$site_slogan]]></description>\n";
    echo "<link>http://{$_SERVER['HTTP_HOST']}{$context_root}</link>\n";

    foreach ($arr_blog_list as $blog) {
        $safe_title = htmlspecialchars($blog['title'], ENT_XML1, 'UTF-8');
        $safe_content = htmlspecialchars($blog['content'], ENT_XML1, 'UTF-8');
        $safe_category = htmlspecialchars($blog['category'], ENT_XML1, 'UTF-8');
        $link = "http://{$_SERVER['HTTP_HOST']}{$context_root}index.php?b={$blog['serial']}&amp;c={$blog['category']}";

        echo "<item>\n";
        echo "<title><![CDATA[{$blog['title']}]]></title>\n";
        echo "<link>$link</link>\n";
        echo "<description><![CDATA[{$blog['content']}]]></description>\n";
        echo "<category><![CDATA[{$blog['category']}]]></category>\n";
        echo "<comments>$link</comments>\n";
        echo "<pubDate>" . date('r', $blog['timestamp']) . "</pubDate>\n";
        echo "</item>\n";
    }
    echo "</channel>\n";
    echo "</rss>";
}
```

---

## Phase 2: Upgrade Third-Party Dependencies

### Task 2.1: Replace GeSHi with highlight.js

**Objective:** Remove the `geshi/` directory (huge, abandoned since 2018) and use highlight.js CDN or local bundle instead.

**Steps:**
1. Delete `geshi/` directory entirely
2. Remove `reformat_callback()` and `reformat_embeded_code()` from index.php
3. Add highlight.js to layout:
   - In `layout.php`, add: `<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">`
   - Add: `<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>`
   - Add: `<script>hljs.highlightAll();</script>`
4. Blog content uses standard `<pre><code class="language-xxx">` markup (backward compat: keep supporting `<pre class="_geshi_xxx">` by converting it in JS or PHP on the fly)

**Code to add in `layout.php` (before `</body>`):**
```html
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script>
// Convert legacy GeSHi format to highlight.js format
document.querySelectorAll('pre[class^="_geshi_"]').forEach(function(pre) {
    var lang = pre.className.replace('_geshi_', '');
    var code = pre.querySelector('code') || document.createElement('code');
    code.className = 'language-' + lang;
    if (!pre.querySelector('code')) {
        code.textContent = pre.textContent;
        pre.textContent = '';
        pre.appendChild(code);
    }
    pre.className = '';
});
hljs.highlightAll();
</script>
```

---

### Task 2.2: Upgrade TinyMCE 4 → TinyMCE 7

**Objective:** Replace local TinyMCE 4 files with CDN-hosted TinyMCE 7.

**Steps:**
1. Delete `tiny_mce/` directory entirely (huge, ~1000+ files)
2. Update `layout/{theme}/blog.php` editor initialization to use CDN:
```html
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.6.0/tinymce.min.js"></script>
<script>
tinymce.init({
    selector: '#content',
    plugins: 'link image media table code fullscreen emoticons charmap anchor',
    toolbar: 'undo redo | blocks fontsize | bold italic underline strikethrough | link image media | table | align | bullist numlist | emoticons charmap | code fullscreen',
    menubar: false,
    image_advtab: true,
});
</script>
```
3. Remove the old `toggleEditor` function and "toggle editor" link if desired (or simplify)
4. Remove `filemanager/` directory (Responsive Filemanager is abandoned since 2019) — replace with TinyMCE 7's built-in image/link plugins
5. Remove `responsivefilemanager` plugin references

---

### Task 2.3: Upgrade jQuery 1.11.3 → jQuery 3.7.x or drop it

**Objective:** jQuery 1.x is ancient. We only use it for 2 AJAX calls (vcode loading).

**Option A (recommended):** Drop jQuery entirely, use vanilla fetch().
Replace in login.php and blog_comment.php:
```javascript
// Old:
$.get("vcode.php", function(data){
  $("#vcode").append(data);
});

// New:
fetch("vcode.php")
  .then(r => r.text())
  .then(html => { document.getElementById("vcode").innerHTML = html; });
```

**Option B:** Update to jQuery 3.7.1 CDN.
```html
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
```

---

## Phase 3: Template & Security Fixes

### Task 3.1: Fix XSS in templates

**Objective:** Add proper HTML escaping to all template outputs.

**Files to update:**
- `layout/metrohacker/main.php` — wrap `$blogs[$i]['title']`, `$blogs[$i]['category']` in `h()`
- `layout/metrohacker/blog_comment.php` — wrap `$blog['title']`, `$blog['category']`, comment fields in `h()`
- `layout/metrohacker/layout.php` — wrap `$site_name`, `$site_slogan` in `h()`
- `layout/metrohacker/login.php` — wrap `$forward_page` in `h()`
- All block templates
- Same for metroscarlet theme

**Example fix in `main.php`:**
```php
<!-- Before -->
<h1><?php echo $blogs[$i]['title']; ?></h1>

<!-- After -->
<h1><?php echo h($blogs[$i]['title']); ?></h1>
```

---

### Task 3.2: Add CSRF tokens to forms

**Objective:** Add hidden CSRF token field to all forms (login, save blog, delete blog, delete category, add comment, save settings).

**In `layout.php`:**
```php
<input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
```
Added inside each `<form>` tag.

---

### Task 3.3: Fix path traversal in file operations

**Objective:** Add validation to all file-path-using functions.

Wherever user input goes into a file path, validate it:
```php
if (str_contains($input, '..') || str_contains($input, '/') || str_contains($input, "\0")) {
    return false;
}
```

Already done in blog_delete(), comment_delete() in the lib versions above. Verify all other usages.

---

### Task 3.4: Update MatrixVCode for PHP 8

**Objective:** Fix deprecated syntax.

- Replace `var $_char_matrix` with `private array $_char_matrix`
- Replace `var $_chars` with `private string $_chars`
- Replace `var $_rand_len` with `private int $_rand_len`
- Replace `function _randChars()` with `private function _randChars(): string`
- Replace `function &_genBinaryMatrix(...)` with `private function _genBinaryMatrix(string $rand_s): array` (remove reference return)

---

### Task 3.5: Update block template files

**Files to update:**
- `layout/metrohacker/block/category.php` — add `h()` escaping
- `layout/metrohacker/block/archive.php` — add `h()` escaping
- `layout/metrohacker/block/search.php` — review
- `layout/metrohacker/block/syndication.php` — review
- Same for `layout/metroscarlet/block/`

---

## Phase 4: Cleanup & Final Polish

### Task 4.1: Remove legacy files

```bash
rm -rf geshi/
rm -rf tiny_mce/
rm -rf filemanager/
rm -f secret-dist.php
rm -f jquery-min.js
rm -f .buildpath .project  # Eclipse project files
```

### Task 4.2: Add .htaccess for clean URLs (optional)

**Create `.htaccess`:**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

### Task 4.3: Verify PHP 8.x compatibility

```bash
php -l lib/*.php
php -l index.php
php -l rss.php
php -l vcode.php
```

### Task 4.4: Update README

Update installation instructions to reflect:
- Copy `secret-dist.json` to `secret.json`
- Run `php migrate-config.php` if upgrading from old version
- PHP 8.1+ requirement
- No more manual `chmod 777` (use proper permissions)

---

## Order of Execution

1. Task 1.1: Create lib directory
2. Task 1.8: Create config template (needed by other tasks)
3. Task 1.2-1.6: Extract lib files (blog, category, comment, auth, utils)
4. Task 1.7: Rewrite index.php
5. Task 1.9: Rewrite rss.php
6. Task 3.4: Update MatrixVCode.php
7. Task 2.1: Replace GeSHi → highlight.js
8. Task 2.2: Upgrade TinyMCE → CDN v7
9. Task 2.3: Drop jQuery → vanilla JS
10. Task 3.1-3.3: Security fixes in templates
11. Task 3.5: Update block templates
12. Task 4.1-4.4: Cleanup & polish
