<?php
namespace Cachet\Backend;

use Cachet\Dependency;
use Cachet\Backend;
use Cachet\Cache;
use Cachet\Item;

class APC implements Backend, Iteration\Iterable
{
    public $iteratorChunkSize = 100;
    
    public $prefix;
    
    function __construct($prefix=null)
    {
        $this->prefix = $prefix;
    }
    
    function get($cacheId, $key)
    {
        return apc_fetch($this->formatKey($cacheId, $key));
    }
    
    function set(Item $item)
    {
        $ttl = null;
        if ($item->dependency instanceof Dependency\TTL) {
            $ttl = $item->dependency->ttlSeconds;
            $item->dependency = null;
        }
        
        $formattedKey = $this->formatKey($item->cacheId, $item->key);
        
        // Item::compact() *increases* data usage!!
        // APCU uses significantly less memory when the Item instance is serialised directly
        $stored = apc_store($formattedKey, $item, $ttl);
        
        return $stored;
    }
    
    function delete($cacheId, $key)
    {
        $key = $this->formatKey($cacheId, $key);
        apc_delete($key);
    }
    
    function flush($cacheId)
    {
        $fullPrefix = $this->formatKey($cacheId, "");
        $iter = new \APCIterator('user', "~^".preg_quote($fullPrefix, "~")."~", APC_ITER_VALUE, $this->iteratorChunkSize);
        apc_delete($iter);
    }
    
    function keys($cacheId)
    {
        $fullPrefix = $this->formatKey($cacheId, "");
        $keyRegex = "~^".preg_quote($fullPrefix, "~")."~";
        /*
        foreach (new \APCIterator('user', $keyRegex, APC_ITER_VALUE, $this->iteratorChunkSize) as $item) {
            yield $item['value']->key;
        }
        */
        return new Iteration\APC($keyRegex, $this->iteratorChunkSize, 'key');
    }
    
    function items($cacheId)
    {
        $fullPrefix = $this->formatKey($cacheId, "");
        $keyRegex = "~^".preg_quote($fullPrefix, "~")."~";
        return new Iteration\APC($keyRegex, $this->iteratorChunkSize, 'item');
    }
    
    private function formatKey($cacheId, $key)
    {
        return ($this->prefix ? "{$this->prefix}/" : "")."{$cacheId}/{$key}";
    }
}
