<?php
namespace Cachet\Test\Util;

use Cachet\Util\BatchingMapIterator;

class BatchingMapIteratorTest extends \CachetTestCase
{
    /**
     * @dataProvider dataForBatching
     */
    function testBatchingWithoutKeys($count, $batchSize)
    {
        $values = range(1, $count, 1);
        $cursor = 0;
        $callCount = 0;
        $iter = new BatchingMapIterator(
            function () use (&$cursor) {
                $cursor = 0;
            },
            function () use (&$callCount, &$cursor, $values, $batchSize) {
                $callCount++;
                $ret = array_slice($values, $cursor, $batchSize);
                $cursor += $batchSize;
                return $ret;
            }
        );

        $this->assertEquals($values, iterator_to_array($iter));
        $this->assertEquals(ceil($count / $batchSize + 1), $callCount);

        // double-tap to make sure you can re-use
        $this->assertEquals($values, iterator_to_array($iter));
    }

    /**
     * @dataProvider dataForBatching
     */
    function testBatchingWithKeys($count, $batchSize)
    {
        $values = range(1, $count, 1);
        $cursor = 0;
        $callCount = 0;
        $iter = new BatchingMapIterator(
            function () use (&$cursor) {
                $cursor = 0;
            },
            function () use (&$callCount, &$cursor, $values, $batchSize) {
                $callCount++;
                $ret = array_slice($values, $cursor, $batchSize, !!'preserveKeys');
                $cursor += $batchSize;
                return $ret;
            },
            !!'useKeys'
        );

        $this->assertEquals($values, iterator_to_array($iter));
        $this->assertEquals(ceil($count / $batchSize + 1), $callCount);

        // double-tap to make sure you can re-use
        $this->assertEquals($values, iterator_to_array($iter));
    }

    function dataForBatching()
    {
        return [
            [100, 20],
            [10, 1],
            [50, 60],
        ];
    }
}
