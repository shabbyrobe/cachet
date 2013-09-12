<?php
require __DIR__.'/config.php';
if (!isset($_GET['key']))
    exit;

session_start();
if (isset($_SESSION[$_GET['key']]))
    echo $_SESSION[$_GET['key']];
