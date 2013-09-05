<?php
namespace Cachet\Test\Backend;

class MemoryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetNonexistent()
    {
        $memory = new \Cachet\Backend\Memory();
        $item = $memory->get('cache', 'doesNotExist');
        $this->assertNull($item);
    }
    
    public function testSetGet()
    {
        $memory = new \Cachet\Backend\Memory();
        $memory->set(new \Cachet\Item('cache', 'key', 'value'));
        
        $item = $memory->get('cache', 'key');
        $this->assertNotNull($item);
        $this->assertTrue($item instanceof \Cachet\Item);
        $this->assertEquals('key', $item->key);
        $this->assertEquals('value', $item->value);
    }
    
    public function testSetDifferentCaches()
    {
        $memory = new \Cachet\Backend\Memory();
        $memory->set(new \Cachet\Item('cache1', 'key', 'value1'));
        $memory->set(new \Cachet\Item('cache2', 'key', 'value2'));
        
        $this->assertEquals('value1', $memory->get('cache1', 'key')->value);
        $this->assertEquals('value2', $memory->get('cache2', 'key')->value);
    }
    
    public function testDeleteNonexistent()
    {
        $memory = new \Cachet\Backend\Memory();
        $memory->delete('cache', 'doesNotExist');
        
        // assert that we made it this far!
        $this->assertTrue(true);
    }
    
    public function testSetDelete()
    {
        $memory = new \Cachet\Backend\Memory();
        $memory->set(new \Cachet\Item('cache', 'key1', 'value'));
        $memory->set(new \Cachet\Item('cache', 'key2', 'value'));
        
        $memory->delete('cache', 'key1');
        $this->assertCount(1, $memory->data['cache']);
    }
    
    public function testFlush()
    {
        $memory = new \Cachet\Backend\Memory();
        $memory->set(new \Cachet\Item('cache1', 'key1', 'value'));
        $memory->set(new \Cachet\Item('cache1', 'key2', 'value'));
        $memory->set(new \Cachet\Item('cache2', 'key1', 'value'));
        
        $memory->flush('cache1');
        $this->assertFalse(isset($memory->data['cache1']));
        $this->assertCount(1, $memory->data['cache2']);
    }
    
    public function testFlushEmpty()
    {
        $memory = new \Cachet\Backend\Memory();
        $memory->flush('foo');
        
        // assert that we made it this far!
        $this->assertTrue(true);
    }
}
