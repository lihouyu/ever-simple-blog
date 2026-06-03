<h1><?php echo h($lang['category_list']); ?></h1>
<p>
<?php if ($categories):
    for ($i = 0, $size = count($categories); $i < $size; $i++):
        if ($categories[$i] === 'general') continue;
?>
[&nbsp;<a href="index.php?ac=6&amp;c=<?php echo urlencode($categories[$i]); ?>&amp;csrf_token=<?php echo csrf_token(); ?>"><?php echo h($lang['delete']); ?></a>&nbsp;]
&nbsp;<?php echo h($categories[$i]); ?><br />
<?php endfor;
endif; ?>
</p>
<h1><?php echo h($lang['new_category']); ?></h1>
<form name="catefrm" action="index.php" method="post">
<input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>" />
<p>
<?php echo h($lang['category_name']); ?>:<br /><input type="text" name="c" value="" />
&nbsp;<span id="msg_category" class="alert" style="display:none;"></span><br />
<input type="submit" name="submit" value="<?php echo h($lang['add']); ?>" />
<input type="hidden" name="ac" value="7" />
</p>
</form>
<script>
document.forms.catefrm.onsubmit = function() {
    var el = document.getElementById('msg_category');
    if (/^\s*$/.test(this.c.value)) {
        el.textContent = '<?php echo addslashes($lang['category_name_empty']); ?>';
        el.style.display = 'block';
        return false;
    }
    el.style.display = 'none';
    return true;
};
</script>
