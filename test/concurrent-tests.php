<?php
return [
    'lockerBlockingSemphore'=>[
        'setupParent'=>function() {
            apcu_clear_cache();
        },
        'setupWorker'=>function($workerState) {
            $backend = new \Cachet\Backend\APCU();
            $workerState->cache = new \Cachet\Cache('test', $backend);
            $workerState->cache->locker = new \Cachet\Locker\Semaphore('locker_single_hasher');
        },
        'test'=>$bits['blockingTest'],
        'check'=>$bits['ensureNonOverlappingSet'],
    ],

    'lockerBlockingFile'=>[
        'setupParent'=>function() {
            apcu_clear_cache();
        },
        'setupWorker'=>$bits['createFileLockerCache'],
        'test'=>$bits['blockingTest'],
        'check'=>$bits['ensureNonOverlappingSet'],
    ],

    'lockerSafeNonBlockingFile'=>[
        'setupParent'=>function() {
            apcu_clear_cache();
        },
        'setupWorker'=>function($workerState) use ($bits) {
            $cache = $bits['createFileLockerCache']($workerState);
            $workerState->cache->backend->useBackendExpirations = false;
            $dep = new \Cachet\Dependency\Dummy(false);
            $workerState->cache->backend->set(new \Cachet\Item('test', 'value', 'stale', $dep));
        },
        'test'=>function($workerState) {
            $cache = $workerState->cache;
            $start = microtime(true);
            $out = $workerState->cache->safeNonBlocking(
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
        'setupParent'=>function() {
            apcu_clear_cache();
        },
        'setupWorker'=>function($workerState) use ($bits) {
            $cache = $bits['createFileLockerCache']($workerState);
            $workerState->cache->backend->useBackendExpirations = false;
        },
        'test'=>function($workerState) {
            $cache = $workerState->cache;
            $start = microtime(true);
            $setter = function() { delay(); return 'set value'; };
            $out = $workerState->cache->unsafeNonBlocking('value', null, $setter, $found, $result);
            $ret = [$result, $start, microtime(true)];
            
            // 10000 is the locker key. this is brittle.
            $cache->locker->acquire($cache, 10000);
            $cache->locker->release($cache, 10000);
            $out = $workerState->cache->get('value');
            $ret[] = $out; 
            return $ret;
        },
        'check'=>function($workers, $responses, $parentState) use ($bits) {
            $result = $bits['ensureNonOverlappingSet']($workers, $responses, $parentState);
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

    'apcBrokenCounterTest'=>[
        'setupParent'=>function() {
            apcu_clear_cache();
        },
        'setupWorker'=>function($workerState) {
            $workerState->counter = new \Cachet\Counter\APCU;
        },
        'test'=>function($workerState) use ($config) {
            for ($i=0; $i<$config->counterRuns; $i++)
                $workerState->counter->increment('count');
        },
        'check'=>function($workers, $responses, $parentState) use ($config) {
            $count = apcu_fetch('counter/count'); 
            $expected = $config->workerCount * $config->counterRuns;
            if ($count != $expected)
                return [false, "Count was $count, expected $expected"];
            else
                return [true];
        },
    ],

    'apcuLockedCounterTest'=>[
        'setupParent'=>function() {
            apcu_clear_cache();
        },
        'setupWorker'=>function($workerState) {
            $workerState->counter = new \Cachet\Counter\APCU;
            $workerState->counter->locker = new \Cachet\Locker\Semaphore();
        },
        'test'=>function($workerState) use ($config) {
            for ($i=0; $i<$config->counterRuns; $i++)
                $workerState->counter->increment('count');
        },
        'check'=>function($workers, $responses, $parentState) use ($config) {
            $count = apcu_fetch('counter/count'); 
            $expected = $config->workerCount * $config->counterRuns;
            if ($count != $expected)
                return [false, "Count was $count, expected $expected"];
            else
                return [true];
        },
    ],

    'xcacheCounterTest'=>[
        'setupParent'=>function() {
            xcache_unset_by_prefix('counter/');
        },
        'setupWorker'=>function($workerState) {
            $workerState->counter = new \Cachet\Counter\XCache;
        },
        'test'=>function($workerState) use ($config) {
            for ($i=0; $i<$config->counterRuns; $i++)
                $workerState->counter->increment('count');
        },
        'check'=>function($workers, $responses, $parentState) use ($config) {
            $count = xcache_get('counter/count'); 
            $expected = $config->workerCount * $config->counterRuns;
            if ($count != $expected)
                return [false, "Count was $count, expected $expected"];
            else
                return [true];
        },
    ],

    'memcacheBrokenCounterTest'=>[
        'setupParent'=>function($parentState) {
            $memcached = memcached_create_testing();
            $memcached->flush();
            $parentState->counter = new \Cachet\Counter\Memcache($memcached);
        },
        'setupWorker'=>function($workerState) {
            $memcached = memcached_create_testing();
            $workerState->counter = new \Cachet\Counter\Memcache($memcached);
        },
        'test'=>function($workerState) use ($config) {
            for ($i=0; $i<$config->counterRuns; $i++)
                $workerState->counter->increment('count');
        },
        'check'=>function($workers, $responses, $parentState) use ($config) {
            $count = $parentState->counter->value('count');
            $expected = $config->workerCount * $config->counterRuns;
            if ($count != $expected)
                return [false, "Count was $count, expected $expected"];
            else
                return [true];
        },
    ],

    'memcacheLockedCounterTest'=>[
        'setupParent'=>function($parentState) {
            $memcached = memcached_create_testing();
            $memcached->flush();
            $parentState->counter = new \Cachet\Counter\Memcache($memcached);
        },
        'setupWorker'=>function($workerState) {
            $memcached = memcached_create_testing();
            $workerState->counter = new \Cachet\Counter\Memcache($memcached);
            $workerState->counter->locker = new \Cachet\Locker\Semaphore('locker_single_hasher');
        },
        'test'=>function($workerState) use ($config) {
            for ($i=0; $i<$config->counterRuns; $i++)
                $workerState->counter->increment('count');
        },
        'check'=>function($workers, $responses, $parentState) use ($config) {
            $count = $parentState->counter->value('count');
            $expected = $config->workerCount * $config->counterRuns;
            if ($count != $expected)
                return [false, "Count was $count, expected $expected"];
            else
                return [true];
        },
    ],
    
    'redisCounterTest'=>[
        'setupParent'=>function() {
            $redis = redis_create_testing();
            $redis->flushDb();
        },
        'setupWorker'=>function($workerState) {
            $redis = redis_create_testing();
            $workerState->counter = new \Cachet\Counter\PHPRedis($redis);
        },
        'test'=>function($workerState) use ($config) {
            for ($i=0; $i<$config->counterRuns; $i++)
                $workerState->counter->increment('count');
        },
        'check'=>function($workers, $responses, $parentState) use ($config) {
            $redis = redis_create_testing();
            $count = $redis->get('counter/count'); 
            $expected = $config->workerCount * $config->counterRuns;
            if ($count != $expected)
                return [false, "Count was $count, expected $expected"];
            else
                return [true];
        },
    ],

    'sqliteCounterTest'=>[
        'setupParent'=>function() {
            $initialState = (object)[];
            $initialState->temp = tempnam(sys_get_temp_dir(), '');
            $pdo = new \PDO('sqlite:'.$initialState->temp);
            $counter = new \Cachet\Counter\PDOSQLite($pdo);
            $counter->ensureTableExists();
            return $initialState;
        },
        'setupWorker'=>function($workerState) {
            $pdo = new \PDO('sqlite:'.$workerState->temp);
            $workerState->counter = new \Cachet\Counter\PDOSQLite($pdo);
        },
        'test'=>function($workerState) use ($config) {
            for ($i=0; $i<$config->counterRuns; $i++)
                $workerState->counter->increment('count');
        },
        'teardownParent'=>function($parentState, $initialState) {
            unlink($initialState->temp);  
        },
        'check'=>function($workers, $responses, $parentState, $initialState) use ($config) {
            $pdo = new \PDO('sqlite:'.$initialState->temp);
            $sqlite = new \Cachet\Counter\PDOSQLite($pdo);
            $count = $sqlite->value('count');
            $expected = $config->workerCount * $config->counterRuns;
            if ($count != $expected)
                return [false, "Count was $count, expected $expected"];
            else
                return [true];
        },
    ],

    'mysqlCounterTest'=>[
        'setupParent'=>function($parentState) {
            $workerState = (object)['table'=>'cachet_counter_test'];
            $counter = new \Cachet\Counter\PDOMySQL(
                $GLOBALS['settings']['mysql'],
                $workerState->table
            );
            $counter->ensureTableExists();
            $pdo = $counter->connector->connect();
            $pdo->exec("TRUNCATE TABLE `{$counter->tableName}`");
            $parentState->counter = $counter;
            return $workerState;
        },
        'setupWorker'=>function($workerState) {
            $workerState->counter = new \Cachet\Counter\PDOMySQL(
                $GLOBALS['settings']['mysql'],
                $workerState->table
            );
        },
        'test'=>function($workerState) use ($config) {
            for ($i=0; $i<$config->counterRuns; $i++)
                $workerState->counter->increment('count');
        },
        'check'=>function($workers, $responses, $parentState) use ($config) {
            $count = $parentState->counter->value('count');
            $expected = $config->workerCount * $config->counterRuns;
            if ($count != $expected)
                return [false, "Count was $count, expected $expected"];
            else
                return [true];
        },
    ],

    'safeCacheCounterTest'=>[
        'setupParent'=>function($parentState) {
            apcu_clear_cache();
            $backend = new \Cachet\Backend\APCU(); 
            $cache = new \Cachet\Cache('safeCounter', $backend);
            $locker = new \Cachet\Locker\Semaphore();
            $parentState->counter = new \Cachet\Counter\SafeCache($cache, $locker);
        },
        'setupWorker'=>function($workerState) {
            $backend = new \Cachet\Backend\APCU(); 
            $cache = new \Cachet\Cache('safeCounter', $backend);
            $locker = new \Cachet\Locker\Semaphore();
            $workerState->counter = new \Cachet\Counter\SafeCache($cache, $locker);
        },
        'test'=>function($workerState) use ($config) {
            for ($i=0; $i<$config->counterRuns; $i++)
                $workerState->counter->increment('count');
        },
        'check'=>function($workers, $responses, $parentState) use ($config) {
            $count = $parentState->counter->value('count');
            $expected = $config->workerCount * $config->counterRuns;
            if ($count != $expected)
                return [false, "Count was $count, expected $expected"];
            else
                return [true];
        },
    ],
];

