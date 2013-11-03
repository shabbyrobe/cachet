<?php
namespace Cachet;

interface Backend
{
    /**
     * Informal interface property:
     * If the backend implementation supports expiring items and you wish
     * to make use of them when certain Dependencies are used, add this property.
     * For example, the PHPRedis backend uses the EXPIREAT command when a
     * Cachet\Dependency\Time instance is encountered, or the APC backend
     * sets the TTL if it encounters a Cachet\Dependency\TTL.
     * If this is false, you should not use these mechanisms.
     */
    // public $useBackendExpirations = true;

    /**
     * Retrieves an item from the backend. This does not check
     * whether the item has expired - this is up to the Cachet\Cache.
     * This must return null if the item is not found
     *
     * @param string $cacheId
     * @param string $key
     * @return Cachet\Item|null
     */
    function get($cacheId, $key);

    /**
     * @return void
     */
    function set(Item $item);

    /**
     * Delete value from the cache that corresponds to the key.
     * This method is not required to return any value indicating
     * success or failure.
     *
     * @param string $cacheId
     * @param string $key
     * @return void
     */
    function delete($cacheId, $key);

    /**
     * @param string $cacheId
     * @return void
     */
    function flush($cacheId);
}
