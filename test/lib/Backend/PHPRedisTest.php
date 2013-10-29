<?php
namespace Cachet\Test\Backend;

use Cachet\Backend;
use Cachet\Item;
use Cachet\Dependency;

if (!extension_loaded('redis')) {
    skip_test(__NAMESPACE__, "PHPRedisTest", "Redis extension not loaded");
}
elseif (!is_server_listening(
    $GLOBALS['settings']['redis']['host'], 
    $GLOBALS['settings']['redis']['port']
)) {
    skip_test(__NAMESPACE__, "PHPRedisTest", "Redis server not listening");
}
else {
    class PHPRedisTest extends \Cachet\Test\BackendTestCase
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
            return redis_create_testing();
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
}
