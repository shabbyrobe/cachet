<?php
require __DIR__.'/../../../src/Cachet.php';
Cachet::register();

$backend = new Cachet\Backend\APC();
$cache = new Cachet\Cache('session', $backend);
Cachet\SessionHandler::register($cache);
