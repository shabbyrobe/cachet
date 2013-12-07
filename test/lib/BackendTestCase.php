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
    
    /**
     * @dataProvider dataSetGet
     */
    public function testSetGet($keyValues, $expected=null)
    {
        $expected = $expected ?: $keyValues;

        $backend = $this->getBackend();

        foreach ($keyValues as $key=>$value) {
            $backend->set(new \Cachet\Item('cache1', $key, $value));
        }

        foreach ($expected as $key=>$value) {
            $item = $backend->get('cache1', $key);
            $this->assertTrue($item instanceof \Cachet\Item);
            $this->assertEquals($key, $item->key);
            if (is_object($value))
                $this->assertEquals($value, $item->value);
            else
                $this->assertSame($value, $item->value);
        }
        foreach (array_diff(array_keys($keyValues), array_keys($expected)) as $key) {
            $this->assertNull($backend->get($key));
        }
    }

    function dataSetGet()
    {
        return [
            [['key1'=>'value1', 'key2'=>'value2']],
            [[1=>'value1', 2=>'value2']],
            [[false=>1, 0=>2], [false=>2, 0=>2]],
            [[true=>1, 1=>2], [true=>2, 1=>2]],
            [[null=>1]],
            [['key'=>1, 'key'=>2], ['key'=>2]],
            [['key'=>1.1231233]],
            [['key'=>false]],
            [['key'=>null]],
            [['key'=>['foo', 'bar', 'baz']]],
            [['key'=>(object)['foo'=>'bar']]],
        ];
    }

    function testSetClosureFails()
    {
        $backend = $this->getBackend();
        $key = 'invalid';
        $value = function() {};
        $this->setExpectedException('Exception', 'Serialization of \'Closure\' is not allowed');
        $backend->set(new \Cachet\Item('cache1', $key, $value));
    }

    function testSetDifferentCaches()
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
