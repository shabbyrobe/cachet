<?php
require __DIR__.'/config.php';

$options = getopt('w:r:d:ht:');
$usage = "Usage: concurrent.php [-w <workers>] [-d <delay>] 
        [-r <runs>] [-t <test>]
";
if (array_key_exists('h', $options)) {
    echo $usage;
    exit;
}

$workerCount = isset($options['w']) ? $options['w'] : 30;
$runs = isset($options['r']) ? $options['r'] : 30;
$delayUsec = isset($options['d']) ? $options['d'] : 1000;
$filter = isset($options['t']) ? explode(",", $options['t']) : null;

if (!extension_loaded('redis')) {
    die("Redis extension not loaded\n");
}
elseif (!is_server_listening(
    $GLOBALS['settings']['redis']['host'], 
    $GLOBALS['settings']['redis']['port']
)) {
    die("Redis server not listening\n");
}

$bits = [
    'blockingTest'=>function(&$testState) {
        $start = microtime(true);
        $out = $testState->cache->blocking(
            'value', 
            null,
            function() { delay(); return 'set value'; },
            $result
        );
        return [$result, $start, microtime(true)];
    },

    'ensureNonOverlappingSet'=>function($id, $workers, $responses) {
        $sets = [];
        foreach ($responses as list($result, $start, $end)) {
            if ($result == 'set')
                $sets[] = [$start, $end];
        }
        usort($sets, function($a, $b) {
            if ($a[0] == $b[0]) return 0;
            return $a[0] < $b[0] ? -1 : 1;
        });
        $lastEnd = null;
        $success = true;
        $overlapping = 0;
        foreach ($sets as list($start, $end)) {
            if ($lastEnd && $lastEnd > $start) {
                $overlapping++;
            }
            $lastEnd = $end;
        }
    
        if ($overlapping >= 1)
            return [false, "Detected multiple overlapping sets"];
        else
            return [true];
    },

    'createFileLockerCache'=>function($testState) {
        $testState->cache = cache_create_testing();
        $keyHasher = function() { return 10000; };
        $testState->cache->locker = new \Cachet\Locker\File('/tmp', $keyHasher);
    },

    'checkDump'=> function($id, $workers, $responses) {
        foreach ($responses as $item) {
            echo implode(' ', $item).PHP_EOL;
        }
    },
];

function delay()
{
    global $delayUsec;
    usleep($delayUsec);
}

function report_success($id)
{
    echo ".";
}

function report_failure($id, $message)
{
    global $failMessages;
    echo "F";
    $failMessages[] = [$id, $message];
}

function cache_create_testing()
{
    $redis = redis_create_testing();
    $redis->flushDb();
    $backend = new \Cachet\Backend\PHPRedis($redis);
    $cache = new \Cachet\Cache('test', $backend);
    return $cache;
}

$tests = [
    'lockerBlockingSemaphore'=>[
        'setup'=>function($testState) {
            $testState->cache = cache_create_testing();
            $testState->cache->locker = new \Cachet\Locker\Semaphore(function() { return 10000; });
        },
        'test'=>$bits['blockingTest'],
        'check'=>$bits['ensureNonOverlappingSet'],
    ],

    'lockerBlockingFile'=>[
        'setup'=>$bits['createFileLockerCache'],
        'test'=>$bits['blockingTest'],
        'check'=>$bits['ensureNonOverlappingSet'],
    ],

    'lockerSafeNonBlockingFile'=>[
        'setup'=>function($testState) use ($bits) {
            $cache = $bits['createFileLockerCache']($testState);
            $testState->cache->backend->useBackendExpirations = false;
            $dep = new \Cachet\Dependency\Dummy(false);
            $testState->cache->backend->set(new \Cachet\Item('test', 'value', 'stale', $dep));
        },
        'test'=>function($testState) {
            $cache = $testState->cache;
            $start = microtime(true);
            $out = $testState->cache->safeNonBlocking(
                'value', 
                null,
                function() { delay(); return 'set value'; },
                $result
            );
            return [$result, $start, microtime(true)];
        },
        'check'=>$bits['ensureNonOverlappingSet'],
    ],

    'lockerUnsafeNonBlocking'=>[
        'setup'=>function($testState) use ($bits) {
            $cache = $bits['createFileLockerCache']($testState);
            $testState->cache->backend->useBackendExpirations = false;
        },
        'test'=>function($testState) {
            $cache = $testState->cache;
            $start = microtime(true);
            $setter = function() { delay(); return 'set value'; };
            $out = $testState->cache->unsafeNonBlocking('value', null, $setter, $found, $result);
            $ret = [$result, $start, microtime(true)];
            
            // 10000 is the locker key. this is brittle.
            $cache->locker->acquire($cache, 10000);
            $cache->locker->release($cache, 10000);
            $out = $testState->cache->get('value');
            $ret[] = $out; 
            return $ret;
        },
        'check'=>function($id, $workers, $responses) use ($bits) {
            $result = $bits['ensureNonOverlappingSet']($id, $workers, $responses);
            if (!$result[0])
                return $result;
            foreach ($responses as $response) {
                if ($response[0] == 'set')
                    continue;
                if ($response[3] != 'set value')
                    return [false, "?"];
            }
            return [true];
        },
    ],

    // ensures the APC counter works concurrently
    'apcCounterTest'=>[
        'setup'=>function($testState) {
            apc_clear_cache('user');
            apc_store('counter/count', 0);
            $testState->counter = new \Cachet\Counter\APC;
        },
        'test'=>function($testState) {
            $testState->counter->increment('count');
        },
        'check'=>function($id, $workers, $responses) use ($workerCount) {
            $count = apc_fetch('counter/count'); 
            if ($count != $workerCount)
                return [false, "Count was $count, expected $workerCount"];
            else
                return [true];
        },
    ],

    // ensures the Redis counter works concurrently
    'redisCounterTest'=>[
        'setup'=>function($testState) {
            $redis = redis_create_testing();
            $redis->flushDb();
            $testState->counter = new \Cachet\Counter\PHPRedis($redis);
        },
        'test'=>function($testState) {
            $testState->counter->increment('count');
        },
        'check'=>function($id, $workers, $responses) use ($workerCount) {
            $redis = redis_create_testing();
            $count = $redis->get('counter/count'); 
            if ($count != $workerCount)
                return [false, "Count was $count, expected $workerCount"];
            else
                return [true];
        },
    ],
];

$parentPid = posix_getpid();
$workers = [];
$failMessages = [];

$pairs = [];
for ($id=0; $id<$workerCount; $id++) {
    if (!socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $pair))
        die();

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
    global $runs;
    global $failMessages;
    global $filter;

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
        if ($filter && !in_array($id, $filter))
            continue;

        echo "$id:\n";
        for ($i=0; $i<$runs; $i++) {
            call_workers($sockets, 'run_test', [$id, 'setup']);
            $responses = call_workers($sockets, 'run_test', [$id, 'test']);

            $result = $test['check']($id, $workerInfo, $responses);
            if ($result[0])
                report_success($id);
            else
                report_failure($id, $result[1]);
        }
        echo "\n\n";
    }
 
    foreach ($workers as $worker) {
        socket_write($worker['socket'], serialize(["quit", []]));
    }

    foreach ($workers as $worker) {
        pcntl_wait($worker['pid']);
        socket_close($worker['socket']);
    }

    echo "All workers done\n";

    $last = '';
    foreach ($failMessages as list($id, $message)) {
        $current = "$id: $message\n";
        if ($last != $current)
            echo $current;
        $last = $current;
    }
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

function run_test($name, $method)
{
    global $tests;
    static $testState;
    static $currentName;

    if ($currentName != $name) {
        $testState = (object)[];
        $currentName = $name;
    }

    return call_user_func($tests[$name][$method], $testState);
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
        
        if (!is_callable($method))
            die();

        $return = call_user_func_array($method, $args);
        if (!@socket_write($socket, serialize($return).PHP_EOL))
            die();
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

