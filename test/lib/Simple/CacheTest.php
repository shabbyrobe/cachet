<?php
namespace Cachet\Test\Simple;

class SimpleCacheTest extends \CachetTestCase
{
    public function setUp(): void
    {
        $this->backend = new \Cachet\Backend\Memory();
        $this->innerCache = new \Cachet\Cache('cache', $this->backend);
        $this->cache = new \Cachet\Simple\Cache($this->innerCache);
    }

    /**
     * @dataProvider dataValidValues
     */
    public function testSetGetValue($value)
    {
        $this->assertTrue($this->cache->set('foo', $value));
        $get = $this->cache->get('foo');
        $this->assertEquals($value, $get);
    }

    public function testGetDefault()
    {
        $get = $this->cache->get('foo', 'default');
        $this->assertEquals($get, 'default');
    }

    public function testGetWithInvalidKeyFails()
    {
        $this->expectException(\Psr\SimpleCache\InvalidArgumentException::class);
        $this->cache->get('{}');
    }

    public function testSetWithInvalidKeyFails()
    {
        $this->expectException(\Psr\SimpleCache\InvalidArgumentException::class);
        $this->cache->set('{}', 'foo');
    }

    public function testHas()
    {
        $this->assertFalse($this->cache->has('foo'));
        $this->assertTrue($this->cache->set('foo', 'bar'));
        $this->assertTrue($this->cache->has('foo'));
    }

    public function testDelete()
    {
        $this->assertTrue($this->cache->set('foo', 'bar'));
        $this->assertEquals('bar', $this->cache->get('foo'));
        $this->assertTrue($this->cache->delete('foo'));

        $this->assertEquals(null, $this->cache->get('foo'));

        // Reports true if you try to delete a key that doesn't exist; returning
        // false is unsupported by Cachet at this time, and the spec is unclear
        // about what conditions to return false under anyway.
        $this->assertTrue($this->cache->delete('foo'));
    }

    public function testClear()
    {
        $this->assertTrue($this->cache->set('foo', 'bar'));
        $this->assertTrue($this->cache->set('baz', 'qux'));
        $this->assertEquals('bar', $this->cache->get('foo'));
        $this->assertEquals('qux', $this->cache->get('baz'));
        $this->assertTrue($this->cache->clear());

        $this->assertEquals(null, $this->cache->get('foo'));
        $this->assertEquals(null, $this->cache->get('baz'));
    }

    public function testSetGetMultiple()
    {
        $this->assertTrue($this->cache->setMultiple(['foo' => 'bar', 'baz' => 'qux']));
        $values = $this->cache->getMultiple(['foo', 'baz']);
        $this->assertEquals(['foo' => 'bar', 'baz' => 'qux'], $values);
    }

    public function testGetMultipleDefault()
    {
        $this->assertEquals(['foo' => null, 'bar' => null], 
            $this->cache->getMultiple(['foo', 'bar']));
        $this->assertEquals(['foo' => 'default', 'bar' => 'default'], 
            $this->cache->getMultiple(['foo', 'bar'], 'default'));
    }

    public function testDeleteMultiple()
    {
        $this->assertTrue($this->cache->setMultiple(['foo' => 'bar', 'baz' => 'qux', 'ding' => 'dong']));
        $this->assertEquals('bar', $this->cache->get('foo'));
        $this->assertEquals('qux', $this->cache->get('baz'));
        $this->assertEquals('dong', $this->cache->get('ding'));

        $this->assertTrue($this->cache->deleteMultiple(['foo', 'baz']));
        $this->assertEquals(null, $this->cache->get('foo'));
        $this->assertEquals(null, $this->cache->get('baz'));
        $this->assertEquals('dong', $this->cache->get('ding'));
    }
}
