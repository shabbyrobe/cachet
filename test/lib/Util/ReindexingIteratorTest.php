<?php
namespace Cachet\Test\Util;

use Cachet\Util\ReindexingIterator;

class ReindexingIteratorTest extends \CachetTestCase
{
    function testReindex()
    {
        $a = ['q'=>'z', 'r'=>'y', 's'=>'x'];
        $expected = ['z', 'y', 'x'];
        
        $iter = new ReindexingIterator(new \ArrayIterator($a));
        $this->assertEquals($expected, iterator_to_array($iter));
    
        // the extra B is for BYOBB, or how I learned to stop worrying and love rewind
        $this->assertEquals($expected, iterator_to_array($iter));
    }
}

