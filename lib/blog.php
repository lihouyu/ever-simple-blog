<?php
declare(strict_types=1);

/**
 * Blog storage functions — flat-file CRUD for blog posts.
 */

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

function blog_save(
    string $blog_serial,
    string $blog_title,
    string $blog_content,
    string $category_name = 'general'
): int|false {
    $blog_file = BLOG_FOLDER . '/cate_' . base64_encode($category_name) . '/' . $blog_serial;
    // Title is stored raw — escaping happens at output (h() in templates).
    return file_put_contents($blog_file, $blog_title . "\n\n" . $blog_content);
}

function blog_create(
    string $blog_title,
    string $blog_content,
    string $category_name = 'general'
): string|false {
    // Ensure category exists
    if (!category_exists($category_name)) {
        category_create($category_name);
    }

    $blog_serial = str_replace(' ', '-', microtime()) . '.' . date('Y-m-d');
    $result = blog_save($blog_serial, $blog_title, $blog_content, $category_name);
    return $result !== false ? $blog_serial : false;
}

function blog_delete(string $blog_serial, string $category_name): bool
{
    // Path traversal guard
    if (str_contains($blog_serial, '..') || str_contains($blog_serial, '/') || str_contains($blog_serial, "\0")) {
        return false;
    }
    $path = BLOG_FOLDER . '/cate_' . base64_encode($category_name) . '/' . $blog_serial;
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

function blog_move(
    string $blog_serial,
    string $src_category_name,
    string $dst_category_name
): bool {
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
    if ($blog_folder_path === false) {
        return false;
    }
    $blog_folder = opendir($blog_folder_path);
    if (!$blog_folder) {
        return false;
    }

    while (false !== ($category_name = readdir($blog_folder))) {
        if (!str_starts_with($category_name, 'cate_')) {
            continue;
        }

        $cat_path = realpath(BLOG_FOLDER . '/' . $category_name);
        if ($cat_path === false) {
            continue;
        }
        $category_folder = opendir($cat_path);
        if (!$category_folder) {
            continue;
        }

        while (false !== ($blog_serial = readdir($category_folder))) {
            if (str_starts_with($blog_serial, '.')) {
                continue;
            }

            $arr_serial = blog_parse_serial($blog_serial);
            $decoded_cat = base64_decode(substr($category_name, 5));

            if ($search_key !== '') {
                $blog = blog_read($blog_serial, $decoded_cat);
                if ($blog === false) {
                    continue;
                }
                $pattern = '/' . preg_quote($search_key, '/') . '/i';
                if (preg_match($pattern, $blog['title']) || preg_match($pattern, $blog['content'])) {
                    $blogs[] = [
                        'serial'    => $blog['serial'],
                        'timestamp' => $arr_serial['timestamp'],
                        'category'  => $blog['category'],
                    ];
                }
            } else {
                $blogs[] = [
                    'serial'    => $blog_serial,
                    'timestamp' => $arr_serial['timestamp'],
                    'category'  => $decoded_cat,
                ];
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
    if ($cat_path === false) {
        return false;
    }

    $category_folder = opendir($cat_path);
    if (!$category_folder) {
        return false;
    }

    while (false !== ($blog_serial = readdir($category_folder))) {
        if (str_starts_with($blog_serial, '.')) {
            continue;
        }
        $arr_serial = blog_parse_serial($blog_serial);
        $blogs[] = [
            'serial'    => $blog_serial,
            'timestamp' => $arr_serial['timestamp'],
            'category'  => $category_name,
        ];
    }
    closedir($category_folder);
    return $blogs;
}

function blog_list_by_archive(string $archive_date): array|false
{
    $blogs = [];
    $ts = strtotime($archive_date);
    if ($ts === false) {
        return false;
    }

    $start_stamp = strtotime(date('Y-m-01 00:00:00', $ts));
    $end_stamp = mktime(0, 0, 0, (int) date('n', $ts) + 1, 1, (int) date('Y', $ts));

    $blog_folder_path = realpath(BLOG_FOLDER);
    if ($blog_folder_path === false) {
        return false;
    }
    $blog_folder = opendir($blog_folder_path);
    if (!$blog_folder) {
        return false;
    }

    while (false !== ($category_name = readdir($blog_folder))) {
        if (!str_starts_with($category_name, 'cate_')) {
            continue;
        }
        $cat_path = realpath(BLOG_FOLDER . '/' . $category_name);
        if ($cat_path === false) {
            continue;
        }
        $category_folder = opendir($cat_path);
        if (!$category_folder) {
            continue;
        }

        while (false !== ($blog_serial = readdir($category_folder))) {
            if (str_starts_with($blog_serial, '.')) {
                continue;
            }
            $arr_serial = blog_parse_serial($blog_serial);
            if ((int) $arr_serial['timestamp'] >= $start_stamp
                && (int) $arr_serial['timestamp'] < $end_stamp
            ) {
                $blogs[] = [
                    'serial'    => $blog_serial,
                    'timestamp' => $arr_serial['timestamp'],
                    'category'  => base64_decode(substr($category_name, 5)),
                ];
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

    $blog_folder_path = realpath(BLOG_FOLDER);
    if ($blog_folder_path === false) {
        return false;
    }
    $blog_folder = opendir($blog_folder_path);
    if (!$blog_folder) {
        return false;
    }

    while (false !== ($category_name = readdir($blog_folder))) {
        if (!str_starts_with($category_name, 'cate_')) {
            continue;
        }
        $cat_path = realpath(BLOG_FOLDER . '/' . $category_name);
        if ($cat_path === false) {
            continue;
        }
        $category_folder = opendir($cat_path);
        if (!$category_folder) {
            continue;
        }

        while (false !== ($blog_serial = readdir($category_folder))) {
            if (str_starts_with($blog_serial, '.')) {
                continue;
            }
            $arr_serial = blog_parse_serial($blog_serial);
            $key = date('M Y', $arr_serial['timestamp']);
            $hash_archive_list[$key] = $key;
        }
        closedir($category_folder);
    }
    closedir($blog_folder);

    return array_values($hash_archive_list);
}

// ── Sorting helpers ──────────────────────────────────────────────

/** Sort items descending by 'timestamp' key. */
function sort_by_timestamp(array $items): array
{
    usort($items, fn(array $a, array $b): int => $b['timestamp'] <=> $a['timestamp']);
    return $items;
}

function blog_sort(array $arr_blog_list): array
{
    // Sticky posts first, then by timestamp descending
    usort($arr_blog_list, function (array $a, array $b): int {
        $sa = sticky_is($a['serial']) ? 1 : 0;
        $sb = sticky_is($b['serial']) ? 1 : 0;
        if ($sa !== $sb) return $sb <=> $sa;
        return $b['timestamp'] <=> $a['timestamp'];
    });
    return $arr_blog_list;
}

/** Sort archive strings (e.g. "Jun 2025") descending. */
function archive_sort(array $arr_archive_list): array
{
    usort($arr_archive_list, fn(string $a, string $b): int => strtotime($b) <=> strtotime($a));
    return $arr_archive_list;
}

// ── Sticky posts ────────────────────────────────────────────────

function sticky_list(bool $refresh = false): array {
    static $cache = null;
    if ($cache !== null && !$refresh) return $cache;
    $file = __DIR__ . '/../sticky.json';
    if (!file_exists($file)) return $cache = [];
    $data = json_decode(file_get_contents($file), true);
    return $cache = (is_array($data) ? $data : []);
}

function sticky_set(string $serial, bool $sticky): void {
    $file = __DIR__ . '/../sticky.json';
    $list = sticky_list();
    if ($sticky) {
        if (!in_array($serial, $list, true)) $list[] = $serial;
    } else {
        $list = array_values(array_filter($list, fn($s) => $s !== $serial));
    }
    file_put_contents($file, json_encode($list, JSON_PRETTY_PRINT));
    sticky_list(true); // refresh static cache after write
}

function sticky_is(string $serial): bool {
    return in_array($serial, sticky_list(), true);
}

// ── Pager ────────────────────────────────────────────────────────

function blog_page(int $curr_page, array $blog_array): array
{
    global $blogs_per_page;

    $total_pages = max(1, (int) ceil(count($blog_array) / $blogs_per_page));

    if ($curr_page < 1) {
        $curr_page = 1;
    } elseif ($curr_page > $total_pages) {
        $curr_page = $total_pages;
    }

    $start_index = ($curr_page - 1) * $blogs_per_page;
    $end_index = $start_index + $blogs_per_page - 1;

    $blogs = [];
    for ($i = $start_index; $i <= $end_index; $i++) {
        if (isset($blog_array[$i])) {
            $blog = blog_read($blog_array[$i]['serial'], $blog_array[$i]['category']);
            if ($blog !== false) {
                $blogs[] = $blog;
            }
        }
    }

    $prev_page = max(1, $curr_page - 1);
    $next_page = min($total_pages, $curr_page + 1);

    // Build page number links with a sliding window
    $page_links = '';
    $window = 2;
    $start = max(1, $curr_page - $window);
    $end = min($total_pages, $curr_page + $window);

    if ($start > 1) {
        $page_links .= '<li class="pgnum"><a href="' . pager_build_uri(1) . '">1</a></li>';
        if ($start > 2) $page_links .= '<li class="pgsep">&hellip;</li>';
    }
    for ($p = $start; $p <= $end; $p++) {
        if ($p === $curr_page) {
            $page_links .= '<li class="pgnum pgcur"><span>' . $p . '</span></li>';
        } else {
            $page_links .= '<li class="pgnum"><a href="' . pager_build_uri($p) . '">' . $p . '</a></li>';
        }
    }
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) $page_links .= '<li class="pgsep">&hellip;</li>';
        $page_links .= '<li class="pgnum"><a href="' . pager_build_uri($total_pages) . '">' . $total_pages . '</a></li>';
    }

    $pager_html = '<div id="pagebar"><ul>'
        . '<li class="pgarr"><a href="' . pager_build_uri($prev_page) . '">&lsaquo;</a></li>'
        . $page_links
        . '<li class="pgarr"><a href="' . pager_build_uri($next_page) . '">&rsaquo;</a></li>'
        . '</ul></div>';

    return ['blogs' => $blogs, 'pager' => $pager_html];
}
