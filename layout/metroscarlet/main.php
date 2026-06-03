<?php
if ($blogs):
    for ($i = 0, $size = count($blogs); $i < $size; $i++):
        $blog_imgs = get_blog_imgs($blogs[$i]['content']);
        $n_imgs = count($blog_imgs);
        $short = get_blog_short($blogs[$i]['content']);
?>
<div class="blog">
<h1><?php echo h($blogs[$i]['title']); ?></h1>
<div class="htimeauth"><?php echo date('Y-m-d H:i', $blogs[$i]['timestamp']); ?>
&nbsp;|&nbsp;
<?php echo h($lang['category']); ?>: <a href="index.php?c=<?php echo urlencode($blogs[$i]['category']); ?>">
<?php echo h($blogs[$i]['category']); ?></a></div>
<?php if ($n_imgs > 0): ?>
<div class="blog_img_thumbs">
<?php for ($j = 0; $j < $n_imgs; $j++): ?>
<img src="<?php echo h($blog_imgs[$j]); ?>" alt="" />
<?php endfor; ?>
</div>
<?php endif; ?>
<?php if ($short !== false): ?>
<?php echo $short; ?><br />
<?php endif; ?>
<a class="readmore" href="index.php?b=<?php echo urlencode($blogs[$i]['serial']); ?>&amp;c=<?php echo urlencode($blogs[$i]['category']); ?>"><?php echo h($lang['readmore']); ?>&nbsp;...</a>
<p class="meta">
&laquo;&nbsp;
<a href="index.php?b=<?php echo urlencode($blogs[$i]['serial']); ?>&amp;c=<?php echo urlencode($blogs[$i]['category']); ?>"><?php echo h($lang['comment']); ?></a>
&nbsp;|&nbsp;
<a href="#top"><?php echo h($lang['back_to_top']); ?></a>
<?php if (admin_check()): ?>
&nbsp;|&nbsp;
<a href="index.php?ac=4&amp;b=<?php echo urlencode($blogs[$i]['serial']); ?>&amp;c=<?php echo urlencode($blogs[$i]['category']); ?>"><?php echo h($lang['edit']); ?></a>
<?php endif; ?>
&nbsp;&raquo;
</p>
</div>
<?php
    endfor;
endif;
echo $pager_html;
?>
