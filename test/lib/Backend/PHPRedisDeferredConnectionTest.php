<?php
namespace Cachet\Test\Backend;

use Cachet\Backend;
use Cachet\Item;
use Cachet\Dependency;

$redisListening = is_server_listening(
    $GLOBALS['settings']['redis']['host'], 
    $GLOBALS['settings']['redis']['port']
);

if (!$redisListening) {
    class PHPRedisDeferredConnectionTest extends \PHPUnit_Framework_TestCase
    {
        public function testDummy()
        {
            $this->markTestSkipped("Redis server not listening");
        }
    }
}
else {
    class PHPRedisDeferredConnectionTest extends PHPRedisTest
    {
        public function getBackend()
        {
            return new \Cachet\Backend\PHPRedis($this->getRedisInfo());
        }
        
        protected function getRedisInfo()
        {
            $info = [
                'host'=>$GLOBALS['settings']['redis']['host'], 
                'port'=>isset($GLOBALS['settings']['redis']['port']) 
                    ? $GLOBALS['settings']['redis']['port'] 
                    : 6379
                ,
                'database'=>$GLOBALS['settings']['redis']['database'],
            ];
            return $info;
        }
    }
}

