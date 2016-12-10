<?php
require __DIR__.'/../../../src/Cachet.php';
Cachet::register();

if (extension_loaded('apcu')) {
    $backend = new Cachet\Backend\APCU();
} else if (extension_loaded('apc')) {
    $backend = new Cachet\Backend\APC();
} else {
    throw new \Exception('No supported backend available for session testing');
}

$cache = new Cachet\Cache('session', $backend);
Cachet\SessionHandler::register($cache);
