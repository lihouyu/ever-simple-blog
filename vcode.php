<?php
error_reporting(0);

session_start();
if (!isset($_SESSION[$_SERVER['HTTP_HOST']])) {
    $_SESSION[$_SERVER['HTTP_HOST']] = array();
}

include_once 'MatrixVCode.php';

$mvcode = new MatrixVCode();
$vcode = $mvcode->render();

$_SESSION[$_SERVER['HTTP_HOST']]['vcode'] = $vcode;
?>