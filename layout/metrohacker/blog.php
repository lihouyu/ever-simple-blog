<h1><?php echo $lang['blog_form']; ?></h1>
<script type="text/javascript" language="javascript" src="tiny_mce/tiny_mce_gzip.js"></script>
<script type="text/javascript" language="javascript">
tinyMCE_GZ.init({
    plugins : "spellchecker,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,imagemanager,filemanager",
	themes : "advanced",
	languages : "en",
	disk_cache : true,
	debug : false
});
</script>
<script type="text/javascript" language="javascript">
<!--
tinyMCE.init({
    mode : "none",
    theme : "advanced",
    skin : "o2k7",
    plugins : "spellchecker,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,imagemanager,filemanager",
    theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,sub,sup,|,forecolor,backcolor,|,fontselect,fontsizeselect,|,removeformat,styleprops,|,link,unlink,anchor,image",
    theme_advanced_buttons2 : "bullist,numlist,|,justifyleft,justifycenter,justifyright,justifyfull,outdent,indent,blockquote,|,ltr,rtl,|,tablecontrols",
    theme_advanced_buttons3 : "undo,redo,cut,copy,paste,search,replace,|,insertlayer,moveforward,movebackward,absolute,|,insertdate,inserttime,charmap,emotions,iespell,media,advhr,nonbreaking,pagebreak,|,preview,cleanup,code,fullscreen",
    theme_advanced_toolbar_location : "top",
    theme_advanced_toolbar_align : "left",
    theme_advanced_statusbar_location : "bottom",
    theme_advanced_resizing : true,
    button_tile_map : true,
    entity_encoding : "raw",
    verify_html : false
});

function toggleEditor(id) {
    if (!tinyMCE.get(id)) {
        tinyMCE.execCommand("mceAddControl", false, id);
    } else {
        tinyMCE.execCommand("mceRemoveControl", false, id);
    }
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
