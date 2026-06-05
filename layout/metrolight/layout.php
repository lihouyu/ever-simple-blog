<!DOCTYPE html>
<html lang="<?php echo h($site_lang); ?>">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?php echo h($page_title); ?> :: <?php echo h($site_name); ?></title>
<link rel="stylesheet" href="layout/<?php echo h($site_tpl); ?>/style.css" />
<link rel="stylesheet" href="vcode.css" />
<link rel="stylesheet" href="assets/highlight/github-dark.min.css" />
<link rel="alternate" type="application/rss+xml" title="RSS feed for <?php echo h($site_name); ?>" href="rss.php" />
<script src="assets/highlight/highlight.min.js"></script>
</head>

<body>

<a id="top"></a>

<?php if ($sys_err): ?>
<div id="global_err">
    <div id="global_err_msg"><?php echo h($sys_err_msg); ?></div>
</div>
<div class="spacer"></div>
<?php endif; ?>

<div id="outer">
<div id="location"><strong><?php echo h($site_name); ?></strong>&nbsp;[&nbsp;<?php echo h($site_slogan); ?>&nbsp;]</div>
<div class="orange"></div>
<div id="topnav">
<a href="index.php"><?php echo h($lang['home']); ?></a>
<a href="mailto:<?php echo h($admin_email); ?>"><?php echo h($lang['contact']); ?></a>
<?php if (admin_check()): ?>
<a href="#">|</a>
<a href="index.php?ac=3"><?php echo h($lang['category']); ?></a>
<a href="index.php?ac=4"><?php echo h($lang['blog']); ?></a>
<a href="index.php?ac=14"><?php echo h($lang['files']); ?></a>
<a href="index.php?ac=12"><?php echo h($lang['settings']); ?></a>
<a href="index.php?ac=5"><?php echo h($lang['logout']); ?></a>
<?php endif; ?>
</div>

<div class="clearer"></div>

<div id="right">
<?php if (!empty($breadcrumb)): ?>
<div id="breadcrumb">
<?php foreach ($breadcrumb as $i => $crumb): ?>
<?php if ($i > 0): ?>&nbsp;&rsaquo;&nbsp;<?php endif; ?>
<?php if ($crumb['url'] !== '' && $i < count($breadcrumb) - 1): ?>
<a href="<?php echo h($crumb['url']); ?>"><?php echo h($crumb['label']); ?></a>
<?php else: ?>
<span><?php echo h($crumb['label']); ?></span>
<?php endif; ?>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php include_once $page_body; ?>
</div>

<div id="left">
<?php load_side_blocks(); ?>
</div>
</div>

<div id="footer">
Copyright &copy; <?php echo date('Y') . ' ' . h($site_name); ?>. powered by <a href="https://github.com/lihouyu/ever-simple-blog">ESiBlog</a>.
&nbsp;
<?php if (!admin_check()): ?>
<a href="index.php?ac=1"><?php echo h($lang['login']); ?></a>&nbsp;&raquo;
<?php endif; ?>
</div>

<script>
// Convert legacy GeSHi <pre> blocks to highlight.js format
document.querySelectorAll('pre[class^="_geshi_"]').forEach(function(pre) {
    var lang = pre.className.replace('_geshi_', '');
    var code = document.createElement('code');
    code.className = 'language-' + lang;
    code.textContent = pre.textContent || '';
    pre.textContent = '';
    pre.appendChild(code);
    pre.removeAttribute('class');
});
hljs.highlightAll();
</script>

</body>
</html>
