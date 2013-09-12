<?php
namespace Cachet\Backend;

use Cachet\Dependency;
use Cachet\Backend;
use Cachet\Item;

class PHPRedis implements Backend, Iterable
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
        if ($item->dependency instanceof Dependency\TTL) {
            $this->setWithTTL($item);
        }
        elseif ($item->dependency instanceof Dependency\Time) {
            $this->setWithExpireTime($item);
        }
        else {
            $this->redis->set(
                $this->formatKey($item->cacheId, $item->key),
                $this->formatItem($item)
            );
        }
    }
    
    private function setWithTTL($item)
    {
        $formattedKey = $this->formatKey($item->cacheId, $item->key);
        $ttl = $item->dependency->ttlSeconds;
        $item->dependency = null;
        $formattedItem = $this->formatItem($item);
        
        $query = $this->redis->multi(\Redis::PIPELINE);
        $query->setEx($formattedKey, $ttl, $formattedItem);
        $query->exec();
    }
    
    private function setWithExpireTime($item)
    {
        $formattedKey = $this->formatKey($item->cacheId, $item->key);
        $time = $item->dependency->time;
        $item->dependency = null;
        $formattedItem = $this->formatItem($item);
        
        $query = $this->redis->multi(\Redis::PIPELINE);
        $query->set($formattedKey, $formattedItem);
        $query->expireAt($formattedKey, $time);
        $query->exec();
    }
    
    private function formatItem($item)
    {
        return serialize($item->compact());
    }
    
    public function get($cacheId, $key)
    {
        $key = $this->formatKey($cacheId, $key);
        $result = $this->redis->get($key);
        
        if ($result) {
            return $this->decode($result);
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
    
    function keys($cacheId)
    {
        $prefix = $this->formatKey($cacheId, "");
        $len = strlen($prefix);
        $redisKeys = $this->redis->keys("$prefix*");
        
        $keys = [];
        foreach ($redisKeys as $key) {
            $keys[] = substr($key, $len);
        }
        
        sort($keys);
        return $keys;
    }
    
    function items($cacheId)
    {
        foreach ($this->keys($cacheId) as $key) {
            $item = $this->get($cacheId, $key);
            if ($item)
                yield $item;
        }
    }
    
    function decode($data)
    {
        $itemData = @unserialize($data);
        if ($itemData)
            return Item::uncompact($itemData);
    }

    private function formatKey($cacheId, $key)
    {
        // WARNING: if this changes, key iteration and flushing will break.
        return ($this->prefix ? "{$this->prefix}/" : "")."{$cacheId}/{$key}";
    }
}
