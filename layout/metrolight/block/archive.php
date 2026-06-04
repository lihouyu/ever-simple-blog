<div class="navouter">
<div class="orange"></div>
<div class="navheader"><?php echo h($lang['archive']); ?></div>
<div class="navcontent">
<ul>
<?php if ($archives ?? false):
    for ($i = 0, $size = count($archives); $i < $size; $i++): ?>
<li><a href="index.php?a=<?php echo urlencode($archives[$i]); ?>"><?php echo h($archives[$i]); ?></a></li>
<?php endfor;
endif; ?>
</ul>
</div>
</div>
