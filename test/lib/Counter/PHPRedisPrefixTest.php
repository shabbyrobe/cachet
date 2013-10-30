<?php
namespace Cachet\Test\Counter;

if (!extension_loaded('redis')) {
    skip_test(__NAMESPACE__, "PHPRedisPrefixTest", "Redis extension not loaded");
}
elseif (!is_server_listening(
    $GLOBALS['settings']['redis']['host'], 
    $GLOBALS['settings']['redis']['port']
)) {
    skip_test(__NAMESPACE__, "PHPRedisPrefixTest", "Redis server not listening");
}
else {
    /**
     * @group counter
     */
    class PHPRedisPrefixTest extends \Cachet\Test\CounterTestCase
    {
        public function getCounter()
        {
            $redis = redis_create_testing();
            $redis->delete('prefix/counter/value');
            $redis->delete('prefix/counter/value2');
            return new \Cachet\Counter\PHPRedis($redis, 'prefix');
        }
    }
}


