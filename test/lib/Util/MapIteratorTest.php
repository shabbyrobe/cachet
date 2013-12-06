<?php
namespace Cachet\Test\Util;

use Cachet\Util\MapIterator;

class MapIteratorTest extends \CachetTestCase
{
    function testMapIteratorWithKeys()
    {
        $i = [['z', 'a'], ['y', 'b'], ['x', 'c']];
        $mapped = iterator_to_array(new MapIterator($i, function($item, &$key) {
            $key = $item[0];
            return $item[1];
        }));
        $expected = ['z' => 'a', 'y' => 'b', 'x' => 'c'];
        $this->assertEquals($expected, $mapped);
    }
    
    function testMapIteratorReindexesWhenKeyNotSetAndNotPresentInSignature()
    {
        // the q, r and s keys should be stripped
        $i = ['q'=>['z'], 'r'=>['y'], 's'=>['x']];
        $iter = new MapIterator($i, function($item) {
            return $item[0];
        });
        $expected = ['z', 'y', 'x'];
        $this->assertEquals($expected, iterator_to_array($iter));

        // rewind
        $this->assertEquals($expected, iterator_to_array($iter));
    }

    function testMapIteratorReindexesWhenKeyNotSetEvenWhenPresentInSignature()
    {
        // the q, r and s keys should be stripped
        $i = ['q'=>['z'], 'r'=>['y'], 's'=>['x']];
        $iter = new MapIterator($i, function($item, &$key) {
            return $item[0];
        });
        $expected = ['z', 'y', 'x'];
        $this->assertEquals($expected, iterator_to_array($iter));

        // rewind
        $this->assertEquals($expected, iterator_to_array($iter));
    }
}
