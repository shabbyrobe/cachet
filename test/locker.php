<?php
require __DIR__.'/config.php';

Cachet::register();
$backend = redis_create_testing(); 

$parentPid = posix_getpid();
$workers = [];

$GLOBALS['semFile'] = sys_get_temp_dir().'/'.uniqid('lockertest', true);
touch($GLOBALS['semFile']);
for ($i=0; $i<$workerCount; $i++) {
    $childPid = pcntl_fork();
    if ($childPid) {
        $workers[] = $childPid;
        return run_parent($workers, $argv);
    }
    else {
        return run_worker($argv);
    }
}

function run_parent($workers, $argv)
{
    $sem = tester_sem_get();
    foreach ($workers as $pid)
        pcntl_wait($pid);

    echo "All workers done\n";
}

function run_worker(


/*
$tests = [
    'fileSingleLock'=>[
        'keyGenerator'=>function() {
            while (true) yield rand(1, 6);
        },
        1;5P
        1;5Q
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
*/

