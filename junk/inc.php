<?php
require __DIR__.'/../src/Cachet.php';
Cachet::register();

$pdo = new Cachet\Backend\PDO(function() {
    return new PDO('sqlite:/tmp/db.sqlite');
});
$cachet = new Cachet\Cache('cache', $pdo);
echo $cachet->increment('foo');

