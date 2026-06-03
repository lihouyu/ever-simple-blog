<?php
declare(strict_types=1);

/**
 * Category management — flat-file categories as subdirectories under blog/.
 */

function category_exists(string $category_name): bool
{
    return is_dir(BLOG_FOLDER . '/cate_' . base64_encode($category_name));
}

function category_create(string $category_name): bool
{
    if (category_exists($category_name)) {
        return false;
    }
    return @mkdir(BLOG_FOLDER . '/cate_' . base64_encode($category_name), 0755, true);
}

function category_delete(string $category_name): bool
{
    if ($category_name === 'general') {
        return false; // never delete the default category
    }

    $base64_name = base64_encode($category_name);
    $cat_path = BLOG_FOLDER . '/cate_' . $base64_name;

    // Move all blogs in this category to "general" first
    $dh = @opendir($cat_path);
    if ($dh) {
        while (false !== ($blog_serial = readdir($dh))) {
            if (str_starts_with($blog_serial, '.')) {
                continue;
            }
            blog_move($blog_serial, $category_name, 'general');
        }
        closedir($dh);
    }

    return @rmdir($cat_path);
}

function category_list(): array|false
{
    $blog_folder_path = realpath(BLOG_FOLDER);
    if ($blog_folder_path === false) {
        return false;
    }
    $blog_folder = opendir($blog_folder_path);
    if (!$blog_folder) {
        return false;
    }

    $arr_category_names = [];
    while (false !== ($entry = readdir($blog_folder))) {
        if (str_starts_with($entry, 'cate_')) {
            $arr_category_names[] = base64_decode(substr($entry, 5));
        }
    }
    closedir($blog_folder);

    return $arr_category_names;
}
