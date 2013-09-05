<?php
namespace Cachet\Backend;

use Cachet\Backend;
use Cachet\Item;

/**
 * Ephemeral memory-backed cache implementation
 */
class Memory implements Backend
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
        if (!isset($this->data[$item->cacheId]))
            $this->data[$item->cacheId] = array();
        
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
}
