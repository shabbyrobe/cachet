<?php
namespace Cachet\Test\Backend;

use Cachet\Backend;
use Cachet\Item;

class ShardingTest extends \BackendTestCase
{
    public function getBackend()
    {
        $backend1 = new Backend\Memory();
        $backend2 = new Backend\Memory();
        $backend3 = new Backend\Memory();
        $sharding = new Backend\Sharding(array($backend1, $backend2, $backend3));
        return $sharding;
    }
    
    public function testSetGetInternal()
    {
        $backend1 = new Backend\Memory();
        $backend2 = new Backend\Memory();
        $backend3 = new Backend\Memory();
        $sharding = new Backend\Sharding(array($backend1, $backend2, $backend3));
    
        // These keys are known to hash into the different backends.
        // The test is slightly brittle as a result - if the hashing algo changes,
        // new keys will have to be used.
        $key1 = 'a';
        $key2 = 'g';
        $key3 = 'i';

        $sharding->set(new Item('cache', $key1, 'yup'));
        $sharding->set(new Item('cache', $key2, 'qux'));
        $sharding->set(new Item('cache', $key3, 'bar'));
        
        // xxx ends up in backend 1
        $this->assertNotNull($backend1->get('cache', $key1));
        $this->assertNull($backend2->get('cache', $key1));
        $this->assertNull($backend3->get('cache', $key1));
        
        // qqq ends up in backend 2
        $this->assertNull($backend1->get('cache', $key2));
        $this->assertNotNull($backend2->get('cache', $key2));
        $this->assertNull($backend3->get('cache', $key2));
        
        // nnn ends up in backend 3
        $this->assertNull($backend1->get('cache', $key3));
        $this->assertNull($backend2->get('cache', $key3));
        $this->assertNotNull($backend3->get('cache', $key3));
        
        $this->assertNotNull($sharding->get('cache', $key1));
        $this->assertNotNull($sharding->get('cache', $key2));
        $this->assertNotNull($sharding->get('cache', $key3));
    }
    
    public function testSetDeleteInternal()
    {
        $backend1 = new Backend\Memory();
        $backend2 = new Backend\Memory();
        $sharding = new Backend\Sharding(array($backend1, $backend2));
        
        $sharding->set(new Item('cache', 'foo', 'bar'));
        $this->assertCount(0, $backend1->data);
        $this->assertCount(1, $backend2->data);
        
        $sharding->delete('cache', 'foo');
        
        $this->assertNull($backend1->get('cache', 'foo'));
        $this->assertNull($backend2->get('cache', 'foo'));
    }
    
    public function testFlushInternal()
    {
        $backend1 = new Backend\Memory();
        $backend1->set(new Item('cache', 'foo', 'bar'));
        $backend2 = new Backend\Memory();
        $backend2->set(new Item('cache', 'baz', 'qux'));
        
        $sharding = new Backend\Sharding(array($backend1, $backend2));
        $sharding->flush('cache');
        
        $this->assertNull($backend1->get('cache', 'foo'));
        $this->assertNull($backend2->get('cache', 'baz'));
    }
}
