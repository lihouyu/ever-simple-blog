<h1><?php echo $lang['blog_form']; ?></h1>
<script type="text/javascript" src="jquery-min.js"></script>
<script type="text/javascript" src="tiny_mce/tinymce.gzip.js"></script>
<script type="text/javascript">
<!--
tinymce.init({
    selector: "textarea#content",
    theme: "modern",
    menubar: false,
    image_advtab: true,
    plugins: [
        "link hr charmap anchor pagebreak code fullscreen image media nonbreaking table emoticons textcolor responsivefilemanager"
    ],
    toolbar: [
        "undo redo | cut copy paste | responsivefilemanager | image media | link unlink | anchor emoticons charmap | code fullscreen",
        "fontselect fontsizeselect forecolor backcolor | bold italic underline strikethrough subscript superscript",
        "table | alignleft aligncenter alignright alignjustify | outdent indent | bullist numlist | blockquote hr pagebreak nonbreaking",
    ],
    external_filemanager_path: "<?php echo $context_root; ?>filemanager/",
    filemanager_title: "Responsive Filemanager",
    external_plugins: { "filemanager": "<?php echo $context_root; ?>filemanager/plugin.min.js" },
    filemanager_sort_by: "name",
});

function toggleEditor(id) {
    tinymce.EditorManager.execCommand('mceToggleEditor', true, id);
    //if (!tinymce.EditorManager.get(id)) {
    //	tinymce.EditorManager.execCommand("mceAddControl", true, id);
    //} else {
    //	tinymce.EditorManager.execCommand("mceRemoveControl", true, id);
    //}
}

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
for ($i = 0, $size = count($categories); $i < $size; $i++)
{
?>
<option value="<?php echo $categories[$i]; ?>"<?php if ($blog['category'] == $categories[$i]) echo ' selected="selected"'; ?>>
<?php echo $categories[$i]; ?></option>
<?php
}
}
?>
</select><br />
<?php echo $lang['content']; ?>:
<a href="javascript:toggleEditor('content');" onclick="toggleEditor('content');return false;">[<?php echo $lang['toggle_editor']; ?>]</a>
<br />
<textarea id="content" name="content" rows="18" cols="72"><?php echo $blog['content']; ?></textarea><br />
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
for ($i = 0, $size = count($blogs); $i < $size; $i++)
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
