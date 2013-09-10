<?php
namespace Cachet\Backend;

use Cachet\Dependency;
use Cachet\Backend;
use Cachet\Cache;
use Cachet\Item;

class PHPRedis implements Backend, Iteration\Iterable
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
        $formattedItem = serialize($item->compact());
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
            elseif ($item->dependency instanceof Dependency\Permanent)
                $query->persist($formattedKey);
        }
        $query->exec();
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
        // WARNING: if the formatKey method ever changes, this will break!
        
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
        $keys = $this->keys($cacheId);
        return new Iteration\BackendFetcher($this, $cacheId, $keys);
    }

    function decode($data)
    {
        $itemData = @unserialize($data);
        if ($itemData)
            return Item::uncompact($itemData);
    }

    private function formatKey($cacheId, $key)
    {
        return ($this->prefix ? "{$this->prefix}/" : "")."{$cacheId}/{$key}";
    }
}
