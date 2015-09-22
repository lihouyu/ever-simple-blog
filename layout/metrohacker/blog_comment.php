<?php
if ($blog)
{
?>
<div class="blog">
<h1><?php echo $blog['title']; ?></h1>
<div class="htimeauth"><?php echo date('Y-m-d H:i', $blog['timestamp']); ?>
&nbsp;|&nbsp;
<?php echo $lang['category']; ?>: <a href="index.php?c=<?php echo urlencode($blog['category']); ?>">
<?php echo $blog['category']; ?></a></div>
<?php echo $blog['content']; ?>
<?php
if (admin_check())
{
?>
<p class="meta">
&laquo;&nbsp;
<a href="index.php?ac=4&amp;b=<?php echo $blog['serial']; ?>&amp;c=<?php echo urlencode($blog['category']); ?>"><?php echo $lang['edit']; ?></a>
&nbsp;&raquo;
</p>
<?php
}
?>
</div>
<div class="blog">
<h1><?php echo $lang['comment']; ?></h1>
<?php
// display comment list
if ($comments)
{
for ($i = 0, $size = count($comments); $i < $size; $i++)
{
?>
<p>
<?php
if (admin_check())
{
?>
[&nbsp;
<a href="index.php?ac=11&amp;b=<?php echo $blog['serial']; ?>&amp;c=<?php echo urlencode($blog['category']); ?>&amp;bc=<?php echo urlencode($comments[$i]['serial']); ?>">
<?php echo $lang['delete']; ?></a>
&nbsp;]
<?php
}
?>
&nbsp;<?php echo date('Y-m-d H:i', $comments[$i]['timestamp']); ?>
&nbsp;<?php echo $comments[$i]['author']; ?>
&nbsp;<?php echo $lang['said']; ?>
<blockquote>
<?php echo $comments[$i]['content']; ?>
</blockquote>
</p>
<?php
}
}
?>
</div>
<div class="blog">
<script type="text/javascript">
<!--
function chkcomment(form)
{
    var has_error = false;
    
    if (/^\s*$/.test(form.elements["comment_author"].value))
    {
        document.getElementById("msg_comment_author").innerHTML = "<?php echo $lang['comment_author_empty']; ?>";
        document.getElementById("msg_comment_author").style.display = "block";
        has_error = true;
    }
    else
    {
        document.getElementById("msg_comment_author").style.display = "none";
    }
    if (/^\s*$/.test(form.elements["comment_content"].value))
    {
        document.getElementById("msg_comment_content").innerHTML = "<?php echo $lang['comment_content_empty']; ?>";
        document.getElementById("msg_comment_content").style.display = "block";
        has_error = true;
    }
    else
    {
        document.getElementById("msg_comment_content").style.display = "none";
    }
    if (/^\s*$/.test(form.elements["comment_vcode"].value))
    {
        document.getElementById("msg_comment_vcode").innerHTML = "<?php echo $lang['comment_vcode_empty']; ?>";
        document.getElementById("msg_comment_vcode").style.display = "block";
        has_error = true;
    }
    else
    {
        document.getElementById("msg_admin_vcode").style.display = "none";
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
<form name="commentfrm" action="index.php" method="post" onSubmit="return chkcomment(this);">
<p>
<?php echo $lang['nickname']; ?>:<br /><input type="text" name="comment_author" value="" />
&nbsp;<span id="msg_comment_author" class="alert" style="display:none;"></span><br />
<?php echo $lang['comment']; ?>:<br />
<textarea name="comment_content" style="width:64%;height:200px;"></textarea>
&nbsp;<span id="msg_comment_content" class="alert" style="display:none;"></span><br />
<?php echo $lang['comment_vcode']; ?>:<br /><input type="text" name="comment_vcode" value="" /><br />
<span id="vcode"></span>
<span id="msg_comment_vcode" class="alert" style="display:none;"></span><br />
<input type="submit" name="submit" value="<?php echo $lang['post_comment']; ?>" />
<input type="hidden" name="ac" value="10" />
<input type="hidden" name="b" value="<?php echo $blog['serial']; ?>" />
<input type="hidden" name="c" value="<?php echo $blog['category']; ?>" />
</p>
</form>
<script type="text/javascript" src="jquery-min.js"></script>
<script type="text/javascript">
<!--
$.get("vcode.php", function(data){
  $("#vcode").append(data);
});
//-->
</script>
<?php
if ($has_error)
{
?>
<br /><span class="alert"><?php echo $lang[$error_msg]; ?></span>
<?php
}
?>
</div>
<?php
}
?>