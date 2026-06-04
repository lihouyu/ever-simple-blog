<?php
declare(strict_types=1);

/**
 * ESiBlog — Ever Simple Blog
 *
 * Flat-file PHP blog engine. Single-file router that loads the
 * configuration, includes the library, and dispatches to templates.
 *
 * Requires PHP 8.1+
 */

// ── Load configuration ─────────────────────────────────────────────
$config_file = __DIR__ . '/secret.json';
if (!file_exists($config_file)) {
    if (file_exists(__DIR__ . '/secret.php')) {
        die('Legacy secret.php found. Run <code>php migrate-config.php</code> to upgrade.');
    }
    die('Configuration not found. Copy <code>secret-dist.json</code> to <code>secret.json</code> and edit it.');
}

$config = json_decode(file_get_contents($config_file), true);
if (!is_array($config)) {
    die('Invalid secret.json — check JSON syntax.');
}

$site_name       = (string) ($config['site_name']       ?? 'ESiBlog');
$site_slogan     = (string) ($config['site_slogan']     ?? '');
$site_tpl        = (string) ($config['site_tpl']        ?? 'metrohacker');
$site_lang       = (string) ($config['site_lang']       ?? 'en');
$admin_name      = (string) ($config['admin_name']      ?? 'admin');
$admin_pwd       = (string) ($config['admin_pwd']       ?? '');
$admin_email     = (string) ($config['admin_email']     ?? '');
$blogs_per_page  = (int)    ($config['blogs_per_page']  ?? 15);
$enable_uri_tpl  = (bool)   ($config['enable_uri_tpl']  ?? false);
$context_root    = (string) ($config['context_root']    ?? '/');
$gzip_output     = (bool)   ($config['gzip_output']     ?? false);
$your_tz         = (string) ($config['your_tz']         ?? '');
$blocks          = (array)  ($config['blocks']          ?? []);

// ── Path constants ──────────────────────────────────────────────────
define('BLOG_FOLDER',    __DIR__ . '/blog');
define('COMMENT_FOLDER', __DIR__ . '/comment');
define('TPL_FOLDER',     __DIR__ . '/layout');
define('LANG_FOLDER',    __DIR__ . '/lang');
define('BLOCK_FOLDER',   'block');
define('UPLOAD_FOLDER',  'upload');
define('THUMBS_FOLDER',  'thumbs');

// ── Load library ────────────────────────────────────────────────────
require_once __DIR__ . '/lib/blog.php';
require_once __DIR__ . '/lib/category.php';
require_once __DIR__ . '/lib/comment.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/utils.php';

// ── Bootstrap ───────────────────────────────────────────────────────
$sys_err     = false;
$sys_err_msg = '';

// Secure session cookies
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}

if ($gzip_output) {
    ob_start('ob_gzhandler');
}

session_start();
define('SESSION_NS', 'esiblog');
if (!isset($_SESSION[SESSION_NS])) {
    $_SESSION[SESSION_NS] = [];
}

// Load language pack
$lang_file = LANG_FOLDER . '/' . $site_lang . '.php';
if (!file_exists($lang_file)) {
    $lang_file = LANG_FOLDER . '/en.php';
}
require_once $lang_file;

// Set timezone
if (!empty($your_tz) && !date_default_timezone_set($your_tz)) {
    $sys_err     = true;
    $sys_err_msg = $lang['tz_error'] ?? 'Invalid timezone.';
}

// Ensure default category exists
if (!category_exists('general')) {
    category_create('general');
}

// ── Parse request parameters ────────────────────────────────────────
$category_name          = $_POST['c']  ?? $_GET['c']  ?? '';
$previous_category_name = $_POST['pc'] ?? $_GET['pc'] ?? '';
$archive_date           = $_POST['a']  ?? $_GET['a']  ?? '';
$action                 = $_POST['ac'] ?? $_GET['ac'] ?? '';
$search_key             = mb_substr($_POST['s']  ?? $_GET['s']  ?? '', 0, 200, 'UTF-8');
$blog_serial            = $_POST['b']  ?? $_GET['b']  ?? '';
$forward_page           = $_POST['f']  ?? $_GET['f']  ?? '';
$comment_serial         = $_POST['bc'] ?? $_GET['bc'] ?? '';
$template_name          = $_POST['t']  ?? $_GET['t']  ?? '';
$csrf_input             = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
$curr_page              = (int) ($_POST['p'] ?? $_GET['p'] ?? 1);

// Optional template override via URL
if ($enable_uri_tpl && $template_name !== '') {
    $tpl_path = realpath(TPL_FOLDER . '/' . $template_name);
    if ($tpl_path !== false && is_dir($tpl_path)) {
        $site_tpl = $template_name;
    }
}

// ── Route ───────────────────────────────────────────────────────────
switch ($action) {

    // ── ac=1 : Show login form ──────────────────────────────────
    case '1':
        if (admin_check()) {
            header('Location: index.php');
            exit;
        }
        $has_error  = isset($_GET['e']);
        $page_title = $lang['admin_login'];
        $page_body  = TPL_FOLDER . '/' . $site_tpl . '/login.php';
        break;

    // ── ac=2 : Process login ────────────────────────────────────
    case '2':
        if (!csrf_verify($csrf_input)) {
            die('Invalid security token.');
        }

        if (admin_login(
            $_POST['admin_name']  ?? '',
            $_POST['admin_pwd']   ?? '',
            $_POST['admin_vcode'] ?? ''
        )) {
            $redirect = ($forward_page !== '') ? $forward_page : 'index.php';
            header('Location: ' . $redirect);
            exit;
        }
        header('Location: index.php?ac=1&e=');
        exit;

    // ── ac=3 : Category management form ─────────────────────────
    case '3':
        if (!admin_check()) {
            header('Location: index.php?ac=1&f=' . urlencode('index.php?ac=3'));
            exit;
        }
        $categories = category_list();
        $page_title = $lang['new_category'];
        $page_body  = TPL_FOLDER . '/' . $site_tpl . '/category.php';
        break;

    // ── ac=4 : Blog editor (create / edit) ──────────────────────
    case '4':
        if (!admin_check()) {
            header('Location: index.php?ac=1&f=' . urlencode('index.php?ac=4'));
            exit;
        }
        $blog = false;
        if ($blog_serial !== '' && $category_name !== '') {
            $blog = blog_read($blog_serial, $category_name);
            if ($blog !== false) {
                $blog['content'] = recode_code_blocks($blog['content']);
            }
        }

        $categories  = category_list();
        $all_blogs   = blog_list('');
        $page_blogs  = blog_page($curr_page, blog_sort($all_blogs ?: []));
        $blogs       = $page_blogs['blogs'];
        $pager_html  = $page_blogs['pager'];

        $page_title = $blog
            ? $lang['edit'] . ' :: ' . $blog['title']
            : $lang['new_blog'];
        $page_body = TPL_FOLDER . '/' . $site_tpl . '/blog.php';
        break;

    // ── ac=5 : Logout ───────────────────────────────────────────
    case '5':
        admin_logout();
        header('Location: index.php');
        exit;

    // ── ac=6 : Delete category ──────────────────────────────────
    case '6':
        if (!csrf_verify($csrf_input)) {
            die('Invalid security token.');
        }

        if (admin_check() && $category_name !== '' && $category_name !== 'general') {
            category_delete($category_name);
        }
        header('Location: index.php?ac=3');
        exit;

    // ── ac=7 : Create category ──────────────────────────────────
    case '7':
        if (!csrf_verify($csrf_input)) {
            die('Invalid security token.');
        }

        if (admin_check() && $category_name !== '' && $category_name !== 'general') {
            category_create($category_name);
        }
        header('Location: index.php?ac=3');
        exit;

    // ── ac=8 : Save blog ────────────────────────────────────────
    case '8':
        if (!csrf_verify($csrf_input)) {
            die('Invalid security token.');
        }

        if (admin_check()) {
            $title   = mb_substr($_POST['title']   ?? '', 0, 500, 'UTF-8');
            $content = $_POST['content'] ?? '';

            if ($blog_serial !== '') {
                blog_save($blog_serial, $title, $content, $previous_category_name);
                if ($category_name !== '' && $category_name !== $previous_category_name) {
                    blog_move($blog_serial, $previous_category_name, $category_name);
                }
            } else {
                $blog_serial = blog_create($title, $content, $category_name !== '' ? $category_name : 'general');
            }

            header('Location: index.php?b=' . urlencode($blog_serial) . '&c=' . urlencode($category_name));
            exit;
        }
        header('Location: index.php?ac=1&f=' . urlencode('index.php?ac=4'));
        exit;

    // ── ac=9 : Delete blog ──────────────────────────────────────
    case '9':
        if (!csrf_verify($csrf_input)) {
            die('Invalid security token.');
        }

        if (admin_check() && $blog_serial !== '' && $category_name !== '') {
            blog_delete($blog_serial, $category_name);
        }
        header('Location: index.php?ac=4');
        exit;

    // ── ac=10 : Add comment ─────────────────────────────────────
    case '10':
        if (!csrf_verify($csrf_input)) {
            die('Invalid security token.');
        }

        $err_code = '';
        $comment_vcode = $_POST['comment_vcode'] ?? '';

        // Length limits
        $author  = mb_substr($_POST['comment_author']  ?? '', 0, 100, 'UTF-8');
        $content = mb_substr($_POST['comment_content'] ?? '', 0, 5000, 'UTF-8');

        if ($comment_vcode !== ($_SESSION[SESSION_NS]['vcode'] ?? '')) {
            $err_code = '&e=comment_failed';
        } elseif ($blog_serial !== '') {
            $result = comment_create(
                $blog_serial,
                $author,
                $content,
                $comment_vcode
            );
            if ($result === false) {
                $err_code = '&e=comment_failed';
            }
        }

        header('Location: index.php?b=' . urlencode($blog_serial) . '&c=' . urlencode($category_name) . $err_code);
        exit;

    // ── ac=11 : Delete comment ──────────────────────────────────
    case '11':
        if (!csrf_verify($csrf_input)) {
            die('Invalid security token.');
        }

        if (admin_check() && $comment_serial !== '') {
            comment_delete($comment_serial);
        }
        header('Location: index.php?b=' . urlencode($blog_serial) . '&c=' . urlencode($category_name));
        exit;

    // ── ac=12 : Settings form ───────────────────────────────────
    case '12':
        if (!admin_check()) {
            header('Location: index.php?ac=1&f=' . urlencode('index.php?ac=12'));
            exit;
        }

        $has_error        = isset($_GET['e']);
        $tpls             = tpl_list();
        $langs            = lang_list();
        $avail_blocks     = block_list();
        $avail_blocks_str = is_array($avail_blocks) ? implode(',', $avail_blocks) : '';
        $block_str        = !empty($blocks) ? implode(',', $blocks) : '';

        $page_title = $lang['general_settings'];
        $page_body  = TPL_FOLDER . '/' . $site_tpl . '/settings.php';
        break;

    // ── ac=13 : Save settings ───────────────────────────────────
    case '13':
        if (!csrf_verify($csrf_input)) {
            die('Invalid security token.');
        }

        if (admin_check()) {
            $new_admin_pwd = $admin_pwd;
            $submitted_pwd = $_POST['admin_pwd'] ?? '';
            if ($submitted_pwd !== '') {
                if ($submitted_pwd === ($_POST['confirm_admin_pwd'] ?? '')) {
                    $new_admin_pwd = password_hash($submitted_pwd, PASSWORD_BCRYPT);
                } else {
                    header('Location: index.php?ac=12&e=');
                    exit;
                }
            }

            $new_blocks = array_filter(
                array_map('trim', explode(',', $_POST['blocks'] ?? '')),
                fn(string $s): bool => $s !== ''
            );

            $new_config = [
                'site_name'      => $_POST['site_name']      ?? $site_name,
                'site_slogan'    => $_POST['site_slogan']    ?? $site_slogan,
                'site_tpl'       => $_POST['site_tpl']       ?? $site_tpl,
                'site_lang'      => $_POST['site_lang']      ?? $site_lang,
                'admin_name'     => $_POST['admin_name']     ?? $admin_name,
                'admin_pwd'      => $new_admin_pwd,
                'admin_email'    => $_POST['admin_email']    ?? $admin_email,
                'blogs_per_page' => (int) ($_POST['blogs_per_page'] ?? $blogs_per_page),
                'enable_uri_tpl' => ($_POST['enable_uri_tpl'] ?? '0') === '1',
                'context_root'   => $_POST['context_root']   ?? $context_root,
                'gzip_output'    => ($_POST['gzip_output']   ?? '0') === '1',
                'your_tz'        => trim($_POST['your_tz']    ?? $your_tz),
                'blocks'         => array_values($new_blocks),
            ];

            file_put_contents(
                __DIR__ . '/secret.json',
                json_encode($new_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
            );

            header('Location: index.php?ac=12');
            exit;
        }
        header('Location: index.php?ac=1&f=' . urlencode('index.php?ac=12'));
        exit;

    // ── ac=14 : Image manager ───────────────────────────────────
    case '14':
        if (!admin_check()) {
            header('Location: index.php?ac=1&f=' . urlencode('index.php?ac=14'));
            exit;
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
                            'name' => $file,
                            'size' => filesize($upload_dir . $file),
                            'url'  => 'upload/' . $file,
                            'time' => filemtime($upload_dir . $file),
                        ];
                    }
                }
                closedir($dh);
            }
        }

        $has_deleted = isset($_GET['d']);
        $delete_error = isset($_GET['e']);

        $page_title = $lang['image_manager'];
        $page_body  = TPL_FOLDER . '/' . $site_tpl . '/images.php';
        break;

    // ── ac=15 : Delete image ───────────────────────────────────
    case '15':
        if (!csrf_verify($csrf_input)) die('Invalid security token.');
        if (!admin_check()) { header('Location: index.php?ac=1'); exit; }

        $img = basename($_GET['img'] ?? '');
        $path = __DIR__ . '/upload/' . $img;
        if ($img !== '' && file_exists($path) && is_file($path)) {
            @unlink($path);
            // Also delete thumbnail
            $thumb = __DIR__ . '/thumbs/' . $img;
            if (file_exists($thumb)) @unlink($thumb);
            header('Location: index.php?ac=14&d=');
        } else {
            header('Location: index.php?ac=14&e=');
        }
        exit;

    // ── default : Blog list or single post view ─────────────────
    default:
        $has_error = isset($_GET['e']);
        $error_msg = $_GET['e'] ?? '';

        if ($blog_serial === '') {
            $page_title = $lang['home'];

            if ($archive_date !== '') {
                $blogs      = blog_list_by_archive($archive_date);
                $page_title = $lang['archive'] . ' :: ' . h($archive_date);
            } elseif ($category_name !== '') {
                $blogs      = blog_list_by_category($category_name);
                $page_title = $lang['category'] . ' :: ' . h($category_name);
            } else {
                $blogs = blog_list($search_key);
                if (trim($search_key) !== '') {
                    $page_title = $lang['search'] . ' :: ' . h($search_key);
                }
            }

            $page_blogs = blog_page($curr_page, blog_sort($blogs ?: []));
            $blogs      = $page_blogs['blogs'];
            $pager_html = $page_blogs['pager'];

            $page_body = TPL_FOLDER . '/' . $site_tpl . '/main.php';
        } else {
            $blog = blog_read($blog_serial, $category_name);
            if ($blog === false) {
                header('HTTP/1.0 404 Not Found');
                $page_title = '404 — Not Found';
                $page_body  = false;
                break;
            }
            $blog['content'] = sanitize_html($blog['content']);

            $comments = comment_sort(comment_list_by_blog($blog_serial) ?: []);

            $page_title = $blog['title'];
            $page_body  = TPL_FOLDER . '/' . $site_tpl . '/blog_comment.php';
        }
        break;
}

// ── Render layout ───────────────────────────────────────────────────
if ($page_body !== false && file_exists(TPL_FOLDER . '/' . $site_tpl . '/layout.php')) {
    require_once TPL_FOLDER . '/' . $site_tpl . '/layout.php';
} elseif ($page_body === false) {
    echo '<h1>404 — Not Found</h1><p><a href="index.php">Go home</a></p>';
}
