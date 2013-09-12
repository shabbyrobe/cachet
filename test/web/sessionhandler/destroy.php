<?php
require __DIR__.'/config.php';
session_start();
session_destroy();
echo "OK";
