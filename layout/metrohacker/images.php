<h1><?php echo h($lang['image_manager']); ?></h1>

<?php if ($delete_error): ?>
<p><span class="alert"><?php echo h($lang['image_delete_failed']); ?></span></p>
<?php endif; ?>
<?php if ($has_deleted): ?>
<p><?php echo h($lang['image_deleted']); ?></p>
<?php endif; ?>

<?php if (empty($images)): ?>
<p><?php echo h($lang['no_images']); ?></p>
<?php else: ?>
<div style="display:flex;flex-wrap:wrap;gap:12px;">
<?php foreach ($images as $img): ?>
<div style="text-align:center;width:160px;padding:8px;border:1px solid #444;border-radius:6px;">
    <img src="<?php echo h($img['url']); ?>" style="width:140px;height:100px;object-fit:cover;border-radius:4px;" alt="" />
    <div style="font-size:11px;margin-top:4px;word-break:break-all;"><?php echo h($img['name']); ?></div>
    <div style="font-size:10px;color:#888;"><?php echo number_format($img['size'] / 1024, 1); ?> KB &middot; <?php echo date('Y-m-d', $img['time']); ?></div>
    <a href="index.php?ac=15&amp;img=<?php echo urlencode($img['name']); ?>&amp;csrf_token=<?php echo csrf_token(); ?>"
       onclick="return confirm('<?php echo addslashes($lang['confirm_delete_image']); ?>')"
       style="display:inline-block;margin-top:6px;font-size:11px;color:#f85149;"><?php echo h($lang['delete']); ?></a>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
