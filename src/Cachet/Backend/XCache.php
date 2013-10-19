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
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, $cacheId, $key]);
        $item = xcache_get($formattedKey);
        if ($item) {
            $item = @unserialize($item);
        }
        return $item ?: null;
    }
    
    protected function setInStore(Item $item)
    {   
        $ttl = null;
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, $item->cacheId, $item->key]);
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
        xcache_unset(\Cachet\Helper::formatKey([$this->prefix, $cacheId, $key]));
    }
    
    protected function flushStore($cacheId)
    {
        $prefix = \Cachet\Helper::formatKey([$this->prefix, $cacheId]);
        xcache_unset_by_prefix($prefix);
    }    
}
