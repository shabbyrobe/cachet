<?php
namespace Cachet\Backend;

use Cachet\Dependency;
use Cachet\Backend;
use Cachet\Item;
use Cachet\Util\APCUIterator;

/**
 * Starting with PHP 7.0, apcu removed the apc_* functions by default,
 * moving them into a separate extension called apc_bc. 
 *
 * To allow libraries like this to maintain API compatibility with
 * older PHP versions, we can slow down our own code by branching on version,
 * introduce a useless abstraction, or ask for a compatibility extension to be
 * installed. The latter would be a good solution, if only it didn't force
 * the burden of the extra extension onto adopters of NEWER PHP versions (!).
 *
 * This is totally backwards. They should have made an extension for PHP <=5.6
 * called apcu_fc which adds apcu_* to earlier versions, placing the burden
 * back on people clinging to old versions.
 *
 * Sometimes a bit of copy/paste is better than an abstraction. I don't
 * want to add a level of indirection to this, so I'm deprecating the
 * APC class and will support the copypasta for the time being.
 */
class APCU implements Backend, Iterator
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
        $item = apcu_fetch($formattedKey);
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
        $stored = apcu_store($formattedKey, $item, $ttl);
        if (!$stored) {
            throw new \RuntimeException("APCU: key {$item->key} failed to store in {$item->cacheId}");
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
        apcu_delete($key);
    }

    /**
     * @param string $cacheId
     * @return void
     */
    function flush($cacheId)
    {
        $fullPrefix = \Cachet\Helper::formatKey([$this->prefix, $cacheId]);
        $iter = new APCUIterator(
            "~^".preg_quote($fullPrefix, "~")."~",
            APCU_ITER_VALUE,
            $this->iteratorChunkSize
        );
        apcu_delete($iter);
    }

    /**
     * @param string $cacheId
     * @return \Iterator
     */
    function keys($cacheId)
    {
        $fullPrefix = \Cachet\Helper::formatKey([$this->prefix, $cacheId]);
        $keyRegex = "~^".preg_quote($fullPrefix, "~")."~";
        $iter = new APCUIterator($keyRegex, APCU_ITER_KEY, $this->iteratorChunkSize);
        $prefixLen = strlen($fullPrefix) + 1;
        return new \Cachet\Util\MapIterator($iter, function($item) use ($prefixLen) {
            return substr($item['key'], $prefixLen);
        });
    }

    /**
     * @param string $cacheId
     * @return \Iterator
     */
    function items($cacheId)
    {
        $fullPrefix = \Cachet\Helper::formatKey([$this->prefix, $cacheId]);
        $keyRegex = "~^".preg_quote($fullPrefix, "~")."~";
        $iter = new APCUIterator($keyRegex, APCU_ITER_VALUE, $this->iteratorChunkSize);
        return new \Cachet\Util\MapIterator($iter, function($item) {
            return $item['value'];
        });
    }
}
