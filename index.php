<?php
// TODO: add file include validation to every php script
//error_reporting(0);

include_once 'secret.php';

$sys_err = false;
$sys_err_msg = '';

if ($gzip_output) {
    ob_start("ob_gzhandler");
}

session_start();
if (!isset($_SESSION[$_SERVER['HTTP_HOST']])) {
    $_SESSION[$_SERVER['HTTP_HOST']] = array();
}

define('BLOG_FOLDER', 'blog');
define('COMMENT_FOLDER', 'comment');
define('TPL_FOLDER', 'layout');
define('LANG_FOLDER', 'lang');
define('BLOCK_FOLDER', 'block');
define('PAGE_FOLDER', 'page');
define('UPLOAD_FOLDER', 'upload');
define('THUMBS_FOLDER', 'thumbs');

function page_save($page_serial, $page_title, $page_content)
{
    $page_file = PAGE_FOLDER.'/'.$page_serial;
    $page_title = htmlspecialchars($page_title);

    $write_page_rs = file_put_contents($page_file,
        $page_title."\n\n".$page_content);

    return $write_page_rs;
}

function page_create($page_title, $page_content)
{
    $page_serial = str_replace(' ', '-', microtime()).'.'.date('Y-m-d');

    return blog_save($page_serial, $page_title, $page_content);
}

function page_read($page_serial)
{
    $page_file = PAGE_FOLDER.'/'.$page_serial;
    if (file_exists($page_file))
    {
        $page = array();

        $page_string = file_get_contents($page_file);

        list($page['title'], $page['content']) =
            explode("\n\n", $page_string, 2);
        $arr_serial = page_parse_serial($page_serial);
        $page['serial'] = $page_serial;
        $page['publishtime'] = $arr_serial['publishtime'];
        $page['timestamp'] = $arr_serial['timestamp'];

        return $page;
    }
    else
    {
        return false;
    }
}

function page_list()
{
    $pages = array();

    if ($page_folder = opendir(realpath(PAGE_FOLDER)))
    {
        while (false !== ($page_serial = readdir($page_folder)))
        {
            if (preg_match('/^\./', $page_serial))
            {
                continue;
            }

            $arr_serial = page_parse_serial($page_serial);
            $pages[] = array('serial' => $page_serial,
                'timestamp' => $arr_serial['timestamp']);
        }
        closedir($page_folder);

        return $pages;
    }
    else
    {
        return false;
    }
}

function page_delete($page_serial)
{
    return unlink(PAGE_FOLDER.'/'.$page_serial);
}

function page_parse_serial($page_serial)
{
    list($microsecond, $serial) = explode('-', $page_serial, 2);
    list($timestamp, $archivedate) = explode('.', $serial, 2);

    $arr_serial['microsecond'] = $microsecond;
    $arr_serial['timestamp'] = $timestamp;
    $arr_serial['publishtime'] = date('Y-m-d H:i:s', $timestamp);
    $arr_serial['archivedate'] = $archivedate;

    return $arr_serial;
}

function page_sort($arr_page_list) {
    if (!$arr_page_list) return false;

    $list_length = count($arr_page_list);
    for ($i = 1; $i <= $list_length; $i++)
    {
        for ($j = $list_length - 1; $j >= $i; $j--)
        {
            if ($arr_page_list[$j]['timestamp']
                > $arr_page_list[$j - 1]['timestamp'])
            {
                $swap = $arr_page_list[$j - 1];
                $arr_page_list[$j - 1] = $arr_page_list[$j];
                $arr_page_list[$j] = $swap;
            }
        }
    }

    return $arr_page_list;
}

function category_exists($category_name)
{
    return file_exists(realpath(BLOG_FOLDER.'/cate_'.base64_encode($category_name)));
}

function category_create($category_name)
{
    if (!category_exists($category_name))
    {
        return mkdir(BLOG_FOLDER.'/cate_'.base64_encode($category_name));
    }
    else
    {
        return false;
    }
}

function category_delete($category_name)
{
    $base64_category_name = base64_encode($category_name);

    if ($category_folder = opendir(realpath(BLOG_FOLDER.'/cate_'.$base64_category_name)))
    {
        while (false !== ($blog_serial = readdir($category_folder)))
        {
            if (preg_match('/^\./', $blog_serial))
            {
                continue;
            }

            blog_move($blog_serial, $category_name, 'general');
        }
        closedir($category_folder);

        return rmdir(BLOG_FOLDER.'/cate_'.$base64_category_name);
    }
    else
    {
        return false;
    }
}

function category_list()
{
    if ($blog_folder = opendir(realpath(BLOG_FOLDER)))
    {
        $arr_category_names = array();
        while (false !== ($category_name = readdir($blog_folder)))
        {
            if (preg_match('/^cate_/', $category_name))
            {
                $arr_category_names[] = base64_decode(substr($category_name, 5));
            }
        }
        closedir($blog_folder);

        return $arr_category_names;
    }
    else
    {
        return false;
    }
}

function blog_parse_serial($blog_serial)
{
    list($microsecond, $serial) = explode('-', $blog_serial, 2);
    list($timestamp, $archivedate) = explode('.', $serial, 2);

    $arr_serial['microsecond'] = $microsecond;
    $arr_serial['timestamp'] = $timestamp;
    $arr_serial['publishtime'] = date('Y-m-d H:i:s', $timestamp);
    $arr_serial['archivedate'] = $archivedate;

    return $arr_serial;
}

function blog_save($blog_serial, $blog_title, $blog_content, $category_name = 'general')
{
    $blog_file = BLOG_FOLDER.'/cate_'.base64_encode($category_name).'/'.$blog_serial;
    $blog_title = htmlspecialchars($blog_title);

    $blog_fp = fopen($blog_file, 'w');
    $write_blog_rs = fwrite($blog_fp, $blog_title."\n\n".$blog_content);
    fclose($blog_fp);

    return $write_blog_rs;
}

function blog_create($blog_title, $blog_content, $category_name = 'general')
{
    $blog_serial = str_replace(' ', '-', microtime()).'.'.date('Y-m-d');
    $blog_save_rs = blog_save($blog_serial, $blog_title, $blog_content, $category_name);
    
    return $blog_save_rs?$blog_serial:$blog_save_rs;
}

function blog_delete($blog_serial, $category_name)
{
    return unlink(BLOG_FOLDER.'/cate_'.base64_encode($category_name).'/'.$blog_serial);
}

function blog_read($blog_serial, $category_name)
{
    $blog_file = BLOG_FOLDER.'/cate_'.base64_encode($category_name).'/'.$blog_serial;
    if (file_exists($blog_file))
    {
        $blog = array();

        $blog_fp = fopen($blog_file, 'r');
        $blog_string = fread($blog_fp, filesize($blog_file));
        fclose($blog_fp);

        list($blog['title'], $blog['content']) =
            explode("\n\n", $blog_string, 2);
        $arr_serial = blog_parse_serial($blog_serial);
        $blog['serial'] = $blog_serial;
        $blog['publishtime'] = $arr_serial['publishtime'];
        $blog['timestamp'] = $arr_serial['timestamp'];
        $blog['category'] = $category_name;

        return $blog;
    }
    else
    {
        return false;
    }
}

function blog_move($blog_serial, $src_category_name, $dst_category_name)
{
    if (copy(BLOG_FOLDER.'/cate_'.base64_encode($src_category_name).'/'.$blog_serial,
        BLOG_FOLDER.'/cate_'.base64_encode($dst_category_name).'/'.$blog_serial))
    {
        return blog_delete($blog_serial, $src_category_name);
    }
    else
    {
        return false;
    }
}

function blog_list($search_key = '')
{
    $search_key = trim($search_key);

    $blogs = array();

    if ($blog_folder = opendir(realpath(BLOG_FOLDER)))
    {
        while (false !== ($category_name = readdir($blog_folder)))
        {
            if (preg_match('/^cate_/', $category_name))
            {
                if ($category_folder = opendir(realpath(BLOG_FOLDER.'/'.$category_name)))
                {
                    while (false !== ($blog_serial = readdir($category_folder)))
                    {
                        if (preg_match('/^\./', $blog_serial))
                        {
                            continue;
                        }

                        $arr_serial = blog_parse_serial($blog_serial);
                        if (!empty($search_key))
                        {
                            $entities = array(' ', '\\', '|', '{', '}', '[', ']', '.', '+', '*');
                            $replace_entities = array('|', '\\\\', '\\|', '\\{', '\\}', '\\[', '\\]', '\\.', '\\+', '\\*');
                            $search_key = str_replace($entities, $replace_entities, $search_key);
                            $blog = blog_read($blog_serial, base64_decode(substr($category_name, 5)));
                            if (preg_match('/'.$search_key.'/', $blog['title'])
                                || preg_match('/'.$search_key.'/', $blog['content']))
                            {
                                $blogs[] = array('serial' => $blog['serial'],
                                    'timestamp' => $arr_serial['timestamp'],
                                    'category' => $blog['category']);
                            }
                            //unset($blog);
                        }
                        else
                        {
                            $blogs[] = array('serial' => $blog_serial,
                                'timestamp' => $arr_serial['timestamp'],
                                'category' => base64_decode(substr($category_name, 5)));
                        }
                    }
                    closedir($category_folder);
                }
                else
                {
                    return false;
                }
            }
        }
        closedir($blog_folder);

        return $blogs;
    }
    else
    {
        return false;
    }
}

function blog_list_by_category($category_name)
{
    $blogs = array();

    if ($category_folder = opendir(realpath(BLOG_FOLDER.'/cate_'.base64_encode($category_name))))
    {
        while (false !== ($blog_serial = readdir($category_folder)))
        {
            if (preg_match('/^\./', $blog_serial))
            {
                continue;
            }

            $arr_serial = blog_parse_serial($blog_serial);
            $blogs[] = array('serial' => $blog_serial,
                'timestamp' => $arr_serial['timestamp'],
                'category' => $category_name);
        }
        closedir($category_folder);

        return $blogs;
    }
    else
    {
        return false;
    }
}

function blog_list_by_archive($archive_date)
{
    $blogs = array();

    $start_stamp = strtotime(date('Y-m-01 00:00:00', strtotime($archive_date)));
    $end_stamp = mktime(0, 0, 0, intval(date('n', strtotime($archive_date))) + 1,
        1, date('Y', strtotime($archive_date)));

    if ($blog_folder = opendir(realpath(BLOG_FOLDER)))
    {
        while (false !== ($category_name = readdir($blog_folder)))
        {
            if (preg_match('/^cate_/', $category_name))
            {
                if ($category_folder = opendir(realpath(BLOG_FOLDER.'/'.$category_name)))
                {
                    while (false !== ($blog_serial = readdir($category_folder)))
                    {
                        if (preg_match('/^\./', $blog_serial))
                        {
                            continue;
                        }

                        $arr_serial = blog_parse_serial($blog_serial);
                        if (intval($arr_serial['timestamp']) >= $start_stamp &&
                            intval($arr_serial['timestamp']) < $end_stamp)
                        {
                            $blogs[] = array('serial' => $blog_serial,
                                'timestamp' => $arr_serial['timestamp'],
                                'category' => base64_decode(substr($category_name, 5)));
                        }
                    }
                    closedir($category_folder);
                }
                else
                {
                    return false;
                }
            }
        }
        closedir($blog_folder);

        return $blogs;
    }
    else
    {
        return false;
    }
}

function blog_sort($arr_blog_list)
{
    if (!$arr_blog_list) return false;

    $list_length = count($arr_blog_list);
    for ($i = 1; $i <= $list_length; $i++)
    {
        for ($j = $list_length - 1; $j >= $i; $j--)
        {
            if ($arr_blog_list[$j]['timestamp']
                > $arr_blog_list[$j - 1]['timestamp'])
            {
                $swap = $arr_blog_list[$j - 1];
                $arr_blog_list[$j - 1] = $arr_blog_list[$j];
                $arr_blog_list[$j] = $swap;
            }
        }
    }

    return $arr_blog_list;
}

function blog_page($curr_page, $blog_array)
{
    global $blogs_per_page;

    $total_pages = ceil(count($blog_array) / $blogs_per_page);
    if ($total_pages < 1)
    {
        $total_pages = 1;
    }

    if (empty($curr_page) || !is_numeric($curr_page) || $curr_page < 1)
    {
        $curr_page = 1;
    }
    else if ($curr_page > $total_pages)
    {
        $curr_page = $total_pages;
    }

    $start_index = ($curr_page - 1) * $blogs_per_page;
    $end_index = $start_index + $blogs_per_page - 1;

    $blogs = array();
    for ($i = $start_index; $i <= $end_index; $i++)
    {
        if (isset($blog_array[$i]))
        {
            $blogs[] = blog_read($blog_array[$i]['serial'],
                $blog_array[$i]['category']);
        }
    }

    $prev_page = $curr_page - 1;
    if ($prev_page < 1)
    {
        $prev_page = 1;
    }

    $next_page = $curr_page + 1;
    if ($next_page > $total_pages)
    {
        $next_page = $total_pages;
    }

    $pager_html = '<div id="pagebar"><ul>'
        .'<li>&nbsp;<a href="'.pager_build_uri(1).'">&laquo;</a>&nbsp;</li>'
        .'<li>&nbsp;<a href="'.pager_build_uri($prev_page).'">&lsaquo;</a>&nbsp;</li>'
        .'<li>&nbsp;<a href="'.pager_build_uri($next_page).'">&rsaquo;</a>&nbsp;</li>'
        .'<li>&nbsp;<a href="'.pager_build_uri($total_pages).'">&raquo;</a>&nbsp;</li>'
        .'<li>&nbsp;'.$curr_page.'&nbsp;/&nbsp;'.$total_pages.'&nbsp;</li></ul></div>';

    $result['blogs'] = $blogs;
    $result['pager'] = $pager_html;

    return $result;
}

function comment_parse_serial($comment_serial)
{
    list($blog_serial, $microtime) = explode('#', $comment_serial);
    list($microsecond, $timestamp) = explode('-', $microtime);

    $arr_serial['microsecond'] = $microsecond;
    $arr_serial['timestamp'] = $timestamp;
    $arr_serial['commenttime'] = date('Y-m-d H:i:s', $timestamp);
    $arr_serial['blogserial'] = $blog_serial;

    return $arr_serial;
}

function comment_save($comment_serial, $comment_author, $comment_content)
{
    $comment_file = COMMENT_FOLDER.'/'.$comment_serial;
    $comment_author = htmlspecialchars($comment_author);
    $comment_content = htmlspecialchars($comment_content);

    $comment_fp = fopen($comment_file, 'w');
    $write_comment_rs = fwrite($comment_fp, $comment_author."\n\n".$comment_content);
    fclose($comment_fp);

    return $write_comment_rs;
}

function comment_create($blog_serial, $comment_author, $comment_content)
{
    $comment_serial = $blog_serial.'#'.str_replace(' ', '-', microtime());

    return comment_save($comment_serial, $comment_author, $comment_content);
}

function comment_read($comment_serial)
{
    $comment_file = COMMENT_FOLDER.'/'.$comment_serial;
    if (file_exists($comment_file))
    {
        $comment = array();

        $comment_fp = fopen($comment_file, 'r');
        $comment_string = fread($comment_fp, filesize($comment_file));
        fclose($comment_fp);

        list($comment['author'], $comment['content']) =
            explode("\n\n", $comment_string, 2);
        $arr_serial = comment_parse_serial($comment_serial);
        $comment['serial'] = $comment_serial;
        $comment['commenttime'] = $arr_serial['commenttime'];
        $comment['timestamp'] = $arr_serial['timestamp'];
        $comment['blogserial'] = $arr_serial['blogserial'];

        return $comment;
    }
    else
    {
        return false;
    }
}

function comment_delete($comment_serial)
{
    return unlink(COMMENT_FOLDER.'/'.$comment_serial);
}

function comment_list_by_blog($blog_serial)
{
    $blog_serial = str_replace('.', '\\.', $blog_serial);

    $comments = array();

    if ($comment_folder = opendir(realpath(COMMENT_FOLDER)))
    {
        while (false !== ($comment_serial = readdir($comment_folder)))
        {
            if (preg_match('/^'.$blog_serial.'#/', $comment_serial))
            {
                $comments[] = comment_read($comment_serial);
            }
        }
        closedir($comment_folder);

        return $comments;
    }
    else
    {
        return false;
    }
}

function comment_sort($arr_comment_list)
{
    if (!$arr_comment_list) return false;

    $list_length = count($arr_comment_list);
    for ($i = 1; $i <= $list_length; $i++)
    {
        for ($j = $list_length - 1; $j >= $i; $j--)
        {
            if ($arr_comment_list[$j]['timestamp']
                > $arr_comment_list[$j - 1]['timestamp'])
            {
                $swap = $arr_comment_list[$j - 1];
                $arr_comment_list[$j - 1] = $arr_comment_list[$j];
                $arr_comment_list[$j] = $swap;
            }
        }
    }

    return $arr_comment_list;
}

function archive_list()
{
    $hash_archive_list = array();
    $arr_archive_list = array();

    if ($blog_folder = opendir(realpath(BLOG_FOLDER)))
    {
        while (false !== ($category_name = readdir($blog_folder)))
        {
            if (preg_match('/^cate_/', $category_name))
            {
                if ($category_folder = opendir(realpath(BLOG_FOLDER.'/'.$category_name)))
                {
                    while (false !== ($blog_serial = readdir($category_folder)))
                    {
                        if (preg_match('/^\./', $blog_serial))
                        {
                            continue;
                        }

                        $arr_serial = blog_parse_serial($blog_serial);
                        $hash_archive_list[date('M Y', $arr_serial['timestamp'])] =
                            date('M Y', $arr_serial['timestamp']);
                    }
                    closedir($category_folder);
                }
            }
        }
        closedir($blog_folder);

        if (!empty($hash_archive_list))
        {
            foreach ($hash_archive_list as $key => $archive_date)
            {
                $arr_archive_list[] = $archive_date;
            }
        }

        return $arr_archive_list;
    }
    else
    {
        return false;
    }
}

function archive_sort($arr_archive_list)
{
    if (!$arr_archive_list) return false;

    $list_length = count($arr_archive_list);
    for ($i = 1; $i <= $list_length; $i++)
    {
        for ($j = $list_length - 1; $j >= $i; $j--)
        {
            if (strtotime($arr_archive_list[$j])
                > strtotime($arr_archive_list[$j - 1]))
            {
                $swap = $arr_archive_list[$j - 1];
                $arr_archive_list[$j - 1] = $arr_archive_list[$j];
                $arr_archive_list[$j] = $swap;
            }
        }
    }

    return $arr_archive_list;
}

function admin_check()
{
    global $admin_name;
    global $admin_pwd;

    if (!isset($_SESSION[$_SERVER['HTTP_HOST']]['admin_name'])
        || !isset($_SESSION[$_SERVER['HTTP_HOST']]['admin_pwd']))
    {
        return false;
    }
    else
    {
        if ($_SESSION[$_SERVER['HTTP_HOST']]['admin_name'] != $admin_name
            || $_SESSION[$_SERVER['HTTP_HOST']]['admin_pwd'] != $admin_pwd)
        {
            return false;
        }
        else
        {
            return true;
        }
    }
}

function admin_login($login_admin_name, $login_admin_pwd, $login_admin_vcode)
{
    global $admin_name;
    global $admin_pwd;

    if ($login_admin_vcode != $_SESSION[$_SERVER['HTTP_HOST']]['vcode']) {
        return false;
    }

    if ($admin_name == $login_admin_name
        && $admin_pwd == md5($login_admin_pwd))
    {
        $_SESSION[$_SERVER['HTTP_HOST']]['admin_name'] = $admin_name;
        $_SESSION[$_SERVER['HTTP_HOST']]['admin_pwd'] = $admin_pwd;
        return true;
    }
    else
    {
        return false;
    }
}

function admin_logout()
{
    unset($_SESSION[$_SERVER['HTTP_HOST']]);
    session_destroy();
}

function referer_check()
{
    if (isset($_SERVER['HTTP_REFERER']))
    {
        $arr_referer = array();
        preg_match('/^http:\/\/([^\/]+)/', $_SERVER['HTTP_REFERER'], $arr_referer);

        if (isset($arr_referer[1]))
        {
            if (trim($arr_referer[1]) == $_SERVER['HTTP_HOST'])
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }
    else
    {
        return false;
    }
}

function pager_build_uri($page)
{
    $uri_string = '&amp;p='.$page;

    if (isset($_GET))
    {
        foreach ($_GET as $key => $value)
        {
            if ($key != 'p')
            {
                $uri_string .= '&amp;'.$key.'='.urlencode($value);
            }
        }
    }
    if (isset($_POST))
    {
        foreach ($_POST as $key => $value)
        {
            if ($key != 'p')
            {
                $uri_string .= '&amp;'.$key.'='.urlencode($value);
            }
        }
    }

    if (empty($uri_string))
    {
        return 'index.php';
    }
    else
    {
        return 'index.php?'.substr($uri_string, 5);
    }
}

function load_side_blocks() {
    global $lang;
    global $site_tpl;
    global $blocks;

    if (sizeof($blocks) <= 0) {
        return false;
    }
    foreach ($blocks as $block) {
        $block_file = BLOCK_FOLDER.'/'.$block.'.php';
        $block_tpl = TPL_FOLDER.'/'.$site_tpl.'/'.BLOCK_FOLDER.'/'.$block.'.php';
        if (file_exists($block_tpl)) {
            if (file_exists($block_file)) {
                include_once $block_file;
            }
            include_once $block_tpl;
        }
    }
}

function tpl_list() {
    if ($tpl_folder = opendir(realpath(TPL_FOLDER)))
    {
        $arr_tpl_names = array();
        while (false !== ($tpl_name = readdir($tpl_folder)))
        {
            if ($tpl_name == '.' || $tpl_name == '..') {
                continue;
            }
            if (is_dir(realpath(TPL_FOLDER).'/'.$tpl_name))
            {
                $arr_tpl_names[] = $tpl_name;
            }
        }
        closedir($tpl_folder);

        return $arr_tpl_names;
    }
    else
    {
        return false;
    }
}

function lang_list() {
    if ($lang_folder = opendir(realpath(LANG_FOLDER)))
    {
        $arr_lang_names = array();
        while (false !== ($lang_name = readdir($lang_folder)))
        {
            if ($lang_name == '.' || $lang_name == '..') {
                continue;
            }
            $arr_lang_names[] = str_replace('.php', '', $lang_name);
        }
        closedir($lang_folder);

        return $arr_lang_names;
    }
    else
    {
        return false;
    }
}

function block_list() {
    global $site_tpl;

    if ($block_folder = opendir(realpath(TPL_FOLDER.'/'.$site_tpl.'/'.BLOCK_FOLDER)))
    {
        $arr_block_names = array();
        while (false !== ($block_name = readdir($block_folder)))
        {
            if ($block_name == '.' || $block_name == '..') {
                continue;
            }
            $arr_block_names[] = str_replace('.php', '', $block_name);
        }
        closedir($block_folder);

        return $arr_block_names;
    }
    else
    {
        return false;
    }
}

function get_blog_imgs($content) {
    /* Extract images if there's any */
    $content_images = array();
    $blog_images = array();
    preg_match_all('/<img[^>]+?src=["\'](.+?)["\']/i', $content, $content_images);
    // Replace with thumbs image
    if (($size = count($content_images[1])) > 0) {
        for ($i = 0; $i < $size; $i++) {
            $blog_images[] = preg_replace('/^'.UPLOAD_FOLDER.'/', THUMBS_FOLDER,
                    $content_images[1][$i]);
        }
    }
    return $blog_images;
}

function get_blog_short($content, $short_len = 240) {
    $content = strip_tags($content);
    if (strlen(trim($content)) > 0) {
        mb_internal_encoding('UTF-8');
        return mb_substr($content, 0, $short_len);
    } else
        return FALSE;
}

function reformat_callback($matches) {
    $geshi = new GeSHi($matches[2], $matches[1]);
    //$geshi->set_header_type(GESHI_HEADER_PRE_TABLE);
    $geshi->set_overall_style('color:#729fcf;background:#ffffdd;border-left:6px solid #729fcf;', true);
    $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
    return $geshi->parse_code();
}

function reformat_embeded_code($content) {
    return preg_replace_callback('/<pre class="_geshi_(.+?)">(.+?)<\/pre>/ims',
        "reformat_callback", $content);
}

include_once LANG_FOLDER.'/'.$site_lang.'.php';

if (version_compare(phpversion(), '5.1.0', '>') &&
    !empty($your_tz))
{
    if (!date_default_timezone_set($your_tz))
    {
        $sys_err = true;
        $sys_err_msg = $lang['tz_error'];
    }
}

// simulate magic_quotes_gpc off
if (ini_get('magic_quotes_gpc'))
{
    if (!empty($_POST))
    {
        foreach ($_POST as $key=>$val)
        {
            $_POST[$key] = stripcslashes($val);
        }
    }
    if (!empty($_GET))
    {
        foreach ($_GET as $key=>$val)
        {
            $_GET[$key] = stripcslashes($val);
        }
    }
}

// check general category
if (!category_exists('general')) category_create('general');

// get parameters
// TODO: Check key existance for both $_GET and $POST and get the existing value
$category_name = empty($_POST['c'])?(empty($_GET['c'])?FALSE:$_GET['c']):$_POST['c'];
$previous_category_name = empty($_POST['pc'])?(empty($_GET['pc'])?FALSE:$_GET['pc']):$_POST['pc'];
$archive_date = empty($_POST['a'])?(empty($_GET['a'])?FALSE:$_GET['a']):$_POST['a'];
$action = empty($_POST['ac'])?(empty($_GET['ac'])?FALSE:$_GET['ac']):$_POST['ac'];
$search_key = empty($_POST['s'])?(empty($_GET['s'])?FALSE:$_GET['s']):$_POST['s'];
$blog_serial = empty($_POST['b'])?(empty($_GET['b'])?FALSE:$_GET['b']):$_POST['b'];
$forward_page = empty($_POST['f'])?(empty($_GET['f'])?FALSE:$_GET['f']):$_POST['f'];
$comment_serial = empty($_POST['bc'])?(empty($_GET['bc'])?FALSE:$_GET['bc']):$_POST['bc'];
$template_name = empty($_POST['t'])?(empty($_GET['t'])?FALSE:$_GET['t']):$_POST['t'];
$curr_page = empty($_POST['p'])?(empty($_GET['p'])?FALSE:$_GET['p']):$_POST['p'];

if ($enable_uri_tpl)
{
    if (!empty($template_name) && file_exists(TPL_FOLDER.'/'.$template_name))
    {
        $site_tpl = $template_name;
    }
}

// TODO: add more action value to extend functions
switch ($action)
{
    case '1': // show login page
        if (admin_check())
        {
            header('Location:index.php');
            exit();
        }

        if (isset($_GET['e']))
        {
            $has_error = true;
        }
        else
        {
            $has_error = false;
        }

        $page_title = $lang['admin_login'];
        $page_body = TPL_FOLDER.'/'.$site_tpl.'/login.php';
    break;
    case '2': // do login
        if (!referer_check()) die();

        if (admin_login($_POST['admin_name'], $_POST['admin_pwd'], $_POST['admin_vcode']))
        {
            if (empty($forward_page))
            {
                header('Location:index.php');
                exit;
            }
            else
            {
                header('Location:'.$forward_page);
                exit();
            }
        }
        else
        {
            header('Location:index.php?ac=1&e=');
            exit();
        }
    break;
    case '3': // category form
        if (admin_check())
        {
            $categories = category_list();

            $page_title = $lang['new_category'];
            $page_body = TPL_FOLDER.'/'.$site_tpl.'/category.php';
        }
        else
        {
            header('Location:index.php?ac=1&f='.urlencode('index.php?ac=3'));
            exit();
        }
    break;
    case '4': // blog form
        if (admin_check())
        {
        	$blog = FALSE;
            if (!empty($blog_serial))
            {
                $blog = blog_read($blog_serial, $category_name);
            }

            $categories = category_list();

            $page_blogs = blog_page($curr_page, blog_sort(blog_list('')));
            $blogs = $page_blogs['blogs'];
            $pager_html = $page_blogs['pager'];

            if ($blog) {
                $page_title = $lang['edit'].' :: '.$blog['title'];
            } else {
                $page_title = $lang['new_blog'];
            }
            $page_body = TPL_FOLDER.'/'.$site_tpl.'/blog.php';
        }
        else
        {
            header('Location:index.php?ac=1&f='.urlencode('index.php?ac=4'));
            exit();
        }
    break;
    case '5': // do logout
        if (!referer_check()) die();

        admin_logout();

        header('Location:index.php');
        exit;
    break;
    case '6': // delete category
        if (!referer_check()) die();

        if (admin_check())
        {
            if ($category_name != 'general')
            {
                category_delete($category_name);
            }

            header('Location:index.php?ac=3');
            exit();
        }
        else
        {
            header('Location:index.php?ac=1&f='.urlencode('index.php?ac=3'));
            exit();
        }
    break;
    case '7': // create category
        if (!referer_check()) die();

        if (admin_check())
        {
            if ($category_name != 'general')
            {
                category_create($category_name);
            }

            header('Location:index.php?ac=3');
            exit();
        }
        else
        {
            header('Location:index.php?ac=1&f='.urlencode('index.php?ac=3'));
            exit();
        }
    break;
    case '8': // save blog
        if (!referer_check()) die();

        if (admin_check())
        {
            if (!empty($blog_serial))
            {
                blog_save($blog_serial, $_POST['title'], $_POST['content'], $previous_category_name);
                if ($category_name != $previous_category_name)
                {
                    blog_move($blog_serial, $previous_category_name, $category_name);
                }
            }
            else
            {
                $blog_serial = blog_create($_POST['title'], $_POST['content'], $category_name);
            }

            //header('Location:index.php?ac=4');
            header('Location:index.php?b='.$blog_serial.'&c='.urlencode($category_name));
            exit();
        }
        else
        {
            header('Location:index.php?ac=1&f='.urlencode('index.php?ac=4'));
            exit();
        }
    break;
    case '9': // delete blog
        if (!referer_check()) die();

        if (admin_check())
        {
            blog_delete($blog_serial, $category_name);

            header('Location:index.php?ac=4');
            exit();
        }
        else
        {
            header('Location:index.php?ac=1&f='.urlencode('index.php?ac=4'));
            exit();
        }
    break;
    case '10': // add comment
        if (!referer_check()) die();

        comment_create($blog_serial, $_POST['comment_author'], $_POST['comment_content']);

        header('Location:index.php?b='.$blog_serial.'&c='.$category_name);
        exit();
    break;
    case '11': // delete comment
        if (!referer_check()) die();

        if (admin_check())
        {
            comment_delete($comment_serial);

            header('Location:index.php?b='.$blog_serial.'&c='.$category_name);
            exit();
        }
        else
        {
            header('Location:index.php?ac=1&f='.urlencode('index.php?b='.$blog_serial.'&c='.$category_name));
            exit();
        }
    break;
    case '12': // settings form
        if (admin_check())
        {
            if (isset($_GET['e']))
            {
                $has_error = true;
            }
            else
            {
                $has_error = false;
            }

            $tpls = tpl_list();
            $langs = lang_list();
            $avail_blocks = block_list();
            $avail_blocks_str = '';
            if ($avail_blocks) {
                for ($i = 0, $size = count($avail_blocks); $i < $size; $i++) {
                    $avail_blocks_str .= ','.$avail_blocks[$i];
                }
                $avail_blocks_str = substr($avail_blocks_str, 1);
            }
            $block_str = '';
            if (sizeof($blocks) > 0) {
                for ($i = 0, $size = count($blocks); $i < $size; $i++) {
                    $block_str .= ','.$blocks[$i];
                }
                $block_str = substr($block_str, 1);
            }

            $page_title = $lang['general_settings'];
            $page_body = TPL_FOLDER.'/'.$site_tpl.'/settings.php';
        }
        else
        {
            header('Location:index.php?ac=1&f='.urlencode('index.php?ac=12'));
            exit();
        }
    break;
    case '13': // save general settings
        if (!referer_check()) die();

        if (admin_check())
        {
            $new_blocks = '\''.str_replace(',', '\', \'', $_POST['blocks']).'\'';
            $new_admin_pwd = '';
            if ($_POST['admin_pwd'] == $_POST['confirm_admin_pwd'])
            {
                $new_admin_pwd = $_POST['admin_pwd'];
            }
            else
            {
                header('Location:index.php?ac=12&e=');
                exit();
            }
            if (strlen(trim($new_admin_pwd)) == 0)
            {
                $new_admin_pwd = $admin_pwd;
            }
            else
            {
                $new_admin_pwd = md5($new_admin_pwd);
            }
            $settings = "<?php\n"
                ."\$site_name = '{$_POST['site_name']}';\n"
                ."\$site_slogan = '{$_POST['site_slogan']}';\n"
                ."\$site_tpl = '{$_POST['site_tpl']}';\n"
                ."\$site_lang = '{$_POST['site_lang']}';\n"
                ."\$admin_name = '{$_POST['admin_name']}';\n"
                ."\$admin_pwd = '{$new_admin_pwd}';\n"
                ."\$admin_email = '{$_POST['admin_email']}';\n"
                ."\$blogs_per_page = {$_POST['blogs_per_page']};\n";
            if ($_POST['enable_uri_tpl'] == '1') {
                $settings .= "\$enable_uri_tpl = true;\n";
            }
            else
            {
                $settings .= "\$enable_uri_tpl = false;\n";
            }
            $settings .= "\$context_root = '{$_POST['context_root']}';\n";
            if ($_POST['gzip_output'] == '1') {
                $settings .= "\$gzip_output = true;\n";
            }
            else
            {
                $settings .= "\$gzip_output = false;\n";
            }
            $settings .= "\$your_tz = '".trim($_POST['your_tz'])."';\n";
            $settings .= "\$blocks = array({$new_blocks});\n"
                ."?>";

            file_put_contents('secret.php', $settings);
            header('Location:index.php?ac=12');
            exit();
        }
        else
        {
            header('Location:index.php?ac=1&f='.urlencode('index.php?ac=12'));
            exit();
        }
    break;
    default: // blog list
        if (empty($blog_serial))
        {
        	$page_title = $lang['home'];
            if (empty($category_name) && empty($archive_date))
            {
                $blogs = blog_list($search_key);
                if (strlen(trim($search_key)) > 0) {
                    $page_title = $lang['search'].' :: '.$search_key;
                }
            }
            else if (!empty($category_name))
            {
                $blogs = blog_list_by_category($category_name);
                $page_title = $lang['category'].' :: '.$category_name;
            }
            else if (!empty($archive_date))
            {
                $blogs = blog_list_by_archive($archive_date);
                $page_title = $lang['archive'].' :: '.$archive_date;
            }

            $page_blogs = blog_page($curr_page, blog_sort($blogs));
            $blogs = $page_blogs['blogs'];
            $pager_html = $page_blogs['pager'];

            $page_body = TPL_FOLDER.'/'.$site_tpl.'/main.php';
        }
        else
        {
            include_once 'geshi.php';

            $blog = blog_read($blog_serial, $category_name);

            $blog['content'] = reformat_embeded_code($blog['content']);

            $comments = comment_list_by_blog($blog_serial);
            $comments = comment_sort($comments);

            $page_title = $blog['title'];
            $page_body = TPL_FOLDER.'/'.$site_tpl.'/blog_comment.php';
        }
    break;
}

include_once TPL_FOLDER.'/'.$site_tpl.'/layout.php';
?>
