<?php
declare(strict_types=1);

/**
 * Verification code endpoint — returns MatrixVCode HTML + stores answer in session.
 * Called via fetch() from login and comment forms.
 */

error_reporting(0);

session_start();
define('SESSION_NS', 'esiblog');
if (!isset($_SESSION[SESSION_NS])) {
    $_SESSION[SESSION_NS] = [];
}

require_once __DIR__ . '/MatrixVCode.php';

$mvcode = new MatrixVCode();
$vcode = $mvcode->render();

$_SESSION[SESSION_NS]['vcode'] = $vcode;
