<?php
namespace Cachet\Test;

trait IterableBackendYieldMemoryTest
{
    function testKeyIteratorMemoryGrowthRemainsStable()
    {
        $items = 200;
        $bytesPerItem = 10000;
        $cacheId = 'a';
        $backend = $this->getBackend();
        $this->fillBackend($backend, $cacheId, $items, $bytesPerItem);
        $iterator = $backend->keys($cacheId);
        $this->assertMemoryGrowthAcceptable($iterator, $items, $bytesPerItem);
    }

    function fillBackend($backend, $cacheId, $items, $bytesPerItem)
    {
        for ($i=0; $i<$items; $i++) {
            $item = new \Cachet\Item($cacheId, $i, str_repeat('0', $bytesPerItem));
            $backend->set($item);
        }
    }

    function assertMemoryGrowthAcceptable($iterator, $items, $bytesPerItem)
    {
        $i = 0;
        $max = $memory = memory_get_usage();
        foreach ($iterator as $k=>$v) {
            $max = max($max, memory_get_usage());
            ++$i;
        }
        $this->assertEquals($items, $i);

        $peakGrowth = $max - $memory;
        $maximumGrowth = $items * $bytesPerItem;
        $this->assertLessThan($maximumGrowth, $peakGrowth);
    }
}
