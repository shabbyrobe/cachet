<?php
namespace Cachet\Backend;

use Cachet\Backend;
use Cachet\Item;

class XCache extends IterationAdapter implements Counter
{   
    public $prefix;
    
    public $useBackendExpirations = true;

    public $counterTTL = null;

    function __construct($prefix=null)
    {
        $this->prefix = $prefix;
    }
    
    function get($cacheId, $key)
    {
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, $cacheId, 'value', $key]);
        $item = xcache_get($formattedKey);
        if ($item) {
            $item = @unserialize($item);
        }
        return $item ?: null;
    }
    
    protected function setInStore(Item $item)
    {   
        $ttl = null;
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, $item->cacheId, 'value', $item->key]);
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
        xcache_unset(\Cachet\Helper::formatKey([$this->prefix, $cacheId, 'value', $key]));
    }
    
    protected function flushStore($cacheId)
    {
        $prefix = \Cachet\Helper::formatKey([$this->prefix, $cacheId]);
        xcache_unset_by_prefix($prefix);
    }
    
    function increment($cacheId, $key, $by=1)
    {
        $formatted = \Cachet\Helper::formatKey([$this->prefix, $cacheId, 'count', $key]);
        $value = xcache_inc($formatted, $by, $this->counterTTL);
        return $value;
    }

    function decrement($cacheId, $key, $by=1)
    {
        $formatted = \Cachet\Helper::formatKey([$this->prefix, $cacheId, 'count', $key]);
        $value = xcache_dec($formatted, $by, $this->counterTTL);
        return $value;
    }
}
