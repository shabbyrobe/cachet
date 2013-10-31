<?php
require __DIR__.'/config.php';

$workerCount = 20;

$tests = [
    'fileLocker'=>[
        'test'=>function() {
            $start = microtime(true);
            $redis = redis_create_testing();
            $redis->flushDb();
            $backend = new \Cachet\Backend\PHPRedis($redis);
            $cache = new \Cachet\Cache('test', $backend);
            $cache->locker = new \Cachet\Locker\Semaphore(function() { return 10000; });

            $out = $cache->blocking(
                'value', 
                null,
                function() { sleep(0.5); return 'foo'; },
                $result
            );

            return [$result, $start, microtime(true)];
        },
        'check'=>function($workers, $responses) {
            var_dump($responses);
        },
    ],
];

$parentPid = posix_getpid();
$workers = [];

$pairs = [];
for ($id=0; $id<$workerCount; $id++) {
    if (!socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $pair))
        die("Oops");

    $childPid = pcntl_fork();
    if ($childPid) {
        socket_close($pair[1]);
        $workers[] = ['id'=>$id, 'pid'=>$childPid, 'socket'=>$pair[0], 'socketId'=>intval($pair[0])];
    }
    else {
        socket_close($pair[0]);
        return run_worker_process($id, $pair[1]);
    }
}
return run_parent_process($workers, $argv);

function run_parent_process($workers, $argv)
{
    global $tests;

    $sockets = [];
    $workerInfo = [];
    foreach ($workers as $id=>$worker) {
        $workerInfo[$id] = [
            'id'=>$worker['id'],
            'pid'=>$worker['pid'],
            'socketId'=>$worker['socketId']
        ];
        $sockets[$id] = $worker['socket'];
    }
    collect_responses($sockets);

    foreach ($tests as $id=>$test) {
        // var_dump(call_workers($sockets, 'hello'));
        $responses = call_workers($sockets, 'run_test', [$id]);
        $test['check']($workerInfo, $responses);
    }
    
    foreach ($workers as $worker) {
        socket_write($worker['socket'], serialize(["quit", []]));
    }

    foreach ($workers as $worker) {
        pcntl_wait($worker['pid']);
        socket_close($worker['socket']);
    }

    echo "All workers done\n";
}

function hello()
{
    return "Hello world";
}

function call_workers($sockets, $method, $args=[])
{ 
    $index = [];
    foreach ($sockets as $id=>$socket)
        $index[intval($socket)] = $id;

    foreach ($sockets as $socket)
        socket_write($socket, serialize([$method, $args]));
    
    return collect_responses($sockets, $index);
}

function run_test($name)
{
    global $tests;
    return call_user_func($tests[$name]['test']);
}

function collect_responses($sockets, $index=null)
{
    if (!$index) {
        $index = [];
        foreach ($sockets as $id=>$socket)
            $index[intval($socket)] = $id;
    }

    $socketCount = count($sockets);
    $answered = 0;
    $responses = [];
    foreach ($sockets as $id=>$socket)
        $responses[$id] = "";

    while (true) {
        $readers = $sockets;
        $ready = socket_select($readers, $w, $x, 10);
        if (!$ready)
            continue;

        foreach ($readers as $reader) {
            $buf = socket_read($reader, 8192);     
            $responses[$index[intval($reader)]] .= $buf;
            if (substr($buf, -1) == "\n")
                $answered++;
        }
        if ($answered == $socketCount)
            break;
    }
    
    foreach ($responses as &$response)
        $response = unserialize($response);
     
    return $responses;
}

function run_worker_process($id, $socket)
{
    socket_write($socket, serialize('ready').PHP_EOL);
    while (true) {
        $read = [$socket];
        $changed = socket_select($read, $write, $x, 1);
        if (!$changed)
            continue;

        $cmd = socket_read($socket, 8192);
        list($method, $args) = unserialize($cmd);
        if ($method == 'quit')
            break;

        $return = call_user_func_array($method, $args);
        socket_write($socket, serialize($return).PHP_EOL);
    }
    socket_close($socket);
}

/*

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

