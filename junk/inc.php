<?php
require __DIR__.'/../vendor/autoload.php';

$pdo = new Cachet\Backend\PDO(function() {
    return new PDO('sqlite:/tmp/db.sqlite');
});
$cachet = new Cachet\Cache('cache', $pdo);
echo $cachet->increment('foo');

