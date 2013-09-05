<?php 
namespace Cachet;

interface Dependency
{
    /**
     * WARNING: Do not save the value of $cache into the dependency
     */
	function valid(Cache $cache, Item $item);
	
	/**
	 * Initialise the dependency for the cache item
     * 
	 * This is called by Cache immediately before the item is passed to the
     * backend for storage.
     * 
     * WARNING: Do not save the value of $cache into the dependency
	 */
	function init(Cache $cache, Item $item);
}
