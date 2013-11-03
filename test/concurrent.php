<?php
require __DIR__.'/config.php';
throw_on_error();

$options = getopt('w:r:d:ht:x:c:p:');
$usage = "Cachet's hacky concurrent tester

Usage: concurrent.php [-w <workers>] [-r <runs>] 
        [-t <test>] [-x <exclude>] [-d <lockerDelay>] [-c <counterRuns>]
        [-p <outfile>]

This script helps test various aspects of Cachet under simulated heavy
concurrent load.

Options:
  -t <test[,test2]>  Run only tests which match any of the comma separated
                     case insensitive non-delimited regexps passed.
  -x <test[,test2]>  Exclude tests which match any of the comma separated case
                     insensitive non-delimited regexps passed.
  -w <workers>       Number of concurrent worker processes to use
  -r <runs>          Number of times to run the test
  -d <lockerDelay>   For locker tests, how long to wait inside the set method
                     in microseconds. Used to simulate long-running sets.
  -c <counterRuns>   For counter tests, the number of increments or decrements
                     to perform per worker per run.
  -p <outfile>       Profile workers with xdebug_start_trace(). <outfile> will
                     be suffixed with the worker ID.
";
if (array_key_exists('h', $options)) {
    echo $usage;
    exit;
}

$config = (object)[
    'workerCount' => isset($options['w']) ? $options['w'] : 20,
    'runs' => isset($options['r']) ? $options['r'] : 50,
    'delayUsec' => isset($options['d']) ? $options['d'] : 1000,
    'filter' => isset($options['t']) ? explode(",", $options['t']) : null,
    'exclude' => isset($options['x']) ? explode(",", $options['x']) : null,
    'counterRuns' => isset($options['c']) ? $options['c'] : 200,
    'profile' => isset($options['p']) ? $options['p'] : null,
];

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
    'blockingTest'=>function(&$workerState) {
        $start = microtime(true);
        $out = $workerState->cache->blocking(
            'value', 
            null,
            function() { delay(); return 'set value'; },
            $result
        );
        return [$result, $start, microtime(true)];
    },

    'ensureNonOverlappingSet'=>function($workers, $responses, $parentState) {
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

    'createFileLockerCache'=>function($workerState) {
        $backend = new \Cachet\Backend\APC();
        $workerState->cache = new \Cachet\Cache('test', $backend);
        $keyHasher = 'locker_single_hasher';
        $workerState->cache->locker = new \Cachet\Locker\File('/tmp', $keyHasher);
    },

    'checkDump'=> function($workers, $responses) {
        foreach ($responses as $item) {
            echo implode(' ', $item).PHP_EOL;
        }
    },
];

function delay()
{
    global $config;
    usleep($config->delayUsec);
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
    // $redis = redis_create_testing();
    // $redis->flushDb();
    // $backend = new \Cachet\Backend\PHPRedis($redis);
    return $cache;
}

function locker_single_hasher()
{
    return 10000;
}

/*
foreach (new \DirectoryIterator(__DIR__.'/concurrent/') as $item) {
    $name = (string) $item;
    if (!$item->isFile() || $name[0] == '.' || !ends_with($name, '.php')) 
        continue;

    $tests[$testName] = require $item->getPathname(); 
}
*/

$tests = require __DIR__.'/concurrent-tests.php';

$parentPid = posix_getpid();
$workers = [];
$failMessages = [];

$pairs = [];
for ($id=0; $id<$config->workerCount; $id++) {
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
    global $config;
    global $failMessages;

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
        if ($config->filter) {
            $match = false;
            foreach ($config->filter as $f) {
                if (preg_match("/$f/i", $id)) {
                    $match = true;
                    break;
                }
            }
            if (!$match)
                continue;
        }
        
        if ($config->exclude) {
            $match = false;
            foreach ($config->exclude as $e) {
                if (preg_match("/$e/i", $id)) {
                    $match = true;
                    break;
                }
            }
            if ($match)
                continue;
        }

        echo "$id:\n";
        for ($i=0; $i<$config->runs; $i++) {
            $parentState = (object)[];
            if (isset($test['setupParent']))
                $initialState = $test['setupParent']($parentState);
            else
                $initialState = (object)[];

            if (isset($test['setupWorker']))
                call_workers($sockets, 'run_test', [$id, $i, 'setupWorker', $initialState]);

            $responses = call_workers($sockets, 'run_test', [$id, $i, 'test']);

            $result = $test['check']($workerInfo, $responses, $parentState, $initialState);
            if ($result[0])
                report_success($id);
            else
                report_failure($id, $result[1]);
        
            if (isset($test['teardownParent']))
                $test['teardownParent']($parentState, $initialState);

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

    if ($failMessages) {
        $out = [];
        foreach ($failMessages as list($id, $message)) {
            $current = "$id: $message";
            $out[] = $current;
        }
        echo implode("\n", array_unique($out))."\n";
        exit(1);
    }
    else {
        exit(0);
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

function run_test($test, $run, $method, $initialState=null)
{
    global $tests;
    static $workerState;
    static $currentName;

    $name = "$test-$run";
    if ($currentName != $name) {
        $workerState = $initialState ?: ((object)[]);
        $currentName = $name;
    }

    return call_user_func($tests[$test][$method], $workerState);
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
    global $config;

    if ($config->profile)
        xdebug_start_trace($config->profile."-$id"); // XDEBUG_TRACE_COMPUTERIZED);

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

