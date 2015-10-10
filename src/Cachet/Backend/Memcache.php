<?php
namespace Cachet\Backend;

use Cachet\Connector;
use Cachet\Dependency;
use Cachet\Item;

class Memcache extends IterationAdapter
{
    public $connector;

    public $unsafeFlush = false;

    public $prefix;

    public $useBackendExpirations = true;

    public function __construct($memcache=null)
    {
        $this->connector = $memcache instanceof Connector\Memcache
            ? $memcache
            : new Connector\Memcache($memcache)
        ;
    }

    function get($cacheId, $key)
    {
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, $cacheId, $key]);
        $memcache = $this->connector->memcache ?: $this->connector->connect();
        $encoded = $memcache->get($formattedKey);

        $item = null;
        if ($encoded)
            $item = @unserialize($encoded);

        return $item ?: null;
    }

    protected function setInStore(Item $item)
    {
        $ttl = 0;

        $memcache = $this->connector->memcache ?: $this->connector->connect();
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, $item->cacheId, $item->key]);
        $formattedItem = serialize($item);
        if ($this->useBackendExpirations && $item->dependency instanceof Dependency\TTL) {
            $ttl = $item->dependency->ttlSeconds;
            $item->dependency = null;
        }

        $memcache->set($formattedKey, $formattedItem, $ttl);
    }

    protected function deleteFromStore($cacheId, $key)
    {
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, $cacheId, $key]);
        $memcache = $this->connector->memcache ?: $this->connector->connect();
        return $memcache->delete($formattedKey);
    }

    protected function flushStore($cacheId)
    {
        $memcache = $this->connector->memcache ?: $this->connector->connect();
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
            $memcache->flush();
        }
    }
}
