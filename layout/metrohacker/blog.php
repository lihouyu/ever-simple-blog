<h1><?php echo h($lang['blog_form']); ?></h1>
<script src="assets/tinymce/tinymce.min.js"></script>
<script>
tinymce.init({
    selector: '#content',
    plugins: 'link image media table code codesample fullscreen emoticons charmap anchor',
    toolbar: 'undo redo | bold italic underline strikethrough | link image media | table | bullist numlist | codesample | emoticons charmap | code fullscreen',
    menubar: false,
    promotion: false,
    branding: false,
    license_key: 'gpl',
    images_upload_url: 'upload.php',
    images_upload_credentials: true,
    automatic_uploads: true,
});
</script>
<form name="blogfrm" action="index.php" method="post" onsubmit="return checkblog(this);">
<input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>" />
<p>
<?php echo h($lang['title']); ?>:<br />
<input type="text" name="title" value="<?php echo h($blog['title'] ?? ''); ?>" size="50" />
&nbsp;<span id="msg_title" class="alert" style="display:none;"></span><br />
<?php echo h($lang['category']); ?>:<br />
<select name="c">
<?php if ($categories):
    for ($i = 0, $size = count($categories); $i < $size; $i++): ?>
<option value="<?php echo h($categories[$i]); ?>"<?php if (($blog['category'] ?? '') === $categories[$i]) echo ' selected'; ?>>
<?php echo h($categories[$i]); ?></option>
<?php endfor;
endif; ?>
</select><br />
<?php echo h($lang['content']); ?>:<br />
<textarea id="content" name="content" rows="18" cols="72"><?php echo ($blog['content'] ?? ''); ?></textarea><br />
<br />
<input type="submit" name="bsubmit" value="<?php echo h($lang['save']); ?>" />
<input type="hidden" name="b" value="<?php echo h($blog['serial'] ?? ''); ?>" />
<input type="hidden" name="pc" value="<?php echo h($blog['category'] ?? ''); ?>" />
<input type="hidden" name="ac" value="8" />
</p>
</form>

<script>
function checkblog(form) {
    var ok = true;
    var el = document.getElementById('msg_title');
    if (/^\s*$/.test(form.title.value)) {
        el.textContent = "<?php echo addslashes($lang['blog_title_empty']); ?>";
        el.style.display = 'block';
        ok = false;
    } else {
        el.style.display = 'none';
    }
    return ok;
}
</script>

<br />
<h1><?php echo h($lang['blog_list']); ?></h1>
<p>
<?php if ($blogs):
    for ($i = 0, $size = count($blogs); $i < $size; $i++): ?>
[&nbsp;<a href="index.php?ac=4&amp;b=<?php echo urlencode($blogs[$i]['serial']); ?>&amp;c=<?php echo urlencode($blogs[$i]['category']); ?>"><?php echo h($lang['edit']); ?></a>&nbsp;]
&nbsp;[&nbsp;<a href="index.php?ac=9&amp;b=<?php echo urlencode($blogs[$i]['serial']); ?>&amp;c=<?php echo urlencode($blogs[$i]['category']); ?>&amp;csrf_token=<?php echo csrf_token(); ?>"><?php echo h($lang['delete']); ?></a>&nbsp;]
&nbsp;<?php echo h($blogs[$i]['title']); ?><br />
<?php endfor;
endif; ?>
</p>
<?php echo $pager_html; ?>
