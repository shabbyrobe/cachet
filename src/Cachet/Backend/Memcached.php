<?php
namespace Cachet\Backend;

use Cachet\Backend;
use Cachet\Dependency;
use Cachet\Item;

class Memcached implements Backend
{
    public $memcached;
    
    public $iUnderstandThatFlushingDeletesAbsolutelyEverythingFromMemcache = false;
    
    public function __construct($memcached=null)
    {
        if ($memcached instanceof \Memcached) {
            $this->memcached = $memcached;
        }
        else {
            $this->memcached = new \Memcached($memcached);
            if ($memcached)
                $this->memcached->addServers($memcached);
        }
    }

    function delete($cacheId, $key)
    {
        return $this->memcached->delete($key);
    }
    
    function get($cacheId, $key)
    {
        return @unserialize($this->memcached->get($key));
    }
    
    function set(Item $item)
    {
        $ttl = 0;
        
        $formattedKey = $item->key;
        $formattedItem = serialize($item);
        if ($item->dependency instanceof Dependency\TTL) {
            $ttl = $item->dependency->ttlSeconds;
        }
        return $this->memcached->set($formattedKey, $formattedItem, $ttl);
    }
    
    function flush($cacheId)
    {
        if (!$this->iUnderstandThatFlushingDeletesAbsolutelyEverythingFromMemcache) {
            throw new \RuntimeException("Memcache doesn't support key listing. It's not "
                ."possible to flush without completely flushing memcache. Please set the"
                ."``iUnderstandThatFlushingDeletesAbsolutelyEverythingFromMemcache`` variable"
                ."to enable this behaviour"
            );
        }
        else {
            $this->memcached->flush();
        }
    }
}
