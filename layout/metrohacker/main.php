<?php
if ($blogs)
{
for ($i = 0; $i < count($blogs); $i++)
{
?>
<div class="blog">
<h1><?php echo $blogs[$i]['title']; ?></h1>
<div class="htimeauth"><?php echo date('Y-m-d H:i', $blogs[$i]['timestamp']); ?>
&nbsp;|&nbsp;
<?php echo $lang['category']; ?>: <a href="index.php?c=<?php echo urlencode($blogs[$i]['category']); ?>">
<?php echo $blogs[$i]['category']; ?></a></div>
<?php echo get_blog_short($blogs[$i]['content']); ?>
<br />
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