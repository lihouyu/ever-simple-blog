<?php
error_reporting(0);

define('BLOG_FOLDER', 'blog');

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

function blog_read($blog_serial, $category_name)
{
    $blog_file = BLOG_FOLDER.'/cate_'.$category_name.'/'.$blog_serial;
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

function blog_list()
{
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
                        $blogs[] = array('serial' => $blog_serial, 
                            'timestamp' => $arr_serial['timestamp'], 
                            'category' => substr($category_name, 5));
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
        .'<li>&nbsp;<a href="'.pager_build_uri(1).'">&laquo;</a>&nbsp;<li>'
        .'<li>&nbsp;<a href="'.pager_build_uri($prev_page).'">&lsaquo;</a>&nbsp;<li>'
        .'<li>&nbsp;<a href="'.pager_build_uri($next_page).'">&rsaquo;</a>&nbsp;<li>'
        .'<li>&nbsp;<a href="'.pager_build_uri($total_pages).'">&raquo;</a>&nbsp;<li>'
        .'<li>&nbsp;'.$curr_page.'|'.$total_pages.'&nbsp;</li></ul></div>';
    
    $result['blogs'] = $blogs;
    $result['pager'] = $pager_html;
    
    return $result;
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

function rss_create($arr_blog_list)
{
    global $site_name;
    global $site_slogan;
    global $context_root;
    
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
    echo "<rss version=\"2.0\">\n";
    echo "<channel>\n";
    echo "<title><![CDATA[$site_name]]></title>\n";
    echo "<description><![CDATA[$site_slogan]]></description>\n";
    echo "<link>http://".$_SERVER['HTTP_HOST'].$context_root."</link>\n";
    if (!empty($arr_blog_list))
    {
        foreach ($arr_blog_list as $blog)
        {
            echo "<item>\n";
            echo "<title><![CDATA[{$blog['title']}]]></title>\n";
            echo "<link>http://".$_SERVER['HTTP_HOST'].$context_root
                ."index.php?b={$blog['serial']}&amp;c={$blog['category']}</link>\n";
            echo "<description><![CDATA[{$blog['content']}]]></description>";
            echo "<category><![CDATA[{$blog['category']}]]></category>\n";
            echo "<comments>http://".$_SERVER['HTTP_HOST'].$context_root
                ."index.php?b={$blog['serial']}&amp;c={$blog['category']}</comments>\n";
            echo "<pubDate>".date('r', $blog['timestamp'])."</pubDate>\n";
            echo "</item>\n";
        }
    }
    echo "</channel>\n";
    echo "</rss>";
}

include_once 'secret.php';

$page_blogs = blog_page(1, blog_sort(blog_list()));
rss_create($page_blogs['blogs']);
?>