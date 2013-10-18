<?php
namespace Cachet\Backend;

use Cachet\Dependency;
use Cachet\Backend;
use Cachet\Item;

class APC implements Backend, Iterable, Counter
{
    public $iteratorChunkSize = 100;
    
    public $prefix;
    
    public $useBackendExpirations = true;

    function __construct($prefix=null)
    {
        $this->prefix = $prefix;
    }
    
    function get($cacheId, $key)
    {
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, $cacheId, $key]);
        $item = apc_fetch($formattedKey);
        return $item ?: null;
    }
    
    function set(Item $item)
    {
        $ttl = null;
        if ($this->useBackendExpirations && $item->dependency instanceof Dependency\TTL) {
            $ttl = $item->dependency->ttlSeconds;
            $item->dependency = null;
        }
        
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, $item->cacheId, $item->key]);
        
        // Item::compact() *increases* data usage!!
        // APCU uses significantly less memory when the Item instance is serialised directly
        $stored = apc_store($formattedKey, $item, $ttl);
        
        return $stored;
    }
    
    function delete($cacheId, $key)
    {
        $key = \Cachet\Helper::formatKey([$this->prefix, $cacheId, $key]);
        apc_delete($key);
    }
    
    function flush($cacheId)
    {
        $fullPrefix = \Cachet\Helper::formatKey([$this->prefix, $cacheId]);
        $iter = new \APCIterator(
            'user', 
            "~^".preg_quote($fullPrefix, "~")."~",
            APC_ITER_VALUE,
            $this->iteratorChunkSize
        );
        apc_delete($iter);
    }
    
    function keys($cacheId)
    {
        $fullPrefix = \Cachet\Helper::formatKey([$this->prefix, $cacheId]);
        $keyRegex = "~^".preg_quote($fullPrefix, "~")."~";
        foreach (new \APCIterator('user', $keyRegex, APC_ITER_VALUE, $this->iteratorChunkSize) as $item) {
            yield $item['value']->key;
        }
    }
    
    function items($cacheId)
    {
        $fullPrefix = \Cachet\Helper::formatKey([$this->prefix, $cacheId]);
        $keyRegex = "~^".preg_quote($fullPrefix, "~")."~";
        $iter = new \APCIterator('user', $keyRegex, APC_ITER_VALUE, $this->iteratorChunkSize);
        foreach ($iter as $item) {
            yield $item['value'];
        }
    }
    
    function increment($cacheId, $key, $by=1)
    {
        $fullKey = \Cachet\Helper::formatKey([$this->prefix, $cacheId, $key]);
        $value = apc_inc($fullKey, $by, $success);
        if (!$success)
            throw new \UnexpectedValueException("APC could not increment the key at $fullKey");

        return $value;
    }

    function decrement($cacheId, $key, $by=1)
    {
        $fullKey = \Cachet\Helper::formatKey([$this->prefix, $cacheId, $key]);
        $value = apc_dec($fullKey, $by, $success);
        if (!$success)
            throw new \UnexpectedValueException("APC could not decrement the key at $fullKey");

        return $value;
    }
}
