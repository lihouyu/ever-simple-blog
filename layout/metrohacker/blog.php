<h1><?php echo $lang['blog_form']; ?></h1>
<script type="text/javascript" language="javascript" src="jquery-min.js"></script>
<script type="text/javascript" language="javascript" src="jquery-ui-resizable.min.js"></script>
<script type="text/javascript" language="javascript">
<!--
$(document).ready(function() {
    $("textarea").resizable({
        handles: "s", 
        animate: true, 
    	animateDuration: "fast", 
    	animateEasing: "swing"
    });
});

function checkblog(form)
{
    var has_error = false;
    
    if (/^\s*$/.test(form.elements["title"].value))
    {
        document.getElementById("msg_title").innerHTML = "<?php echo $lang['blog_title_empty']; ?>";
        document.getElementById("msg_title").style.display = "block";
        has_error = true;
    }
    else
    {
        document.getElementById("msg_title").style.display = "none";
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
<form name="blogfrm" action="index.php" method="post" onSubmit="return checkblog(this);">
<p>
<?php echo $lang['title']; ?>:<br />
<input type="text" name="title" value="<?php echo $blog['title']; ?>" size="50" />
&nbsp;<span id="msg_title" class="alert" style="display:none;"></span><br />
<?php echo $lang['category']; ?>:<br />
<select name="c">
<?php
if ($categories)
{
for ($i = 0; $i < count($categories); $i++)
{
?>
<option value="<?php echo $categories[$i]; ?>"<?php if ($blog['category'] == $categories[$i]) echo ' selected="selected"'; ?>>
<?php echo $categories[$i]; ?></option>
<?php
}
}
?>
</select><br />
<?php echo $lang['content']; ?>:<br />
<textarea id="content" name="content" rows="12" cols="64"><?php echo $blog['content']; ?></textarea><br />
<br />
<input type="submit" name="bsubmit" value="<?php echo $lang['save']; ?>" />
<input type="hidden" name="b" value="<?php echo $blog['serial']; ?>" />
<input type="hidden" name="pc" value="<?php echo $blog['category']; ?>" />
<input type="hidden" name="ac" value="8" />
</p>
</form>
<br />
<h1><?php echo $lang['blog_list']; ?></h1>
<p>
<?php
if ($blogs)
{
for ($i = 0; $i < count($blogs); $i++)
{
?>
[&nbsp;<a href="index.php?ac=4&amp;b=<?php echo $blogs[$i]['serial']; ?>&amp;c=<?php echo urlencode($blogs[$i]['category']); ?>"><?php echo $lang['edit']; ?></a>&nbsp;]
&nbsp;[&nbsp;<a href="index.php?ac=9&amp;b=<?php echo $blogs[$i]['serial']; ?>&amp;c=<?php echo urlencode($blogs[$i]['category']); ?>"><?php echo $lang['delete']; ?></a>&nbsp;]
&nbsp;<?php echo $blogs[$i]['title']; ?><br />
<?php
}
}
?>
</p>
<?php echo $pager_html; ?>