<?php
require '/Users/blakewilliams/code/php/cachet/src/Cachet.php';

Cachet::register();

$backend = new Cachet\Backend\PHPRedis('127.0.0.1');
$cache = new Cachet\Cache('foo', $backend);

$cache->locker = new Cachet\Locker\File('/tmp/locks', function($cache, $key) {
    $key = $cache->id."/".implode("/", str_split(md5($key), 8));
    return $key;
});

/*
$cache->locker = new Cachet\Locker\Semaphore(function ($cache, $key) {
    return Cachet\Helper::hashMDHack("$cache->id/$key") % 4;
});
*/

if (!isset($argv[1]))
    die("Pass id\n");

$id = $argv[1];

$cnt = 0;
$tot = 0;
while (true) {
    $s = microtime(true);
    $key = rand(1, 6);
    $cache->nonblocking($key, rand(10, 20), function() use ($id, $cnt, $tot, $key) {
        sleep(1);
        file_put_contents("/tmp/ass", time()." ".sprintf("%02d", $id)." - set ".$key.", ".($cnt ? round($cnt / $tot) : 0)." TPS\n", FILE_APPEND);
        return rand();
    });
    $tot += (microtime(true) - $s);
    $cnt++;
}
