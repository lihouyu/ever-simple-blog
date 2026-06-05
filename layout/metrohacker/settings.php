<h1><?php echo h($lang['general_settings']); ?></h1>
<script>
function checksettings(form) {
    var ok = true;
    var checks = {
        admin_name:      '<?php echo addslashes($lang['admin_name_empty']); ?>',
        admin_email:     '<?php echo addslashes($lang['admin_email_empty']); ?>',
        site_name:       '<?php echo addslashes($lang['site_name_empty']); ?>',
        blogs_per_page:  '<?php echo addslashes($lang['blogs_per_page_invalid']); ?>',
    };
    Object.keys(checks).forEach(function(name) {
        var el = document.getElementById('msg_' + name);
        if (/^\s*$/.test(form[name].value)) {
            el.textContent = checks[name];
            el.style.display = 'block';
            ok = false;
        } else {
            el.style.display = 'none';
        }
    });
    // blogs_per_page extra check
    if (!/^[0-9]+$/.test(form.blogs_per_page.value)) {
        document.getElementById('msg_blogs_per_page').textContent = '<?php echo addslashes($lang['blogs_per_page_invalid']); ?>';
        document.getElementById('msg_blogs_per_page').style.display = 'block';
        ok = false;
    } else if (parseInt(form.blogs_per_page.value, 10) < 5) {
        document.getElementById('msg_blogs_per_page').textContent = '<?php echo addslashes($lang['blogs_per_page_min']); ?>';
        document.getElementById('msg_blogs_per_page').style.display = 'block';
        ok = false;
    }
    // password match
    if (form.admin_pwd.value !== form.confirm_admin_pwd.value) {
        document.getElementById('msg_admin_pwd').textContent = '<?php echo addslashes($lang['admin_pwd_mismatch']); ?>';
        document.getElementById('msg_admin_pwd').style.display = 'block';
        ok = false;
    } else {
        document.getElementById('msg_admin_pwd').style.display = 'none';
    }
    // context root fallback
    if (/^\s*$/.test(form.context_root.value)) {
        form.context_root.value = '/';
    }
    return ok;
}
</script>
<?php if ($has_error ?? false): ?>
<span class="alert"><?php echo h($lang['admin_pwd_mismatch']); ?></span><br />
<?php endif; ?>
<form name="settingsfrm" action="index.php" method="post" onsubmit="return checksettings(this);">
<input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>" />
<p>
<?php echo h($lang['admin_name']); ?>:<br /><input type="text" name="admin_name" value="<?php echo h($admin_name); ?>" />
&nbsp;<span id="msg_admin_name" class="alert" style="display:none;"></span><br />
<?php echo h($lang['admin_pwd']); ?>: <em><?php echo h($lang['change_passwd']); ?></em><br /><input type="password" name="admin_pwd" value="" /><br />
<?php echo h($lang['confirm_admin_pwd']); ?>:<br /><input type="password" name="confirm_admin_pwd" value="" />
&nbsp;<span id="msg_admin_pwd" class="alert" style="display:none;"></span><br />
<?php echo h($lang['admin_email']); ?>:<br /><input type="text" name="admin_email" value="<?php echo h($admin_email); ?>" size="32" />
&nbsp;<span id="msg_admin_email" class="alert" style="display:none;"></span><br />
<?php echo h($lang['site_name']); ?>:<br /><input type="text" name="site_name" value="<?php echo h($site_name); ?>" />
&nbsp;<span id="msg_site_name" class="alert" style="display:none;"></span><br />
<?php echo h($lang['site_slogan']); ?>:<br /><input type="text" name="site_slogan" value="<?php echo h($site_slogan); ?>" size="64" /><br />
<?php echo h($lang['your_tz']); ?>:<br /><input type="text" name="your_tz" value="<?php echo h($your_tz); ?>" />
&nbsp;<a href="https://www.php.net/manual/en/timezones.php" title="<?php echo h($lang['tz_list']); ?>" target="_blank" rel="noopener"><?php echo h($lang['tz_list']); ?>&nbsp;&raquo;</a><br />
<?php echo h($lang['site_tpl']); ?>:<br />
<select name="site_tpl">
<?php if ($tpls):
    for ($i = 0, $size = count($tpls); $i < $size; $i++): ?>
<option value="<?php echo h($tpls[$i]); ?>"<?php if ($site_tpl === $tpls[$i]) echo ' selected'; ?>>
<?php echo h($tpls[$i]); ?></option>
<?php endfor;
endif; ?>
</select><br />
<?php echo h($lang['site_lang']); ?>:<br />
<select name="site_lang">
<?php if ($langs):
    for ($i = 0, $size = count($langs); $i < $size; $i++): ?>
<option value="<?php echo h($langs[$i]); ?>"<?php if ($site_lang === $langs[$i]) echo ' selected'; ?>>
<?php echo h($langs[$i]); ?></option>
<?php endfor;
endif; ?>
</select><br />
<?php echo h($lang['blogs_per_page']); ?>:<br /><input type="text" name="blogs_per_page" value="<?php echo $blogs_per_page; ?>" size="4" />
&nbsp;<span id="msg_blogs_per_page" class="alert" style="display:none;"></span><br />
<?php echo h($lang['enable_uri_tpl']); ?>:<br /><input type="checkbox" name="enable_uri_tpl" value="1"<?php if ($enable_uri_tpl) echo ' checked'; ?> /><br />
<?php echo h($lang['context_root']); ?>:<br /><input type="text" name="context_root" value="<?php echo h($context_root); ?>" /><br />
<?php echo h($lang['gzip_output']); ?>:<br /><input type="checkbox" name="gzip_output" value="1"<?php if ($gzip_output) echo ' checked'; ?> /><br />
<?php echo h($lang['comment_enabled']); ?>:<br /><input type="checkbox" name="comment_enabled" value="1"<?php if ($comment_enabled) echo ' checked'; ?> /><br />
<?php echo h($lang['blocks']); ?>:<br /><input type="text" name="blocks" value="<?php echo h($block_str); ?>" size="64" /><br />
<?php echo h($lang['avail_blocks']); ?>:<br /><strong><?php echo h($avail_blocks_str); ?></strong><br />
<input type="submit" name="submit" value="<?php echo h($lang['save']); ?>" />
<input type="hidden" name="ac" value="13" />
</p>
</form>
