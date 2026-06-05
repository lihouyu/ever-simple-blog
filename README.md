# ESiBlog — Ever Simple Blog

> *The Retro Time on Internet*

A flat-file PHP blog engine. No database required — just PHP 8.1+ and a writable filesystem.

**Author:** Li HouYu <lihouyu@phpex.net>, Shanghai, China

## Features

- **Flat-file storage** — no MySQL, no SQLite, just files
- Blog posts with categories, comments, monthly archives, and sticky posts
- Numbered pagination with sliding window
- Breadcrumb navigation (context-aware: category/archive/search)
- WYSIWYG editor (TinyMCE 7) with code samples and file/media upload
- Built-in file manager with multi-type upload, thumbnails, and TinyMCE integration
- Syntax highlighting via highlight.js
- Matrix-style CAPTCHA for comments and login
- Four built-in themes (metrohacker dark, metroscarlet red, metromodern modern dark, metrolight modern light)
- i18n support (English, Simplified Chinese, Japanese, French)
- Admin panel with settings management (edit config via web UI)
- RSS 2.0 feed
- Dynamic `/llms.txt` endpoint for LLM indexing
- CSRF protection, HTML sanitization, bcrypt password hashing

## Requirements

- PHP 8.1 or later with `mbstring`, `gd`, `fileinfo` extensions
- Write permissions on `blog/`, `comment/`, `upload/`, `thumbs/`, and project root

## Project Structure

```
index.php            # Single-file router (entry point)
lib/                 # Core library
  blog.php           #   Blog CRUD + pagination + search + sticky
  category.php       #   Category management
  comment.php        #   Comment CRUD
  auth.php           #   Authentication + CSRF
  utils.php          #   Template helpers + HTML sanitization
layout/              # Themes
  metrohacker/       #   Classic dark (orange accent)
  metroscarlet/      #   Classic red
  metromodern/       #   Modern dark (animations, cards)
  metrolight/        #   Modern light (clean white)
assets/              # Third-party libraries (self-hosted)
  tinymce/           #   TinyMCE 7 (GPL)
  highlight/         #   highlight.js 11 (BSD)
block/               # Sidebar data providers
lang/                # Language packs (en.php, zh-cn.php)
blog/                # Blog post storage
comment/             # Comment storage
upload/              # Uploaded media files
thumbs/              # Auto-generated image thumbnails
MatrixVCode.php      # CAPTCHA generator
rss.php              # RSS 2.0 feed endpoint
upload.php           # File upload handler
filelist.php         # JSON file list for TinyMCE
llms.php             # Dynamic /llms.txt generator
vcode.php            # CAPTCHA image endpoint
migrate-config.php   # Legacy config migration tool
```

## Installation

1. Copy `secret-dist.json` to `secret.json`:
   ```
   cp secret-dist.json secret.json
   ```

2. Edit `secret.json` with your site settings. The default admin password is `123456`.

3. Ensure writable directories:
   ```
   chmod 755 blog comment upload thumbs
   ```

4. Point your web server to this directory. For local testing:
   ```
   php -S localhost:8080
   ```

## Upgrading from ESiBlog 1.x

If you have an existing `secret.php`, run:
```
php migrate-config.php
```

This converts your config to `secret.json`. The old password hash is preserved (MD5). Log in and change your password via Settings to upgrade it to bcrypt.

## Security

- Session cookies use `HttpOnly`, `SameSite=Lax`, and `Secure` (on HTTPS)
- Session ID is regenerated on login
- All forms are protected by CSRF tokens
- Blog content is sanitized on display (scripts, event handlers, and `javascript:` URLs are stripped)
- Input lengths are capped
- `secret.json` is blocked from direct HTTP access via `.htaccess`
- Passwords are hashed with bcrypt (with MD5 fallback for legacy upgrades)

## Included Libraries

| Library | Version | License | Location |
|---|---|---|---|
| TinyMCE | 7.x | GPL-2.0 | `assets/tinymce/` |
| highlight.js | 11.x | BSD-3-Clause | `assets/highlight/` |

## License

ESiBlog original code is free to use. Bundled third-party libraries have their own licenses — see their respective directories.
