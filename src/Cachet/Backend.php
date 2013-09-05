<?php
namespace Cachet;

interface Backend
{
    /**
     * Retrieves a cache item from the cache. This does not check
     * whether the item has expired - this is up to the outer cache.
     * @param string key to look up
     * @return Cachet\Item
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
     * @return void
     */
    function delete($cacheId, $key);
    
    function flush($cacheId);
}
