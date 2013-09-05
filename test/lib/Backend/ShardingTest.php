<?php
namespace Cachet\Test\Backend;

use Cachet\Backend;
use Cachet\Dependency;
use Cachet\Item;

class ShardingTest extends \PHPUnit_Framework_TestCase
{
    public function testSetGet()
    {
        $backend1 = new Backend\Memory();
        $backend2 = new Backend\Memory();
        $backend3 = new Backend\Memory();
        $sharding = new Backend\Sharding(array($backend1, $backend2, $backend3));
        
        // These keys are known to hash into the different backends.
        // The test is slightly brittle as a result - if the hashing algo changes,
        // new keys will have to be used.
        $sharding->set(new Item('cache', 'xxx', 'yup'));
        $sharding->set(new Item('cache', 'qqq', 'qux'));
        $sharding->set(new Item('cache', 'nnn', 'bar'));
        
        // xxx ends up in backend 1
        $this->assertNotNull($backend1->get('cache', 'xxx'));
        $this->assertNull($backend2->get('cache', 'xxx'));
        $this->assertNull($backend3->get('cache', 'xxx'));
        
        // qqq ends up in backend 2
        $this->assertNull($backend1->get('cache', 'qqq'));
        $this->assertNotNull($backend2->get('cache', 'qqq'));
        $this->assertNull($backend3->get('cache', 'qqq'));
        
        // nnn ends up in backend 3
        $this->assertNull($backend1->get('cache', 'nnn'));
        $this->assertNull($backend2->get('cache', 'nnn'));
        $this->assertNotNull($backend3->get('cache', 'nnn'));
        
        $this->assertNotNull($sharding->get('cache', 'xxx'));
        $this->assertNotNull($sharding->get('cache', 'qqq'));
        $this->assertNotNull($sharding->get('cache', 'nnn'));
    }
    
    public function testSetDelete()
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
    
    public function testFlush()
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
