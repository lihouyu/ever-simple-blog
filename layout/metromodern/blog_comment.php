<?php if ($blog): ?>
<div class="blog">
<h1><?php echo h($blog['title']); ?><?php if (sticky_is($blog['serial'])): ?> 📌<?php endif; ?></h1>
<div class="htimeauth"><?php echo date('Y-m-d H:i', $blog['timestamp']); ?>
&nbsp;|&nbsp;
<?php echo h($lang['category']); ?>: <a href="index.php?c=<?php echo urlencode($blog['category']); ?>">
<?php echo h($blog['category']); ?></a></div>
<?php echo $blog['content']; ?>
<?php if (admin_check()): ?>
<p class="meta">
&laquo;&nbsp;
<a href="index.php?ac=4&amp;b=<?php echo urlencode($blog['serial']); ?>&amp;c=<?php echo urlencode($blog['category']); ?>"><?php echo h($lang['edit']); ?></a>
&nbsp;&raquo;
</p>
<?php endif; ?>
</div>

<?php if ($comment_enabled): ?>
<div class="blog">
<h1><?php echo h($lang['comment']); ?></h1>
<?php if ($comments):
    for ($i = 0, $size = count($comments); $i < $size; $i++): ?>
<p>
<?php if (admin_check()): ?>
[&nbsp;
<a href="index.php?ac=11&amp;b=<?php echo urlencode($blog['serial']); ?>&amp;c=<?php echo urlencode($blog['category']); ?>&amp;bc=<?php echo urlencode($comments[$i]['serial']); ?>&amp;csrf_token=<?php echo csrf_token(); ?>">
<?php echo h($lang['delete']); ?></a>
&nbsp;]
<?php endif; ?>
&nbsp;<?php echo date('Y-m-d H:i', $comments[$i]['timestamp']); ?>
&nbsp;<?php echo h($comments[$i]['author']); ?>
&nbsp;<?php echo h($lang['said']); ?>
<blockquote>
<?php echo h($comments[$i]['content']); ?>
</blockquote>
</p>
<?php endfor;
endif; ?>
</div>

<div class="blog">
<script>
function chkcomment(form) {
    var ok = true;
    var msgs = {
        comment_author:    '<?php echo addslashes($lang['comment_author_empty']); ?>',
        comment_content:   '<?php echo addslashes($lang['comment_content_empty']); ?>',
        comment_vcode:     '<?php echo addslashes($lang['comment_vcode_empty']); ?>',
    };
    Object.keys(msgs).forEach(function(name) {
        var el = document.getElementById('msg_' + name);
        if (/^\s*$/.test(form[name].value)) {
            el.textContent = msgs[name];
            el.style.display = 'block';
            ok = false;
        } else {
            el.style.display = 'none';
        }
    });
    return ok;
}
</script>
<form name="commentfrm" action="index.php" method="post" onsubmit="return chkcomment(this);">
<input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>" />
<p>
<?php echo h($lang['nickname']); ?>:<br /><input type="text" name="comment_author" value="" />
&nbsp;<span id="msg_comment_author" class="alert" style="display:none;"></span><br />
<?php echo h($lang['comment']); ?>:<br />
<textarea name="comment_content" style="width:64%;height:200px;"></textarea>
&nbsp;<span id="msg_comment_content" class="alert" style="display:none;"></span><br />
<?php echo h($lang['comment_vcode']); ?>:<br /><input type="text" name="comment_vcode" value="" /><br />
<span id="vcode"></span>
<span id="msg_comment_vcode" class="alert" style="display:none;"></span><br />
<input type="submit" name="submit" value="<?php echo h($lang['post_comment']); ?>" />
<input type="hidden" name="ac" value="10" />
<input type="hidden" name="b" value="<?php echo h($blog['serial']); ?>" />
<input type="hidden" name="c" value="<?php echo h($blog['category']); ?>" />
</p>
</form>
<script>
fetch('vcode.php')
    .then(function(r) { return r.text(); })
    .then(function(html) { document.getElementById('vcode').innerHTML = html; });
</script>
<?php if ($has_error ?? false): ?>
<br /><span class="alert"><?php echo h($lang[$error_msg] ?? ''); ?></span>
<?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>
