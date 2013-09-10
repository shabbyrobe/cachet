<?php
namespace Cachet;

interface Backend
{
    /**
     * Retrieves an item from the backend. This does not check
     * whether the item has expired - this is up to the Cachet\Cache.
     * This must return null if the item is not found
     * @param string key to look up
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
     * @return void
     */
    function delete($cacheId, $key);
    
    /**
     * @return void
     */
    function flush($cacheId);
}
