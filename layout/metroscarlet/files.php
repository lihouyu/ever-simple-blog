<h1><?php echo h($lang['file_manager']); ?></h1>

<?php if ($uploaded ?? false): ?>
<p style="color: var(--accent);"><?php echo h($lang['file_uploaded']); ?></p>
<?php endif; ?>
<?php if ($delete_error): ?>
<p><span class="alert"><?php echo h($lang['file_delete_failed']); ?></span></p>
<?php endif; ?>
<?php if ($has_deleted): ?>
<p><?php echo h($lang['file_deleted']); ?></p>
<?php endif; ?>

<!-- Upload form -->
<form action="index.php" method="post" enctype="multipart/form-data" style="margin-bottom:18px;padding:12px;border:1px dashed var(--border);border-radius:6px;">
<input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>" />
<input type="hidden" name="ac" value="16" />
<strong><?php echo h($lang['upload_file']); ?>:</strong>
<input type="file" name="file" style="margin:0 8px;" />
<input type="text" name="title" placeholder="<?php echo h($lang['custom_filename']); ?>" size="24" style="margin:0 8px;" />
<input type="submit" value="<?php echo h($lang['upload']); ?>" />
<span style="font-size:11px;color:var(--text-dim);margin-left:8px;"><?php echo h($lang['max_50mb']); ?></span>
</form>

<?php if (empty($files)): ?>
<p><?php echo h($lang['no_files']); ?></p>
<?php else: ?>
<div style="display:flex;flex-wrap:wrap;gap:10px;">
<?php foreach ($files as $f):
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $is_img = in_array($ext, ['jpg','jpeg','png','gif','webp','svg']);
    $icon = match($ext) {
        'pdf' => '📄', 'zip','rar','7z','gz','tar' => '📦',
        'doc','docx' => '📝', 'xls','xlsx' => '📊', 'ppt','pptx' => '📽️',
        'mp3','wav','ogg' => '🎵', 'mp4','webm' => '🎬',
        'txt','md','csv' => '📃', 'json','xml' => '📋',
        default => '📁',
    };
    $size_kb = $f['size'] / 1024;
    $size_str = $size_kb > 1024 ? number_format($size_kb / 1024, 1) . ' MB' : number_format($size_kb, 1) . ' KB';
?>
<div style="text-align:center;width:140px;padding:8px;border:1px solid var(--border);border-radius:6px;background:var(--card-alt);">
    <?php if ($is_img): ?>
    <img src="<?php echo h($f['url']); ?>" style="width:120px;height:80px;object-fit:cover;border-radius:4px;" alt="" />
    <?php else: ?>
    <div style="width:120px;height:80px;display:flex;align-items:center;justify-content:center;font-size:36px;background:rgba(0,0,0,0.1);border-radius:4px;"><?php echo $icon; ?></div>
    <?php endif; ?>
    <div style="font-size:10px;margin-top:4px;word-break:break-all;line-height:1.2;"><?php echo h($f['name']); ?></div>
    <div style="font-size:9px;color:var(--text-dim);"><?php echo $size_str; ?> &middot; <?php echo date('Y-m-d', $f['time']); ?></div>
    <div style="margin-top:4px;">
        <a href="<?php echo h($f['url']); ?>" target="_blank" style="font-size:10px;margin-right:8px;"><?php echo h($lang['view']); ?></a>
        <a href="#" onclick="if(window.opener&&window.opener.filePickerCallback){window.opener.filePickerCallback('<?php echo addslashes($f['url']); ?>','<?php echo addslashes($f['name']); ?>');window.close();return false;}" style="font-size:10px;color:var(--accent);margin-right:8px;">pick</a>
        <a href="index.php?ac=15&amp;file=<?php echo urlencode($f['name']); ?>&amp;csrf_token=<?php echo csrf_token(); ?>"
           onclick="return confirm('<?php echo addslashes($lang['confirm_delete_file']); ?>')"
           style="font-size:10px;color:#f85149;"><?php echo h($lang['delete']); ?></a>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
