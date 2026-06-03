<?php
declare(strict_types=1);

/**
 * Authentication — login, logout, session checks, CSRF tokens.
 */

function admin_check(): bool
{
    global $admin_name, $admin_pwd;

    if (
        !isset($_SESSION[SESSION_NS]['admin_name'])
        || !isset($_SESSION[SESSION_NS]['admin_pwd'])
    ) {
        return false;
    }

    return $_SESSION[SESSION_NS]['admin_name'] === $admin_name
        && $_SESSION[SESSION_NS]['admin_pwd'] === $admin_pwd;
}

function admin_login(
    string $login_admin_name,
    string $login_admin_pwd,
    string $login_admin_vcode
): bool {
    global $admin_name, $admin_pwd;

    if ($login_admin_vcode !== ($_SESSION[SESSION_NS]['vcode'] ?? '')) {
        return false;
    }

    if ($admin_name !== $login_admin_name) {
        return false;
    }

    // Support both legacy MD5 and modern bcrypt
    $is_md5 = (strlen($admin_pwd) === 32 && ctype_xdigit($admin_pwd));

    if ($is_md5) {
        if (md5($login_admin_pwd) === $admin_pwd) {
            session_regenerate_id(true);
            $_SESSION[SESSION_NS]['admin_name'] = $admin_name;
            $_SESSION[SESSION_NS]['admin_pwd']  = $admin_pwd;
            return true;
        }
    } elseif (password_verify($login_admin_pwd, $admin_pwd)) {
        session_regenerate_id(true);
        $_SESSION[SESSION_NS]['admin_name'] = $admin_name;
        $_SESSION[SESSION_NS]['admin_pwd']  = $admin_pwd;
        return true;
    }

    return false;
}

function admin_logout(): void
{
    unset($_SESSION[SESSION_NS]);
    session_destroy();
}

/**
 * Lightweight referer check — skip if Referer is absent (common with modern
 * browsers and privacy settings). CSRF tokens provide the real protection.
 * Returns true when Referer is missing, matching, or behind a known proxy.
 */
function referer_check(): bool
{
    // No Referer? Allow — CSRF token handles security.
    if (!isset($_SERVER['HTTP_REFERER']) || $_SERVER['HTTP_REFERER'] === '') {
        return true;
    }

    $referer_host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    if ($referer_host === null) {
        return true; // malformed Referer — allow, CSRF token still enforced
    }

    if ($referer_host === $_SERVER['HTTP_HOST']) {
        return true;
    }
    if (
        isset($_SERVER['HTTP_X_FORWARDED_HOST'])
        && $referer_host === $_SERVER['HTTP_X_FORWARDED_HOST']
    ) {
        return true;
    }

    // Referer from a different host — block (potential CSRF from external site
    // that somehow also has a valid CSRF token, which is near-impossible)
    return false;
}

// ── CSRF protection ──────────────────────────────────────────────

function csrf_token(): string
{
    if (empty($_SESSION[SESSION_NS]['csrf_token'])) {
        $_SESSION[SESSION_NS]['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION[SESSION_NS]['csrf_token'];
}

function csrf_verify(string $token): bool
{
    return hash_equals(
        $_SESSION[SESSION_NS]['csrf_token'] ?? '',
        $token
    );
}
