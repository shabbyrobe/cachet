<?php
namespace Cachet\Backend;

use Cachet\Backend;
use Cachet\Item;

class XCache extends IterationAdapter
{   
    public $prefix;
    
    public $useBackendExpirations = true;

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
        }
        return $item ?: null;
    }
    
    protected function setInStore(Item $item)
    {   
        $ttl = null;
        $formattedKey = $this->formatKey($item->cacheId, $item->key);
        if (
            $this->useBackendExpirations && 
            $item->dependency && $item->dependency instanceof Dependency\TTL
        ) {
            $ttl = $item->dependency->ttlSeconds;
            $item->dependency = null;
        }
        
        xcache_set($formattedKey, serialize($item), $ttl);
    }
    
    protected function deleteFromStore($cacheId, $key)
    {
        xcache_unset($this->formatKey($cacheId, $key));
    }
    
    protected function flushStore($cacheId)
    {
        $prefix = $this->formatKey($cacheId, "");
        xcache_unset_by_prefix($prefix);
    }
    
    private function formatKey($cacheId, $key)
    {
        return ($this->prefix ? "{$this->prefix}/" : "")."{$cacheId}/{$key}";
    }
}
