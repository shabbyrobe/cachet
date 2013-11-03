<?php
namespace Cachet\Backend;

use Cachet\Backend;
use Cachet\Item;

/**
 * Very simple sharding cache backend.
 * This does not tolerate changing the number of backends. You will
 * need to flush the entire cache if you do unless you want unpredictable
 * results.
 */
class Sharding implements Backend, Iterable
{
    private $backends;
    private $backendCount;

    public function __construct($backends)
    {
        $count = count($backends);
        if ($count < 2)
            throw new \InvalidArgumentException("No point sharding with less than two backends!");

        $this->backends = array_values($backends);
        $this->backendCount = $count;
    }

    function get($cacheId, $key)
    {
        $backend = $this->selectBackend($cacheId, $key);
        return $backend->get($cacheId, $key);
    }

    function set(Item $item)
    {
        $backend = $this->selectBackend($item->cacheId, $item->key);
        return $backend->set($item);
    }

    function delete($cacheId, $key)
    {
        $backend = $this->selectBackend($cacheId, $key);
        return $backend->delete($cacheId, $key);
    }

    function flush($cacheId)
    {
        foreach ($this->backends as $backend)
            $backend->flush($cacheId);
    }

    function keys($cacheId)
    {
        foreach ($this->backends as $backend) {
            foreach ($backend->keys($cacheId) as $key)
                yield $key;
        }
    }

    function items($cacheId)
    {
        foreach ($this->backends as $backend) {
            foreach ($backend->items($cacheId) as $item)
                yield $item;
        }
    }

    private function selectBackend($cacheId, $key)
    {
        $hashValue = "$cacheId/$key";
        $backendId = abs(crc32($hashValue)) % $this->backendCount;
        return $this->backends[$backendId];
    }
}
