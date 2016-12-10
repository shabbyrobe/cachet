<?php
require __DIR__.'/config.php';
if (!isset($_GET['key']) || !isset($_GET['value'])) {
    exit;
}
session_start();
$_SESSION[$_GET['key']] = $_GET['value'];
echo "OK";
