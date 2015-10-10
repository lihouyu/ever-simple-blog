<?php
if ($blogs)
{
for ($i = 0, $size = count($blogs); $i < $size; $i++)
{
?>
<div class="blog">
<h1><?php echo $blogs[$i]['title']; ?></h1>
<div class="htimeauth"><?php echo date('Y-m-d H:i', $blogs[$i]['timestamp']); ?>
&nbsp;|&nbsp;
<?php echo $lang['category']; ?>: <a href="index.php?c=<?php echo urlencode($blogs[$i]['category']); ?>">
<?php echo $blogs[$i]['category']; ?></a></div>
<?php
$blog_imgs = get_blog_imgs($blogs[$i]['content']);
if (($n_imgs = count($blog_imgs)) > 0)
{
?>
<div class="blog_img_thumbs">
<?php
for ($j = 0; $j < $n_imgs; $j++)
{
?>
<img src="<?php echo $blog_imgs[$j]; ?>" />
<?php
}
?>
</div>
<?php
}
?>
<?php
$content = get_blog_short($blogs[$i]['content']);
echo $content?$content.'<br />':'';
?>
<a href="index.php?b=<?php echo $blogs[$i]['serial']; ?>&amp;c=<?php echo urlencode($blogs[$i]['category']); ?>"><?php echo $lang['readmore']; ?></a>&nbsp;&raquo;
<p class="meta">
&laquo;&nbsp;
<a href="index.php?b=<?php echo $blogs[$i]['serial']; ?>&amp;c=<?php echo urlencode($blogs[$i]['category']); ?>"><?php echo $lang['comment']; ?></a>
&nbsp;|&nbsp;
<a href="#top"><?php echo $lang['back_to_top']; ?></a>
<?php
if (admin_check())
{
?>
&nbsp;|&nbsp;
<a href="index.php?ac=4&amp;b=<?php echo $blogs[$i]['serial']; ?>&amp;c=<?php echo urlencode($blogs[$i]['category']); ?>"><?php echo $lang['edit']; ?></a>
<?php
}
?>
&nbsp;&raquo;
</p>
</div>
<?php
}
}
echo $pager_html;
?>