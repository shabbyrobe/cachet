<?php
namespace Cachet\Test;

abstract class CounterTestCase extends \CachetTestCase
{
    /**
     * This must ensure that the counter's backend is clean, i.e.
     * there must not be any value already stored in it.
     */
    public abstract function getCounter();

//    public abstract function getMaximumCounterValue();

    /**
     * @dataProvider dataForIncrement
     */
    function testSetValue($initial)
    {
        $counter = $this->getCounter();
        $counter->set('value', $initial);
        $this->assertEquals($initial, $counter->value('value'));
    }

    /**
     * @dataProvider dataForIncrement
     */
    function testIncrementDecrementAboveZeroWhenSet($initial, $by)
    {
        $counter = $this->getCounter();
        $counter->set('value', $initial);
        $this->assertEquals($initial + $by, $counter->increment('value', $by));
        $counter->increment('value', $by);
        $this->assertEquals($initial + $by, $counter->decrement('value', $by));
        $counter->decrement('value', $initial);
        $this->assertEquals($by, $counter->value('value'));
    }
    
    /**
     * @dataProvider dataForIncrement
     */
    function testIncrementDecrementAboveZeroWhenUnset($initial, $by)
    {
        $counter = $this->getCounter();
        $this->assertEquals($initial, $counter->increment('value', $initial));
        $this->assertEquals($initial + $by, $counter->increment('value', $by));
        $counter->increment('value', $by);
        $this->assertEquals($initial + $by, $counter->decrement('value', $by));
        $counter->decrement('value', $initial);
        $this->assertEquals($by, $counter->value('value'));
    }
    
    /**
     * @dataProvider dataForIncrement
     */
    function testIncrementByWhenSet($initial, $by)
    {
        $counter = $this->getCounter();
        $counter->set('value', $initial);
        $this->assertEquals($initial + $by, $counter->increment('value', $by));
        $this->assertEquals($initial + $by, $counter->value('value'));
    }

    /**
     * @dataProvider dataForIncrement
     */
    function testIncrementByWhenUnset($initial, $by)
    {
        $counter = $this->getCounter();
        $value = $counter->increment('value', $by);
        $this->assertEquals($by, $value);
        $this->assertEquals($by, $counter->value('value'));
    }

    /**
     * @dataProvider dataForIncrement
     */
    function testDecrementBy($initial, $by)
    {
        $counter = $this->getCounter();
        $counter->set('value', $initial);
        $this->assertEquals($initial - $by, $counter->decrement('value', $by));
        $this->assertEquals($initial - $by, $counter->value('value'));
    }

    /**
     * @dataProvider dataForIncrement
     */
    function testDecrementByWhenUnset($initial, $by)
    {
        $counter = $this->getCounter();
        $this->assertEquals(-$by, $counter->decrement('value', $by));
        $this->assertEquals(-$by, $counter->value('value'));
    }

    function dataForIncrement()
    {
        return [
            [1, 1],
            [2, 1],
            [4, 4],
            [10, 1],

            // TODO: APC doesn't support integer overflow
            // [PHP_INT_MAX, 1],

            // TODO: Memcache doesn't support negative counters
            // [5, -1],
        ];
    }

/*
    function testOverflow()
    {
        $counter = $this->getCounter();

        $value = $this->getMaximumCounterValue() - 1;
        $counter->set('value', $value);
        $this->assertEquals($value, $counter->value('value'));

        $value++;
        $this->assertEquals($value, $counter->increment('value'));

        $value++;
        $this->assertEquals($value, $counter->increment('value'));
    }
*/

    function testMultipleCounters()
    {
        $counter = $this->getCounter();
        $counter->increment('value');
        $counter->increment('value', 2);
        $counter->increment('value2');
        $counter->increment('value');
        $counter->increment('value2', 2);
        $this->assertEquals(4, $counter->value('value'));
        $this->assertEquals(3, $counter->value('value2'));
    }
}
