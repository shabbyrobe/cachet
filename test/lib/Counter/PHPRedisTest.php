<?php
namespace Cachet\Test\Counter;

if (!extension_loaded('redis')) {
    skip_test(__NAMESPACE__, "PHPRedisTest", "Redis extension not loaded");
}
elseif (is_server_listening(
    $GLOBALS['settings']['redis']['host'], 
    $GLOBALS['settings']['redis']['port']
)) {
    skip_test(__NAMESPACE__, "PHPRedisTest", "Redis server not listening");
}
else {
    class PHPRedisTest extends \Cachet\Test\CounterTestCase
    {
        public function setUp()
        {
            $this->redis = $this->getRedis();
            $this->redis->flushDb();
            $count = $this->redis->dbSize();
            if ($count != 0)
                throw new \Exception();
        }

        private function getRedis()
        {
            return redis_create_testing();
        }
        
        public function getCounter()
        {
            return new \Cachet\Counter\PHPRedis($this->redis);
        }
    }
}

