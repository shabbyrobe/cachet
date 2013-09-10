<?php
namespace Cachet\Test\Backend;

use Cachet\Backend;
use Cachet\Cache;
use Cachet\Item;

class PHPRedisTest extends \BackendTestCase
{
    public function setUp()
    {
        $redis = $this->getRedis();
        $redis->flushDb();
        $count = $redis->dbSize();
        if ($count != 0)
            throw new \Exception();
    }
    
    public function getBackend()
    {
        $redis = $this->getRedis();
        return new \Cachet\Backend\PHPRedis($redis);
    }
    
    protected function getRedis()
    {
        if (!extension_loaded('redis'))
            $this->markTestSkipped("phpredis extension not found");
        
        if (!isset($GLOBALS['settings']['redis']) || !$GLOBALS['settings']['redis']['server'])
            $this->markTestSkipped("Please supply a redis server in .cachettestrc");
        
        $redis = new \Redis();
        $redis->connect(
            $GLOBALS['settings']['redis']['server'], 
            isset($GLOBALS['settings']['redis']['port']) ? $GLOBALS['settings']['redis']['port'] : 6379
        );
        $redis->select($GLOBALS['settings']['redis']['database']);
        return $redis;
    }
}
