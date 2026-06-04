<h1><?php echo h($lang['admin_login']); ?></h1>
<script>
function chklogin(form) {
    var ok = true;
    ['admin_name','admin_pwd','admin_vcode'].forEach(function(id) {
        var el = document.getElementById('msg_' + id);
        if (/^\s*$/.test(form[id].value)) {
            el.style.display = 'block';
            ok = false;
        } else {
            el.style.display = 'none';
        }
    });
    return ok;
}
</script>
<form name="loginfrm" action="index.php" method="post" onsubmit="return chklogin(this);">
<input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>" />
<p>
<?php echo h($lang['admin_name']); ?>:<br /><input type="text" name="admin_name" value="" />
&nbsp;<span id="msg_admin_name" class="alert" style="display:none;"><?php echo h($lang['admin_name_empty']); ?></span><br />
<?php echo h($lang['admin_pwd']); ?>:<br /><input type="password" name="admin_pwd" value="" />
&nbsp;<span id="msg_admin_pwd" class="alert" style="display:none;"><?php echo h($lang['admin_pwd_empty']); ?></span><br />
<?php echo h($lang['admin_vcode']); ?>:<br /><input type="text" name="admin_vcode" value="" /><br />
<span id="vcode"></span>
<span id="msg_admin_vcode" class="alert" style="display:none;"><?php echo h($lang['admin_vcode_empty']); ?></span><br />
<input type="submit" name="submit" value="<?php echo h($lang['login']); ?>" />
<input type="hidden" name="ac" value="2" />
<input type="hidden" name="f" value="<?php echo h($forward_page ?? ''); ?>" />
</p>
</form>
<script>
fetch('vcode.php')
    .then(function(r) { return r.text(); })
    .then(function(html) { document.getElementById('vcode').innerHTML = html; });
</script>
<?php if ($has_error ?? false): ?>
<br /><span class="alert"><?php echo h($lang['login_failed']); ?></span>
<?php endif; ?>
