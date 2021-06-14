<?php
namespace Cachet\Test;

use Cachet\Dependency;
use Cachet\Item;

class ItemTest extends \PHPUnit\Framework\TestCase
{
    public function testCompactNoDependency()
    {
        $item = new Item('foo', 'bar', 'baz');
        $compact = $item->compact();
        $this->assertEquals(array('foo', 'bar', 'baz', $item->timestamp), $compact);
    }
    
    public function testCompactWithDependency()
    {
        $dep = new Dependency\Permanent();
        $item = new Item('foo', 'bar', 'baz', $dep);
        $compact = $item->compact();
        $this->assertEquals(array('foo', 'bar', 'baz', $item->timestamp, $dep), $compact);
    }
    
    public function testUncompactFull()
    {
        $dep = new Dependency\Permanent();
        $compact = array('foo', 'bar', 'baz', 1234, $dep);
        $item = Item::uncompact($compact);
        $this->assertEquals('foo', $item->cacheId);
        $this->assertEquals('bar', $item->key);
        $this->assertEquals('baz', $item->value);
        $this->assertEquals($dep, $item->dependency);
        $this->assertEquals(1234, $item->timestamp);
    }
    
    public function testUncompactNoDependency()
    {
        $compact = array('foo', 'bar', 'baz', 1234);
        $item = Item::uncompact($compact);
        $this->assertEquals('foo', $item->cacheId);
        $this->assertEquals('bar', $item->key);
        $this->assertEquals('baz', $item->value);
        $this->assertEquals(1234, $item->timestamp);
        $this->assertNull($item->dependency);
    }
}
