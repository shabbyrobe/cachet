<?php
namespace Cachet\Backend;

use Cachet\Backend;
use Cachet\Item;

class XCache extends IterationAdapter
{
    public $prefix;

    public $useBackendExpirations = true;

    function __construct($prefix=null)
    {
        $this->prefix = $prefix;
    }

    /**
     * @param string $cacheId
     * @param string $key
     */
    function get($cacheId, $key)
    {
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, $cacheId, $key]);
        $item = xcache_get($formattedKey);
        if ($item) {
            $item = @unserialize($item);
        }
        return $item ?: null;
    }

    protected function setInStore(Item $item)
    {
        $ttl = null;
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, $item->cacheId, $item->key]);
        if (
            $this->useBackendExpirations &&
            $item->dependency && $item->dependency instanceof \Cachet\Dependency\TTL
        ) {
            $ttl = $item->dependency->ttlSeconds;
            $item->dependency = null;
        }

        $status = xcache_set($formattedKey, serialize($item), $ttl);
        if (!$status)
            throw new \RuntimeException("Could not set $formattedKey in xcache");
    }

    protected function deleteFromStore($cacheId, $key)
    {
        xcache_unset(\Cachet\Helper::formatKey([$this->prefix, $cacheId, $key]));
    }

    protected function flushStore($cacheId)
    {
        $prefix = \Cachet\Helper::formatKey([$this->prefix, $cacheId]);
        xcache_unset_by_prefix($prefix);
    }
}
