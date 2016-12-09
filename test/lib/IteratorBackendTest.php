<?php
namespace Cachet\Test;

trait IteratorBackendTest
{
    public function testKeys()
    {
        $backend = $this->getBackend();
        $backend->set(new \Cachet\Item('cache1', 'key1', 'value1'));
        
        // backends don't care if your item is valid
        $backend->set(
            new \Cachet\Item('cache1', 'key2', 'value2', new \Cachet\Dependency\Dummy(false))
        );
        
        $backend->set(new \Cachet\Item('cache1', 'key3', 'value3'));
        $backend->set(new \Cachet\Item('cache2', 'key1', 'value1'));
        
        $keys = $backend->keys('cache1');
        $keys = is_array($keys) ? $keys : iterator_to_array($keys);
        sort($keys);
        $this->assertEquals(["key1", "key2", "key3"], $keys);
    }
    
    public function testItems()
    {
        $backend = $this->getBackend();
        $item1 = new \Cachet\Item('cache1', 'key1', 'value1');
        $backend->set($item1);
        
        // backends don't care if your item is valid
        $item2 = new \Cachet\Item('cache1', 'key2', 'value2', new \Cachet\Dependency\Dummy(false));
        $backend->set($item2);
        
        $item3 = new \Cachet\Item('cache1', 'key3', 'value3');
        $backend->set($item3);
        
        $backend->set(new \Cachet\Item('cache2', 'key1', 'value1'));
        
        $items = $backend->items('cache1');
        $items = is_array($items) ? $items : iterator_to_array($items);
        usort($items, function($a, $b) {
            return strcmp($a->key, $b->key);
        });
        $this->assertEquals([$item1, $item2, $item3], $items);
    }
}
