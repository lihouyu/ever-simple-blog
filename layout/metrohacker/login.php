<h1><?php echo $lang['admin_login']; ?></h1>
<script type="text/javascript" language="javascript">
<!--
function chklogin(form)
{
    var has_error = false;
    
    if (/^\s*$/.test(form.elements["admin_name"].value))
    {
        document.getElementById("msg_admin_name").innerHTML = "<?php echo $lang['admin_name_empty']; ?>";
        document.getElementById("msg_admin_name").style.display = "block";
        has_error = true;
    }
    else
    {
        document.getElementById("msg_admin_name").style.display = "none";
    }
    if (/^\s*$/.test(form.elements["admin_pwd"].value))
    {
        document.getElementById("msg_admin_pwd").innerHTML = "<?php echo $lang['admin_pwd_empty']; ?>";
        document.getElementById("msg_admin_pwd").style.display = "block";
        has_error = true;
    }
    else
    {
        document.getElementById("msg_admin_pwd").style.display = "none";
    }
    if (/^\s*$/.test(form.elements["admin_vcode"].value))
    {
        document.getElementById("msg_admin_vcode").innerHTML = "<?php echo $lang['admin_vcode_empty']; ?>";
        document.getElementById("msg_admin_vcode").style.display = "block";
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
<form name="loginfrm" action="index.php" method="post" onSubmit="return chklogin(this);">
<p>
<?php echo $lang['admin_name']; ?>:<br /><input type="text" name="admin_name" value="" />
&nbsp;<span id="msg_admin_name" class="alert" style="display:none;"></span><br />
<?php echo $lang['admin_pwd']; ?>:<br /><input type="password" name="admin_pwd" value="" />
&nbsp;<span id="msg_admin_pwd" class="alert" style="display:none;"></span><br />
<?php echo $lang['admin_vcode']; ?>:<br /><input type="text" name="admin_vcode" value="" /><br />
<span id="vcode"></span>
<span id="msg_admin_vcode" class="alert" style="display:none;"></span><br />
<input type="submit" name="submit" value="<?php echo $lang['login']; ?>" />
<input type="hidden" name="ac" value="2" />
<input type="hidden" name="f" value="<?php echo $forward_page; ?>" />
</p>
</form>
<script type="text/javascript" language="javascript" src="jquery-min.js"></script>
<script type="text/javascript" language="javascript">
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
<br /><span class="alert"><?php echo $lang['login_failed']; ?></span>
<?php
}
?>