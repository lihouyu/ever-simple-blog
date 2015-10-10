<div class="navouter">
<div class="orange"></div>
<div class="navheader"><?php echo $lang['archive']; ?></div>
<div class="navcontent">
<ul>
<?php
if ($archives)
{
for ($i = 0, $size = count($archives); $i < $size; $i++)
{
?>
<li><a href="index.php?a=<?php echo urlencode($archives[$i]); ?>"><?php echo $archives[$i]; ?></a></li>
<?php
}
}
?>
</ul>
</div>
</div>
