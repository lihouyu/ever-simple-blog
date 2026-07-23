<?php
declare(strict_types=1);

/**
 * Comment storage — flat-file CRUD for blog comments.
 */

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

function comment_save(
    string $comment_serial,
    string $comment_author,
    string $comment_content
): int|false {
    $comment_file = COMMENT_FOLDER . '/' . $comment_serial;
    // Author/content are stored raw — escaping happens at output (h() in templates).
    return file_put_contents($comment_file, $comment_author . "\n\n" . $comment_content);
}

function comment_create(
    string $blog_serial,
    string $comment_author,
    string $comment_content,
    string $comment_vcode
): int|false {
    // Vcode validation is done in the caller
    $comment_serial = $blog_serial . '#' . str_replace(' ', '-', microtime());
    return comment_save($comment_serial, $comment_author, $comment_content);
}

function comment_read(string $comment_serial): array|false
{
    $comment_file = COMMENT_FOLDER . '/' . $comment_serial;
    if (!file_exists($comment_file)) {
        return false;
    }

    $comment_string = file_get_contents($comment_file);
    if ($comment_string === false) {
        return false;
    }

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
    // Path traversal guard
    if (
        str_contains($comment_serial, '..')
        || str_contains($comment_serial, '/')
        || str_contains($comment_serial, "\0")
    ) {
        return false;
    }
    return @unlink(COMMENT_FOLDER . '/' . $comment_serial);
}

function comment_list_by_blog(string $blog_serial): array|false
{
    $comments = [];
    $comment_folder_path = realpath(COMMENT_FOLDER);
    if ($comment_folder_path === false) {
        return false;
    }
    $comment_folder = opendir($comment_folder_path);
    if (!$comment_folder) {
        return false;
    }

    $escaped = preg_quote($blog_serial, '/');
    while (false !== ($comment_serial = readdir($comment_folder))) {
        if (preg_match('/^' . $escaped . '#/', $comment_serial)) {
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
    usort($arr_comment_list, fn(array $a, array $b): int => $b['timestamp'] <=> $a['timestamp']);
    return $arr_comment_list;
}
