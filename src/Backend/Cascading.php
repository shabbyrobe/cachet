<?php
namespace Cachet\Backend;

use Cachet\Backend;
use Cachet\Item;

class Cascading implements Backend, Iterator
{
    private $backends;
    private $reverseBackends;

    public function __construct(array $backends)
    {
        if (count($backends) < 2)
            throw new \InvalidArgumentException("No point cascading with less than two backends!");

        $this->backends = $backends;
        $this->reverseBackends = array_reverse($backends);
    }

    /**
     * @param string $cacheId
     * @param string $key
     * @return \Cachet\Item
     */
    function get($cacheId, $key)
    {
        $item = null;
        $missedBackends = array();
        foreach ($this->backends as $backend) {
            $item = $backend->get($cacheId, $key);
            if ($item) {
                break;
            } else {
                $missedBackends[] = $backend;
            }
        }
        if ($item) {
            foreach (array_reverse($missedBackends) as $missedBackend) {
                $missedBackend->set($item);
            }
        }
        return $item;
    }

    function set(Item $item)
    {
        foreach ($this->reverseBackends as $backend) {
            $backend->set($item);
        }
    }

    /**
     * @param string $cacheId
     * @param string $key
     * @return void
     */
    function delete($cacheId, $key)
    {
        foreach ($this->reverseBackends as $backend) {
            $backend->delete($cacheId, $key);
        }
    }

    /**
     * @param string $cacheId
     * @return void
     */
    function flush($cacheId)
    {
        foreach ($this->reverseBackends as $backend) {
            $backend->flush($cacheId);
        }
    }

    /**
     * @param string $cacheId
     * @return \Iterator
     */
    function keys($cacheId)
    {
        return $this->reverseBackends[0]->keys($cacheId);
    }

    /**
     * @param string $cacheId
     * @return \Iterator
     */
    function items($cacheId)
    {
        return $this->reverseBackends[0]->items($cacheId);
    }
}
