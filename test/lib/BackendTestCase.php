<?php
namespace Cachet\Test;

abstract class BackendTestCase extends \CachetTestCase
{
    abstract function getBackend();
    
    public function testGetNonexistent()
    {
        $backend = $this->getBackend();
        $item = $backend->get('cache1', 'doesNotExist');
        $this->assertNull($item);
    }
    
    public function testSetGet()
    {
        $backend = $this->getBackend();
        $backend->set(new \Cachet\Item('cache1', 'key', 'value'));
        
        $item = $backend->get('cache1', 'key');
        $this->assertNotNull($item);
        $this->assertTrue($item instanceof \Cachet\Item);
        $this->assertEquals('key', $item->key);
        $this->assertEquals('value', $item->value);
    }
    
    public function testSetDifferentCaches()
    {
        $backend = $this->getBackend();
        $backend->set(new \Cachet\Item('cache1', 'key', 'value1'));
        $backend->set(new \Cachet\Item('cache2', 'key', 'value2'));
        
        $this->assertEquals('value1', $backend->get('cache1', 'key')->value);
        $this->assertEquals('value2', $backend->get('cache2', 'key')->value);
    }
    
    public function testDeleteNonexistent()
    {
        $backend = $this->getBackend();
        $backend->delete('cache1', 'doesNotExist');
        
        // assert that we made it this far!
        $this->assertTrue(true);
    }
    
    public function testSetDelete()
    {
        $backend = $this->getBackend();
        $backend->set(new \Cachet\Item('cache1', 'key1', 'value'));
        $backend->set(new \Cachet\Item('cache1', 'key2', 'value'));
        
        $backend->delete('cache1', 'key1');
        $this->assertNull($backend->get('cache1', 'key1'));
        $this->assertNotNull($backend->get('cache1', 'key2'));
    }
    
    public function testFlush()
    {
        $backend = $this->getBackend();
        $backend->set(new \Cachet\Item('cache1', 'key1', 'value'));
        $backend->set(new \Cachet\Item('cache1', 'key2', 'value'));
        $backend->set(new \Cachet\Item('cache2', 'key1', 'value'));
        
        $backend->flush('cache1');
        $this->assertNull($backend->get('cache1', 'key1'));
        $this->assertNull($backend->get('cache1', 'key2'));
        $this->assertNotNull($backend->get('cache2', 'key1'));
    }
    
    public function testFlushEmpty()
    {
        $backend = $this->getBackend();
        $backend->flush('cache1');
        
        // assert that we made it this far!
        $this->assertTrue(true);
    }
}
