<?php
namespace Cachet\Test\Backend;

use Cachet\Backend;
use Cachet\Item;
use Cachet\Dependency;

class PHPRedisTest extends \BackendTestCase
{
    public function setUp()
    {
        $this->redis = $this->getRedis();
        $this->redis->flushDb();
        $count = $this->redis->dbSize();
        if ($count != 0)
            throw new \Exception();
    }
    
    public function getBackend()
    {
        return new \Cachet\Backend\PHPRedis($this->redis);
    }
    
    protected function getRedis()
    {
        if (!extension_loaded('redis'))
            $this->markTestSkipped("phpredis extension not found");
        
        if (!isset($GLOBALS['settings']['redis']) || !$GLOBALS['settings']['redis']['host'])
            $this->markTestSkipped("Please supply a redis host in .cachettestrc");
        
        $sock = @fsockopen(
            $GLOBALS['settings']['redis']['host'], 
            $GLOBALS['settings']['redis']['port'],
            $errno,
            $errstr,
            1
        );
        if ($sock === false)
            $this->markTestSkipped("Redis server not running");
        
        fclose($sock);
        
        $redis = new \Redis();
        $redis->connect(
            $GLOBALS['settings']['redis']['host'], 
            isset($GLOBALS['settings']['redis']['port']) ? $GLOBALS['settings']['redis']['port'] : 6379
        );
        $redis->select($GLOBALS['settings']['redis']['database']);
        return $redis;
    }
    
    public function testWithTTLDependency()
    {
        $backend = $this->getBackend();
        $item = new Item('cache1', 'a', 'b', new Dependency\TTL(600));
        $backend->set($item);
        
        $ttl = $this->redis->ttl('cache1/a');
        $this->assertLessThanOrEqual(600, $ttl);
        $this->assertGreaterThan(595, $ttl);
        
        $item = $backend->get('cache1', 'a');
        $this->assertNull($item->dependency);
    }
    
    public function testWithTimeDependency()
    {
        $backend = $this->getBackend();
        $time = time() + 600;
        $item = new Item('cache1', 'a', 'b', new Dependency\Time($time));
        $backend->set($item);
        
        $ttl = $this->redis->ttl('cache1/a');
        $this->assertLessThanOrEqual(600, $ttl);
        $this->assertGreaterThan(595, $ttl);
        
        $item = $backend->get('cache1', 'a');
        $this->assertNull($item->dependency);
    }
    
    public function testWithPermanentDependency()
    {
        // we used to call $redis->persist() with these keys. let's keep permanent 
        // around for now as it acts as a useful tag for garbage collection strategies
        $backend = $this->getBackend();
        $item = new Item('cache1', 'a', 'b', new Dependency\Permanent);
        $backend->set($item);
        
        $ttl = $this->redis->ttl('cache1/a');
        $this->assertEquals(-1, $ttl);
    }
    
    public function testWithPermanentDependencyOverwritesTTL()
    {
        $backend = $this->getBackend();
        $time = time() + 600;
        $item = new Item('cache1', 'a', 'b', new Dependency\Time($time));
        $backend->set($item);
        $ttl = $this->redis->ttl('cache1/a');
        $this->assertGreaterThan(0, $ttl);
        
        $item = new Item('cache1', 'a', 'c', new Dependency\Permanent);
        $backend->set($item);
        
        $ttl = $this->redis->ttl('cache1/a');
        $this->assertEquals(-1, $ttl);
    }
}
