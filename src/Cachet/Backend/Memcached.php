<?php
namespace Cachet\Backend;

use Cachet\Backend;
use Cachet\Dependency;
use Cachet\Item;

class Memcached extends IterationAdapter implements Counter
{
    public $memcached;
    
    public $unsafeFlush = false;
    
    public $prefix;

    public $useBackendExpirations = true;
    
    public $counterTTL = null;

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
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, $cacheId, 'value', $key]);
        $encoded = $this->memcached->get($formattedKey);
        
        $item = null;
        if ($encoded)
            $item = @unserialize($encoded);
        
        return $item ?: null;
    }
    
    protected function setInStore(Item $item)
    {
        $ttl = 0;
        
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, $item->cacheId, 'value', $item->key]);
        $formattedItem = serialize($item);
        if ($this->useBackendExpirations && $item->dependency instanceof Dependency\TTL) {
            $ttl = $item->dependency->ttlSeconds;
            $item->dependency = null;
        }
        
        $this->memcached->set($formattedKey, $formattedItem, $ttl);
    }
    
    protected function deleteFromStore($cacheId, $key)
    {
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, $cacheId, 'value', $key]);
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
            throw new \RuntimeException(
                "Memcache is not iterable by default. Please either call setKeyBackend or unsafeFlush."
            );
        }
        else {
            $this->memcached->flush();
        }
    }

    function increment($cacheId, $key, $by=1)
    {
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, $cacheId, 'count', $key]);
        $value = $this->memcached->increment($formattedKey, $by, null, $this->counterTTL);
        return $value;
    }

    function decrement($cacheId, $key, $by=1)
    {
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, $cacheId, 'count', $key]);
        $value = $this->memcached->decrement($formattedKey, $by, null, $this->counterTTL);
        return $value;
    }
}
