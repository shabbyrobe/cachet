<?php
namespace Cachet\Backend;

use Cachet\Dependency;
use Cachet\Backend;
use Cachet\Cache;
use Cachet\Item;

class PHPRedis implements Backend
{
    private $redis;
    
    public $prefix;
    
    function __construct(\Redis $redis, $prefix=null)
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
    }
    
    public function set(Item $item)
    {
        $formattedItem = serialize($item);
        $formattedKey = $this->formatKey($item->cacheId, $item->key);
        
        $query = $this->redis->multi(\Redis::PIPELINE);
        $ttl = null;
        if ($item->dependency instanceof Dependency\TTL) {
            $ttl = $item->dependency->ttlSeconds;
            unset($item->dependency);
            $query->setEx($formattedKey, $ttl, $formattedItem);
        }
        else {
            $query->set($formattedKey, $formattedItem);
            if ($item->dependency instanceof Dependency\Time)
                $query->expireAt($formattedKey, $expiration->time);
        }
        $query->exec();
    }
    
    public function get($cacheId, $key)
    {
        $key = $this->formatKey($cacheId, $key);
        $result = $this->redis->get($key);
        if ($result) {
            $itemData = @unserialize($result);
            if ($itemData)
                return Item::uncompact($itemData);
        }
    }
    
    public function delete($cacheId, $key)
    {
        $key = $this->formatKey($cacheId, $key);
        $this->redis->delete(array($key));
    }
    
    function flush($cacheId)
    {
        $prefix = $this->formatKey($cacheId, "");
        
        $keys = $this->redis->keys("$prefix*");
        $query = $this->redis->multi(\Redis::PIPELINE);
        foreach ($keys as $key) {
            $query->delete($key);
        }
        $query->exec();
    }
    
    private function formatKey($cacheId, $key)
    {
        return ($this->prefix ? "{$this->prefix}/" : "")."{$cacheId}/{$key}";
    }
}
