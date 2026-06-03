<div class="navouter">
<div class="orange"></div>
<div class="navheader"><?php echo h($lang['category']); ?></div>
<div class="navcontent">
<ul>
<?php if ($categories):
    for ($i = 0, $size = count($categories); $i < $size; $i++): ?>
<li><a href="index.php?c=<?php echo urlencode($categories[$i]); ?>"><?php echo h($categories[$i]); ?></a></li>
<?php endfor;
endif; ?>
</ul>
</div>
</div>
