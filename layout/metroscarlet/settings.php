<h1><?php echo $lang['general_settings']; ?></h1>
<script type="text/javascript">
<!--
function checksettings(form)
{
    var has_error = false;
    
    // check admin name
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
    // check admin password
    if (form.elements["admin_pwd"].value != form.elements["confirm_admin_pwd"].value)
    {
        document.getElementById("msg_admin_pwd").innerHTML = "<?php echo $lang['admin_pwd_mismatch']; ?>";
        document.getElementById("msg_admin_pwd").style.display = "block";
        has_error = true;
    }
    else
    {
        document.getElementById("msg_admin_pwd").style.display = "none";
    }
    // check admin e-mail
    if (/^\s*$/.test(form.elements["admin_email"].value))
    {
        document.getElementById("msg_admin_email").innerHTML = "<?php echo $lang['admin_email_empty']; ?>";
        document.getElementById("msg_admin_email").style.display = "block";
        has_error = true;
    }
    else
    {
        document.getElementById("msg_admin_email").style.display = "none";
    }
    // check site name
    if (/^\s*$/.test(form.elements["site_name"].value))
    {
        document.getElementById("msg_site_name").innerHTML = "<?php echo $lang['site_name_empty']; ?>";
        document.getElementById("msg_site_name").style.display = "block";
        has_error = true;
    }
    else
    {
        document.getElementById("msg_site_name").style.display = "none";
    }
    // check blogs per page
    if (!/^[0-9]+$/.test(form.elements["blogs_per_page"].value))
    {
        document.getElementById("msg_blogs_per_page").innerHTML = "<?php echo $lang['blogs_per_page_invalid']; ?>";
        document.getElementById("msg_blogs_per_page").style.display = "block";
        has_error = true;
    }
    else if (form.elements["blogs_per_page"].value < 5) {
        document.getElementById("msg_blogs_per_page").innerHTML = "<?php echo $lang['blogs_per_page_min']; ?>";
        document.getElementById("msg_blogs_per_page").style.display = "block";
        has_error = true;
    }
    else
    {
        document.getElementById("msg_blogs_per_page").style.display = "none";
    }
    // check context root
    if (/^\s*$/.test(form.elements["context_root"].value))
    {
        form.elements["context_root"].value = "/";
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
<?php
if ($has_error)
{
?>
<span class="alert"><?php echo $lang['admin_pwd_mismatch']; ?></span><br />
<?php
}
?>
<form name="settingsfrm" action="index.php" method="post" onSubmit="return checksettings(this);">
<p>
<?php echo $lang['admin_name']; ?>:<br /><input type="text" name="admin_name" value="<?php echo $admin_name; ?>" />
&nbsp;<span id="msg_admin_name" class="alert" style="display:none;"></span><br />
<?php echo $lang['admin_pwd']; ?>:<br /><input type="password" name="admin_pwd" value="" /><br />
<?php echo $lang['confirm_admin_pwd']; ?>:<br /><input type="password" name="confirm_admin_pwd" value="" />
&nbsp;<span id="msg_admin_pwd" class="alert" style="display:none;"></span><br />
<?php echo $lang['admin_email']; ?>:<br /><input type="text" name="admin_email" value="<?php echo $admin_email; ?>" size="32" />
&nbsp;<span id="msg_admin_email" class="alert" style="display:none;"></span><br />
<?php echo $lang['site_name']; ?>:<br /><input type="text" name="site_name" value="<?php echo $site_name; ?>" />
&nbsp;<span id="msg_site_name" class="alert" style="display:none;"></span><br />
<?php echo $lang['site_slogan']; ?>:<br /><input type="text" name="site_slogan" value="<?php echo $site_slogan; ?>" size="64" /><br />
<?php echo $lang['your_tz']; ?>:<br /><input type="text" name="your_tz" value="<?php echo $your_tz; ?>" />
&nbsp;<a href="http://www.php.net/manual/en/timezones.php" title="<?php echo $lang['tz_list']; ?>" target="_blank"><?php echo $lang['tz_list']; ?>&nbsp;&raquo;</a><br />
<?php echo $lang['site_tpl']; ?>:<br />
<select name="site_tpl">
<?php
if ($tpls)
{
for ($i = 0, $size = count($tpls); $i < $size; $i++)
{
?>
<option value="<?php echo $tpls[$i]; ?>"<?php if ($site_tpl == $tpls[$i]) echo ' selected="selected"'; ?>>
<?php echo $tpls[$i]; ?></option>
<?php
}
}
?>
</select><br />
<?php echo $lang['site_lang']; ?>:<br />
<select name="site_lang">
<?php
if ($langs)
{
for ($i = 0, $size = count($langs); $i < $size; $i++)
{
?>
<option value="<?php echo $langs[$i]; ?>"<?php if ($site_lang == $langs[$i]) echo ' selected="selected"'; ?>>
<?php echo $langs[$i]; ?></option>
<?php
}
}
?>
</select><br />
<?php echo $lang['blogs_per_page']; ?>:<br /><input type="text" name="blogs_per_page" value="<?php echo $blogs_per_page; ?>" size="4" />
&nbsp;<span id="msg_blogs_per_page" class="alert" style="display:none;"></span><br />
<?php echo $lang['enable_uri_tpl']; ?>:<br /><input type="checkbox" name="enable_uri_tpl" value="1"<?php if ($enable_uri_tpl) echo ' checked="checked"'; ?> /><br />
<?php echo $lang['context_root']; ?>:<br /><input type="text" name="context_root" value="<?php echo $context_root; ?>" /><br />
<?php echo $lang['gzip_output']; ?>:<br /><input type="checkbox" name="gzip_output" value="1"<?php if ($gzip_output) echo ' checked="checked"'; ?> /><br />
<?php echo $lang['blocks']; ?>:<br /><input type="text" name="blocks" value="<?php echo $block_str; ?>" size="64" /><br />
<?php echo $lang['avail_blocks']; ?>:<br /><strong><?php echo $avail_blocks_str; ?></strong><br />
<input type="submit" name="submit" value="<?php echo $lang['save']; ?>" />
<input type="hidden" name="ac" value="13" />
</p>
</form>