<?php
namespace Cachet\Test;

abstract class CounterTestCase extends \CachetTestCase
{
    public abstract function getCounter();

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
    function testIncrementBy($initial, $by)
    {
        $counter = $this->getCounter();
        $counter->set('value', $initial);
        $counter->increment('value', $by);
        $this->assertEquals($initial + $by, $counter->value('value'));
    }

    /**
     * @dataProvider dataForIncrement
     */
    function testDecrementBy($initial, $by)
    {
        $counter = $this->getCounter();
        $counter->set('value', $initial);
        $counter->decrement('value', $by);
        $this->assertEquals($initial - $by, $counter->value('value'));
    }

    function dataForIncrement()
    {
        return [
            [1, 1],
            [2, 1],
            [4, 4],
        ];
    }
}
