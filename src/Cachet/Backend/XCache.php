<?php
namespace Cachet\Backend;

use Cachet\Backend;
use Cachet\Cache;
use Cachet\Item;

class XCache implements Backend
{
    public $prefix;
    
    function __construct($prefix=null)
    {
        $this->prefix = $prefix;
    }
    
    function get($cacheId, $key)
    {
        $formattedKey = $this->formatKey($cacheId, $key);
        $item = xcache_get($formattedKey);
        if ($item) {
            $item = @unserialize($item);
            if (!$item)
                $this->delete($cacheId, $key);
        }
        return $item;
    }
    
    function set(Item $item)
    {
        $ttl = null;
        $formattedKey = $this->formatKey($item->cacheId, $item->key);
        if (isset($item->dependency) && $item->dependency instanceof Dependency\TTL) {
            $ttl = $item->dependency->ttlSeconds;
            unset($item->dependency);
        }
        
        return xcache_set($formattedKey, serialize($item), $ttl);
    }
    
    function delete($cacheId, $key)
    {
        xcache_unset($this->formatKey($cacheId, $key));
    }
    
    function flush($cacheId)
    {
        $prefix = $this->formatKey($cacheId, "");
        xcache_unset_by_prefix($prefix);
    }
    
    private function formatKey($cacheId, $key)
    {
        return ($this->prefix ? "{$this->prefix}/" : "")."{$cacheId}/{$key}";
    }
}
