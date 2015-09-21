<h1><?php echo $lang['category_list']; ?></h1>
<p>
<?php
if ($categories)
{
for ($i = 0, $size = count($categories); $i < $size; $i++)
{
?>
[&nbsp;<a href="index.php?ac=6&amp;c=<?php echo urlencode($categories[$i]); ?>"><?php echo $lang['delete']; ?></a>&nbsp;]
&nbsp;<?php echo $categories[$i]; ?><br />
<?php
}
}
?>
</p>
<br />
<script type="text/javascript" language="javascript">
<!--
function checkcategory(form)
{
    var has_error = false;
    
    if (/^\s*$/.test(form.elements["c"].value))
    {
        document.getElementById("msg_c").innerHTML = "<?php echo $lang['category_name_empty']; ?>";
        document.getElementById("msg_c").style.display = "block";
        has_error = true;
    }
    else
    {
        document.getElementById("msg_c").style.display = "none";
    }
    
    if (has_error)
    {
        return false;
    }
    else
    {
        return true;
    }
}
//-->
</script>
<form name="catefrm" action="index.php" method="post" onSubmit="return checkcategory(this);">
<p>
<?php echo $lang['category_name']; ?>:
<br /><input type="text" name="c" />
&nbsp;<span id="msg_c" class="alert" style="display:none;"></span><br />
<br /><input type="submit" name="csubmit" value="<?php echo $lang['add']; ?>" />
<input type="hidden" name="ac" value="7" />
</p>
</form>