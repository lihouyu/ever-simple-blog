<?php
// please make sure folder 'blog', 'comment', 'upload' is writable to web server.
// if not, use 'chmod 777' command on *uix.
// uncheck 'read only' in folder property form on windows.

// your blog site name
$site_name = 'ESiBlog';

// some short description
$site_slogan = 'Ever Simple Blog';

// site style template. default is 'metrohacker'.
$site_tpl = 'metrohacker';

// site default language. available languages: 'en', 'zh-cn'.
$site_lang = 'en';

// blog administrator login name
$admin_name = 'admin';

// blog administrator login password, md5 encrypted. default is '123456'.
$admin_pwd = 'e10adc3949ba59abbe56e057f20f883e';

// blog administrator email
$admin_email = 'admin@yourdomain.tld';

// display specified number of blogs in one page
$blogs_per_page = 15;

// this option allow change site template from uri parameter
$enable_uri_tpl = true;

// the site context root. if your site can be visited via http://www.yourdomain.tld,
// then the value is '/'. if the url is http://www.yourdomain.tld/blog, then
// the value is '/blog/'. keep the trailing '/' at the end.
$context_root = '/';

// whether to use compressed output
$gzip_output = true;

// customize timezone. only works when phpversion > 5.1
// for using the system default timezone, just leave it blank
$your_tz = '';

// side blocks want to load
$blocks = array('category', 'archive', 'search', 'syndication');
?>