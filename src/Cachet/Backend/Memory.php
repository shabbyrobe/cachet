<?php
namespace Cachet\Backend;

use Cachet\Backend;
use Cachet\Item;

/**
 * Ephemeral memory-backed cache implementation
 */
class Memory implements Backend, Iterator
{
    public $data = array();

    function get($cacheId, $key)
    {
        $item = null;
        if (isset($this->data[$cacheId][$key])) {
            $item = $this->data[$cacheId][$key];
        }
        return $item;
    }

    function set(Item $item)
    {
        if (!isset($this->data[$item->cacheId])) {
            $this->data[$item->cacheId] = array();
        }
        if ($item->value instanceof \Closure) {
            throw new \Exception("Serialization of 'Closure' is not allowed");
        }
        $this->data[$item->cacheId][$item->key] = $item;
    }

    function delete($cacheId, $key)
    {
        unset($this->data[$cacheId][$key]);
    }

    function flush($cacheId)
    {
        unset($this->data[$cacheId]);
    }

    function keys($cacheId)
    {
        if (isset($this->data[$cacheId])) {
            return array_keys($this->data[$cacheId]);
        } else {
            return[];
        }
    }

    function items($cacheId)
    {
        if (isset($this->data[$cacheId])) {
            return array_values($this->data[$cacheId]);
        } else {
            return [];
        }
    }
}
