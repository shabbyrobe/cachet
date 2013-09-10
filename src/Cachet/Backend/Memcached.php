<?php
namespace Cachet\Backend;

use Cachet\Backend;
use Cachet\Dependency;
use Cachet\Item;

class Memcached extends Iteration\Adapter
{
    public $memcached;
    
    public $unsafeFlush = false;
    
    public $prefix;
    
    public function __construct($memcached=null)
    {
        if ($memcached instanceof \Memcached) {
            $this->memcached = $memcached;
        }
        else {
            $this->memcached = new \Memcached();
            if ($memcached)
                $this->memcached->addServers($memcached);
        }
    }
    
    function get($cacheId, $key)
    {
        $formattedKey = $this->formatKey($cacheId, $key);
        return @unserialize($this->memcached->get($formattedKey));
    }
    
    protected function setInStore(Item $item)
    {
        $ttl = 0;
        
        $formattedKey = $this->formatKey($item->cacheId, $item->key);
        $formattedItem = serialize($item);
        if ($item->dependency instanceof Dependency\TTL) {
            $ttl = $item->dependency->ttlSeconds;
        }
        
        $this->memcached->set($formattedKey, $formattedItem, $ttl);
    }
    
    protected function deleteFromStore($cacheId, $key)
    {
        $formattedKey = $this->formatKey($cacheId, $key);
        return $this->memcached->delete($formattedKey);
    }
    
    protected function flushStore($cacheId)
    {
        if ($this->keyBackend) {
            foreach ($this->keys($cacheId) as $key) {
                $this->delete($cacheId, $key);
            }
        }
        elseif (!$this->unsafeFlush) {
                throw new \RuntimeException("Memcache is not iterable by default. Please either call setKeyBackend or unsafeFlush.");
        }
        else {
            $this->memcached->flush();
        }
    }
    
    private function formatKey($cacheId, $key)
    {
        return ($this->prefix ? "{$this->prefix}/" : "")."{$cacheId}/{$key}";
    }
}
