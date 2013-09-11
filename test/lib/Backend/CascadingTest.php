<?php
namespace Cachet\Test;

use Cachet\Backend;
use Cachet\Item;

class CascadingTest extends \BackendTestCase
{
    public function getBackend()
    {   
        $backend1 = new Backend\Memory();
        $backend2 = new Backend\Memory();
        $backend3 = new Backend\Memory();
        return new Backend\Cascading([$backend1, $backend2, $backend3]);
    }
    
    public function testGetFromFirst()
    {
        $first = $this->getMockBuilder('Cachet\Backend\Memory')->setMethods(array('get'))->getMock();
        $second = $this->getMockBuilder('Cachet\Backend\Memory')->setMethods(array('get'))->getMock();
        
        $first->expects($this->once())->method('get')->will($this->returnValue('foo'));
        $second->expects($this->never())->method('get');
        
        $cascading = new Backend\Cascading(array($first, $second));
        $result = $cascading->get('cache', 'yep');
        $this->assertEquals('foo', $result);
    }
    
    public function testGetPropagation()
    {
        $item = new Item('cache', 'key', 'value');
        
        $backend1 = new Backend\Memory();
        $backend2 = new Backend\Memory();
        $backend3 = new Backend\Memory();
        $backend3->set($item);
        
        $this->assertNull($backend1->get('cache', 'key'));
        $this->assertNull($backend2->get('cache', 'key'));
        
        $cascading = new Backend\Cascading(array($backend1, $backend2, $backend3));
        $result = $cascading->get('cache', 'key');
        $this->assertEquals($item, $result);
        
        $this->assertEquals($item, $backend1->get('cache', 'key'));
        $this->assertEquals($item, $backend2->get('cache', 'key'));
    }
    
    public function testSetPropagation()
    {
        $item = new Item('cache', 'key', 'value');
        
        $backend1 = new Backend\Memory();
        $backend2 = new Backend\Memory();
        $backend3 = new Backend\Memory();
        
        $this->assertNull($backend1->get('cache', 'key'));
        $this->assertNull($backend2->get('cache', 'key'));
        $this->assertNull($backend3->get('cache', 'key'));
        
        $cascading = new Backend\Cascading(array($backend1, $backend2, $backend3));
        $cascading->set($item);
        
        $this->assertEquals($item, $backend1->get('cache', 'key'));
        $this->assertEquals($item, $backend2->get('cache', 'key'));
        $this->assertEquals($item, $backend3->get('cache', 'key'));
    }
    
    public function testSetReplacement()
    {
        $oldItem = new Item('cache', 'key', 'value');
        
        $backend1 = new Backend\Memory();
        $backend2 = new Backend\Memory();
        $backend3 = new Backend\Memory();
        $backend1->set($oldItem);
        $backend2->set($oldItem);
        
        $this->assertEquals($oldItem, $backend1->get('cache', 'key'));
        $this->assertEquals($oldItem, $backend2->get('cache', 'key'));
        $this->assertNull($backend3->get('cache', 'key'));
        
        $newItem = new Item('cache', 'key', 'newvalue');
        $cascading = new Backend\Cascading(array($backend1, $backend2, $backend3));
        $cascading->set($newItem);
        
        $this->assertEquals($newItem, $backend1->get('cache', 'key'));
        $this->assertEquals($newItem, $backend2->get('cache', 'key'));
        $this->assertEquals($newItem, $backend3->get('cache', 'key'));
    }
    
    public function testDeleteInternal()
    {
        $item = new Item('cache', 'key', 'value');
        $backend1 = new Backend\Memory();
        $backend2 = new Backend\Memory();
        $cascading = new Backend\Cascading(array($backend1, $backend2));
        $cascading->set($item);
        $this->assertEquals($item, $backend1->get('cache', 'key'));
        $this->assertEquals($item, $backend2->get('cache', 'key'));
        
        $cascading->delete('cache', 'key');
        $this->assertNull($backend1->get('cache', 'key'));
        $this->assertNull($backend2->get('cache', 'key'));
    }
    
    public function testFlushInternal()
    {
        $item = new Item('cache', 'key', 'value');
        $backend1 = new Backend\Memory();
        $backend2 = new Backend\Memory();
        $cascading = new Backend\Cascading(array($backend1, $backend2));
        $cascading->set(new Item('cache', 'key1', 'value1'));
        $cascading->set(new Item('cache', 'key2', 'value2'));
        
        $this->assertCount(2, $backend1->data['cache']);
        $this->assertCount(2, $backend2->data['cache']);
        $cascading->flush('cache');
        $this->assertFalse(isset($backend1->data['cache']));
        $this->assertFalse(isset($backend2->data['cache']));
    }
}
