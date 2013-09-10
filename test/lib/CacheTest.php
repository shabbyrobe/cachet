<?php
namespace Cachet\Test;

use Cachet\Dependency;
use Cachet\Item;

class CacheTest extends \CachetTestCase
{
    public function setUp()
    {
        $this->backend = new \Cachet\Backend\Memory();
        $this->cache = new \Cachet\Cache('cache', $this->backend);
    }
    
    /**
     * @dataProvider dataValidValues
     */
    public function testSetGetValue($value)
    {
        $this->cache->set('foo', $value);
        $get = $this->cache->get('foo', $found);
        $this->assertEquals($value, $get);
        $this->assertTrue($found);
    }
    
    public function testGetWithBorkedItemDeletesInvalid()
    {
        $this->backend->data['cache']['key'] = 'Not an item';
        $item = $this->cache->get('key', $found);
        $this->assertNull($item);
        $this->assertFalse($found);
        $this->assertFalse(isset($this->backend->data['cache']['key']));
    }
    
    public function testGetWithBorkedItemLeavesInvalid()
    {
        $this->cache->deleteInvalid = false;
        $this->backend->data['cache']['key'] = 'Not an item';
        $item = $this->cache->get('key', $found);
        $this->assertNull($item);
        $this->assertFalse($found);
        $this->assertTrue(isset($this->backend->data['cache']['key']));
    }
    
    public function testGetWithBorkedDependencyDeletesInvalid()
    {
        $item = new Item('cache', 'key', 'value', 'Not a dependency');
        $this->backend->set($item);
        
        $item = $this->cache->get('key', $found);
        $this->assertNull($item);
        $this->assertFalse($found);
        $this->assertFalse(isset($this->backend->data['cache']['key']));
    }
    
    public function testGetWithBorkedDependencyLeavesInvalid()
    {
        $this->cache->deleteInvalid = false;
        
        $item = new Item('cache', 'key', 'value', 'Not a dependency');
        $this->backend->set($item);
        
        $item = $this->cache->get('key', $found);
        $this->assertNull($item);
        $this->assertFalse($found);
        $this->assertTrue(isset($this->backend->data['cache']['key']));
    }
    
    public function testGetWithValidDependency()
    {
        $this->backend->set(new Item('cache', 'key', 'value', new Dependency\Permanent));
        $result = $this->cache->get('key', $found);
        $this->assertNotNull($result);
        $this->assertTrue($found);
        $this->assertEquals('value', $result);
    }
    
    public function testGetInfersDefaultDependency()
    {
        $dependency = $this->getInvalidDependency();
        $dependency->expects($this->once())->method('valid');
        
        $this->cache->dependency = $dependency;
        $this->backend->set(new Item('cache', 'key', 'value'));
        $result = $this->cache->get('key', $found);
        
        $this->assertNull($result);
        $this->assertFalse($found);
    }
    
    public function testSetInfersDefaultDependency()
    {
        $dependency = $this->getInvalidDependency();
        $dependency->expects($this->once())->method('init');
        
        $this->cache->dependency = $dependency;
        $this->cache->set('foo', 'bar');
        
        $this->assertEquals($dependency, $this->backend->get('cache', 'foo')->dependency);
    }
    
    public function testSetWithDuration()
    {
        $this->cache->set('foo', 'bar', 100);
        $this->assertTrue($this->backend->get('cache', 'foo')->dependency instanceof Dependency\TTL);
        $this->assertEquals(100, $this->backend->get('cache', 'foo')->dependency->ttlSeconds);
    }
    
    public function testSetItemDependencyOverridesDefault()
    {
        $cacheDependency = $this->getInvalidDependency();
        $cacheDependency->expects($this->never())->method('init');
        
        $itemDependency = $this->getValidDependency();
        $itemDependency->expects($this->once())->method('init');
        
        $this->cache->dependency = $cacheDependency;
        $this->cache->set('foo', 'bar', $itemDependency);
        
        $this->assertEquals($itemDependency, $this->backend->get('cache', 'foo')->dependency);
    }
    
    public function testGetItemDependencyOverridesDefault()
    {
        $cacheDependency = $this->getInvalidDependency();
        $cacheDependency->expects($this->never())->method('valid');
        
        $itemDependency = $this->getValidDependency();
        $itemDependency->expects($this->once())->method('valid');
        
        $this->cache->dependency = $cacheDependency;
        $this->backend->set(new Item('cache', 'key', 'value', $itemDependency));
        $result = $this->cache->get('key', $found);
        
        $this->assertNotNull($result);
        $this->assertTrue($found);
    }
    
    public function testGetWithInvalidDependency()
    {
        $dependency = $this->getInvalidDependency();
        
        $this->backend->set(new Item('cache', 'key', 'value', $dependency));
        $result = $this->cache->get('key', $found);
        $this->assertNull($result);
        $this->assertFalse($found);
    }
    
    public function testFlush()
    {
        $this->backend->set(new Item('cache', 'foo', 'baz'));
        $this->backend->set(new Item('cache', 'bar', 'qux'));
        $this->backend->set(new Item('anotherCache', 'foo', 'qux'));
        
        $this->cache->flush();
        
        $this->cache->get('foo', $found);
        $this->assertFalse($found);
        
        $this->cache->get('foo', $found);
        $this->assertFalse($found);
        
        $this->assertNotNull($this->backend->get('anotherCache', 'foo'));
    }
    
    /**
     * @dataProvider dataValidValues
     */
    public function testHas($value)
    {
        $this->cache->set('foo', $value);
        $this->assertTrue($this->cache->has('foo'));
    }
    
    public function testHasNot()
    {
        $this->assertFalse($this->cache->has('foo'));
    }
    
    public function testHasWithInvalidDependency()
    {
        $this->cache->set('foo', 'value', $this->getInvalidDependency());
        $this->assertFalse($this->cache->has('foo'));
    }
    
    public function testHasWithValidDependency()
    {
        $this->cache->set('foo', 'value', $this->getValidDependency());
        $this->assertTrue($this->cache->has('foo'));
    }
    
    /**
     * @dataProvider dataValidValues
     */
    public function testWrapKeyCallbackIsCalled($value)
    {
        $called = false;
        
        $this->cache->wrap('foo', function() use (&$called, $value) {
            $called = true;
            return $value;
        });
        
        $this->assertTrue($called);
        $this->assertEquals($value, $this->cache->get('foo'));
    }
    
    /**
     * @dataProvider dataValidValues
     */
    public function testWrapKeyCallbackNotCalled($value)
    {
        $called = false;
        
        $this->cache->set('foo', $value);
        
        $this->cache->wrap('foo', function() use (&$called, $value) {
            $called = true;
            return $value;
        });
        
        $this->assertFalse($called);
        $this->assertEquals($value, $this->cache->get('foo'));
    }
    
    public function testWrapKeyDependencyCallback()
    {
        $called = false;
        
        $this->cache->wrap('foo', $this->getInvalidDependency(), function() use (&$called) {
            $called = true;
        });
        
        $item = $this->backend->get('cache', 'foo');
        $this->assertTrue($item->dependency instanceof Dependency);
    }
    
    public function testWrapKeyTTLCallback()
    {
        $called = false;
        
        $this->cache->wrap('foo', 100, function() use (&$called) {
            $called = true;
        });
        
        $item = $this->backend->get('cache', 'foo');
        $this->assertTrue($item->dependency instanceof Dependency\TTL);
    }
    
    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testWrapInvalidArguments1()
    {
        $this->cache->wrap('nope');
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testWrapInvalidArguments4()
    {
        $this->cache->wrap('nope', 'nope', 'nope', 'nope');
    }
    
    /**
     * @covers Cachet\Cache::offsetGet
     */
    public function testArrayAccessGet()
    {
        $cache = $this->getMockBuilder('Cachet\Cache')
            ->setMethods(array('get'))
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $cache->expects($this->once())->method('get')->will($this->returnValue('yep'));
        $result = $cache['test'];
        $this->assertEquals('yep', $result);
    }
    
    /**
     * @covers Cachet\Cache::offsetExists
     */
    public function testArrayAccessIsset()
    {
        $cache = $this->getMockBuilder('Cachet\Cache')
            ->setMethods(array('has'))
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $cache->expects($this->once())->method('has')->will($this->returnValue(true));
        $result = isset($cache['test']);
        $this->assertTrue($result);
    }
    
    /**
     * @covers Cachet\Cache::offsetSet
     */
    public function testArrayAccessSet()
    {
        $cache = $this->getMockBuilder('Cachet\Cache')
            ->setMethods(array('set'))
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $cache->expects($this->once())->method('set')->with($this->equalTo('test'), $this->equalTo('yep'));
        $cache['test'] = 'yep';
    }
    
    /**
     * @covers Cachet\Cache::offsetUnset
     */
    public function testArrayAccessUnset()
    {
        $cache = $this->getMockBuilder('Cachet\Cache')
            ->setMethods(array('delete'))
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $cache->expects($this->once())->method('delete')->with($this->equalTo('test'));
        unset($cache['test']);
    }
    
    private function getInvalidDependency()
    {
        $dependency = $this->getMockBuilder('Cachet\Dependency')
            ->setMethods(array('init', 'valid'))
            ->getMockForAbstractClass()
        ;
        $dependency->expects($this->any())->method('valid')->will($this->returnValue(false));
        return $dependency;
    }
    
    private function getValidDependency()
    {
        $dependency = $this->getMockBuilder('Cachet\Dependency')
            ->setMethods(array('init', 'valid'))
            ->getMockForAbstractClass()
        ;
        $dependency->expects($this->any())->method('valid')->will($this->returnValue(true));
        return $dependency;
    }
}
