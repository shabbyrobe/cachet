<?php
require __DIR__.'/config.php';

Cachet::register();
$backend = new Cachet\Backend\PHPRedis('127.0.0.1');
$cache = new Cachet\Cache('foo', $backend);

$tests = [
    'fileSingleLock'=>[
        'keyGenerator'=>function() {
            while (true) yield rand(1, 6);
        },
        'keyHasher'=>function($cache, $key) {
            return 1;
        },
    ],

    'semaphoreSingleLock'=>[
        'keyGenerator'=>function() {
            while (true) yield rand(1, 6);
        },
        'keyHasher'=>function($cache, $key) {
            $file = tempnam(sys_get_temp_dir(), '');
            return ftok($file, '');
        },
    ],
];

$id = $argv[1];

$cnt = 0;
$tot = 0;
while (true) {
    $s = microtime(true);
    $key = rand(1, 6);
    $cache->nonblocking($key, rand(10, 20), function() use ($id, $cnt, $tot, $key) {
        sleep(1);
        file_put_contents("/tmp/pants", time()." ".sprintf("%02d", $id)." - set ".$key.", ".($cnt ? round($cnt / $tot) : 0)." TPS\n", FILE_APPEND);
        return rand();
    });
    $tot += (microtime(true) - $s);
    $cnt++;
}
