<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title><?php echo $site_name; ?></title>
<link rel="stylesheet" type="text/css" href="layout/metrohacker/style.css" />
<link rel="stylesheet" type="text/css" href="vcode.css" />
<link rel="alternate" type="application/rss+xml" title="RSS feed for bloggem.net" href="rss.php" />
</head>

<body>

<a id="top"></a>

<?php
if ($sys_err)
{
?>
<div id="global_err">
    <div id="global_err_msg"><?php echo $sys_err_msg; ?></div>
</div>
<div class="spacer"></div>
<?php
}
?>

<div id="outer">
<div id="location"><strong><?php echo $site_name; ?></strong>&nbsp;[&nbsp;<?php echo $site_slogan; ?>&nbsp;]</div>
<div class="orange"></div>
<div id="topnav">
<a href="index.php"><?php echo $lang['home']; ?></a>
<a href="mailto:<?php echo $admin_email; ?>"><?php echo $lang['contact']; ?></a>
<?php
if (admin_check())
{
?>
<a href="#">|</a>
<a href="index.php?ac=3"><?php echo $lang['category']; ?></a>
<a href="index.php?ac=4"><?php echo $lang['blog']; ?></a>
<a href="index.php?ac=12"><?php echo $lang['settings']; ?></a>
<a href="index.php?ac=5"><?php echo $lang['logout']; ?></a>
<?php
}
?>
</div>

<div class="clearer"></div>

<div id="right">
<?php
include_once $page_body;
?>
</div>

<div id="left">

<?php
load_side_blocks();
?>

</div>
</div>

<div id="footer">
Copyright &copy; <?php echo date('Y').' '.$site_name; ?>. powered by <a href="http://www.bloggem.net">ESiBlog</a>.
&nbsp;
<?php
if (!admin_check())
{
?>
<a href="index.php?ac=1"><?php echo $lang['login']; ?></a>&nbsp;&raquo;
<?php
}
?>
</div>

</body>
</html>