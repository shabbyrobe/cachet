<?php
namespace Cachet\Test;

trait IteratorBackendYieldMemoryTest
{
    function dataForIteratorMemoryGrowth()
    {
        return [
            // below 200 the test is meaningless - the APC backend uses 50 as the default
            // batch size by default and the file iterator seems to cache up to about 50
            // entries.
            [200, 10, 10000],
            [200, 100, 1000],
            [2000, 100, 1000],
        ];
    }

    /**
     * @group slow
     * @dataProvider dataForIteratorMemoryGrowth
     * @medium
     */
    function testIteratorMemoryGrowth($items, $bytesPerItem, $bytesPerKey)
    {
        $cacheId = 'a';
        $backend = $this->getBackend();
        list ($keysMemoryEstimate, $itemsMemoryEstimate) = $this->fillBackend(
            $backend, $cacheId, $items, $bytesPerKey, $bytesPerItem
        );
        
        $start = $max = memory_get_usage();
        foreach ($backend->keys($cacheId) as $k) {
            $max = max($max, memory_get_usage());
        }
        $this->assertLessThan($start + $keysMemoryEstimate, $max);

        $start = $max = memory_get_usage();
        foreach ($backend->items($cacheId) as $k) {
            $max = max($max, memory_get_usage());
        }
        $this->assertLessThan($start + $itemsMemoryEstimate, $max);
    }

    function fillBackend($backend, $cacheId, $items, $bytesPerKey, $bytesPerItem)
    {
        $keysMemoryEstimate = 0;
        $itemsMemoryEstimate = 0;
        for ($i=0; $i<$items; $i++) {
            $key = str_repeat('0', $bytesPerKey - strlen($i)).$i;
            $value = str_repeat('0', $bytesPerItem);
            $keysMemoryEstimate += strlen($key);
            $itemsMemoryEstimate += strlen($key) + $bytesPerItem;
            $item = new \Cachet\Item($cacheId, $key, $value);
            $backend->set($item);
        }

        return [$keysMemoryEstimate, $itemsMemoryEstimate];
    }

    /*
    function testKeyIteratorMemoryGrowthRemainsStable()
    {
        $items = 2000;
        $bytesPerItem = 1000;

        $cacheId = 'a';
        $backend = $this->getBackend();
        $this->fillBackend($backend, $cacheId, $items, $bytesPerItem);
        list ($memory, $iterator) = $this->setupIterator($cacheId, $backend, $items * strlen($items));

        $this->assertMemoryGrowthAcceptable($memory, $iterator, $items);
    }

    private function setupIterator($cacheId, $backend, $estimatedMaxSize)
    {
        $initialMemory = $memory = memory_get_usage();
        $iterator = $backend->keys($cacheId);
        $memory = memory_get_usage();
        $this->assertLessThan($memory, $initialMemory + $estimatedMaxSize);
        return [$memory, $iterator];
    }

    function assertMemoryGrowthAcceptable($memory, $iterator, $items)
    {
        $i = 0;
        foreach ($iterator as $k=>$v) {
            if ($i > 0) {
                $this->assertLessThanOrEqual($memory, memory_get_usage());
                ++$i;
            }
        }
        $this->assertEquals($items, $i);
    }

    function assertMemoryGrowthAcceptable($memory, $iterator, $items, $bytesPerItem)
    {
        $i = 0;
        $max = $memory;
        foreach ($iterator as $k=>$v) {
            $max = max($max, memory_get_usage());
            ++$i;
        }
        $this->assertEquals($items, $i);

        $peakGrowth = $max - $memory;
        $maximumGrowth = method_exists($this, 'getMaximumMemoryGrowth')
            ? call_user_func([$this, 'getMaximumMemoryGrowth'], $items, $bytesPerItem)
            : $bytesPerItem * 2
        ;
        var_dump(get_class($this));
        var_dump($peakGrowth);
        $this->assertLessThan($maximumGrowth, $peakGrowth);
    }
    */
}
