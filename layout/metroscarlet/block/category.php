<div class="navouter">
<div class="orange"></div>
<div class="navheader"><?php echo $lang['category']; ?></div>
<div class="navcontent">
<ul>
<?php
if ($categories)
{
for ($i = 0, $size = count($categories); $i < $size; $i++)
{
?>
<li><a href="index.php?c=<?php echo urlencode($categories[$i]); ?>"><?php echo $categories[$i]; ?></a></li>
<?php
}
}
?>
</ul>
</div>
</div>
