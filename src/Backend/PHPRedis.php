<?php
namespace Cachet\Backend;

use Cachet\Dependency;
use Cachet\Backend;
use Cachet\Item;

class PHPRedis implements Backend, Iterator
{
    public $connector;

    public $prefix;

    public $useBackendExpirations = true;

    public function __construct($redis, $prefix=null)
    {
        if (!$redis instanceof \Cachet\Connector\PHPRedis) {
            $this->connector = new \Cachet\Connector\PHPRedis($redis);
        } else {
            $this->connector = $redis;
        }
        $this->prefix = $prefix;
    }

    public function set(Item $item)
    {
        if ($this->useBackendExpirations && $item->dependency instanceof Dependency\TTL) {
            $this->setWithTTL($item, $item->dependency);
        }
        elseif ($this->useBackendExpirations && $item->dependency instanceof Dependency\Time) {
            $this->setWithExpireTime($item, $item->dependency);
        }
        else {
            $redis = $this->connector->redis ?: $this->connector->connect();
            $redis->set(
                \Cachet\Helper::formatKey([$this->prefix, $item->cacheId, $item->key]),
                $this->formatItem($item)
            );
        }
    }

    private function setWithTTL($item, Dependency\TTL $dependency)
    {
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, $item->cacheId, $item->key]);
        $ttl = $dependency->ttlSeconds;
        $item->dependency = null;
        $formattedItem = $this->formatItem($item);

        $redis = $this->connector->redis ?: $this->connector->connect();
        $query = $redis->multi(\Redis::PIPELINE);
        $query->setEx($formattedKey, $ttl, $formattedItem);
        $query->exec();
    }

    private function setWithExpireTime($item, Dependency\Time $dependency)
    {
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, $item->cacheId, $item->key]);
        $time = $dependency->time;
        $item->dependency = null;
        $formattedItem = $this->formatItem($item);

        $redis = $this->connector->redis ?: $this->connector->connect();
        $query = $redis->multi(\Redis::PIPELINE);
        $query->set($formattedKey, $formattedItem);
        $query->expireAt($formattedKey, $time);
        $query->exec();
    }

    private function formatItem($item)
    {
        return serialize($item->compact());
    }

    /**
     * @param string $cacheId
     * @param string $key
     */
    public function get($cacheId, $key)
    {
        $key = \Cachet\Helper::formatKey([$this->prefix, $cacheId, $key]);
        $redis = $this->connector->redis ?: $this->connector->connect();
        $result = $redis->get($key);
        if ($result) {
            return $this->decode($result);
        }
    }

    /**
     * @param string $cacheId
     * @param string $key
     * @return void
     */
    public function delete($cacheId, $key)
    {
        $key = \Cachet\Helper::formatKey([$this->prefix, $cacheId, $key]);
        $redis = $this->connector->redis ?: $this->connector->connect();
        $redis->del(array($key));
    }

    /**
     * @param string $cacheId
     * @return void
     */
    function flush($cacheId)
    {
        $prefix = \Cachet\Helper::formatKey([$this->prefix, $cacheId]);
        $redis = $this->connector->redis ?: $this->connector->connect();
        $keys = $redis->keys("$prefix*");
        $query = $redis->multi(\Redis::PIPELINE);
        foreach ($keys as $key) {
            $query->delete($key);
        }
        $query->exec();
    }

    /**
     * @param string $cacheId
     * @return \Iterator|array
     */
    function keys($cacheId)
    {
        $prefix = \Cachet\Helper::formatKey([$this->prefix, $cacheId])."/";
        $len = strlen($prefix);
        $redis = $this->connector->redis ?: $this->connector->connect();
        $redisKeys = $redis->keys("$prefix*");

        $keys = [];
        foreach ($redisKeys as $key) {
            $keys[] = substr($key, $len);
        }

        sort($keys);
        return $keys;
    }

    /**
     * @param string $cacheId
     * @return \Iterator|array
     */
    function items($cacheId)
    {
        $iter = new \Cachet\Util\MapIterator($this->keys($cacheId), function($item) use ($cacheId) {
            return $this->get($cacheId, $item);
        });
        return new \CallbackFilterIterator($iter, function($item) {
            return $item instanceof Item;
        });
    }

    function decode($data)
    {
        $itemData = @unserialize($data);
        if ($itemData) {
            return Item::uncompact($itemData);
        }
    }
}
