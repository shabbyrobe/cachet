<?php
namespace Cachet\Backend;

use Cachet\Dependency;
use Cachet\Backend;
use Cachet\Item;

/**
 * @deprecated All pre-opcache versions of PHP are EOL as of 201612,
 *   so we can expect that all users of APC will eventually end up on
 *   APCU at some point. This class won't be removed for the forseeable
 *   future, but as the apc_* compatibility functions have been removed
 *   from the apcu extension for >= 7.0, the new class should be the way
 *   to go from now on for all new code.
 */
class APC implements Backend, Iterator
{
    public $iteratorChunkSize = 50;

    public $prefix;

    public $useBackendExpirations = true;

    function __construct($prefix=null)
    {
        $this->prefix = $prefix;
    }

    /**
     * @param string $cacheId
     * @param string $key
     * @return \Cachet\Item|null
     */
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
        if (!$stored) {
            throw new \RuntimeException("APC: key {$item->key} failed to store in {$item->cacheId}");
        }
    }

    /**
     * @param string $cacheId
     * @param string $key
     * @return void
     */
    function delete($cacheId, $key)
    {
        $key = \Cachet\Helper::formatKey([$this->prefix, $cacheId, $key]);
        apc_delete($key);
    }

    /**
     * @param string $cacheId
     * @return void
     * @suppress PhanUndeclaredClassMethod
     */
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

    /**
     * @param string $cacheId
     * @return \Iterator
     * @suppress PhanUndeclaredClassMethod
     */
    function keys($cacheId)
    {
        $fullPrefix = \Cachet\Helper::formatKey([$this->prefix, $cacheId]);
        $keyRegex = "~^".preg_quote($fullPrefix, "~")."~";
        $iter = new \APCIterator('user', $keyRegex, APC_ITER_KEY, $this->iteratorChunkSize);
        $prefixLen = strlen($fullPrefix) + 1;
        return new \Cachet\Util\MapIterator($iter, function($item) use ($prefixLen) {
            return substr($item['key'], $prefixLen);
        });
    }

    /**
     * @param string $cacheId
     * @return \Iterator
     * @suppress PhanUndeclaredClassMethod
     */
    function items($cacheId)
    {
        $fullPrefix = \Cachet\Helper::formatKey([$this->prefix, $cacheId]);
        $keyRegex = "~^".preg_quote($fullPrefix, "~")."~";
        $iter = new \APCIterator('user', $keyRegex, APC_ITER_VALUE, $this->iteratorChunkSize);
        return new \Cachet\Util\MapIterator($iter, function($item) {
            return $item['value'];
        });
    }
}
