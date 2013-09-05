<?php
namespace Cachet\Dependency;

use Cachet\Cache;
use Cachet\Dependency;
use Cachet\Item;

class TTL implements Dependency
{
    public $ttlSeconds = 0;
    public $time;
    
    function __construct($ttlSeconds)
    {
        $this->ttlSeconds = $ttlSeconds;
    }
    
    function init(Cache $cache, Item $item)
    {
        $this->time = $item->timestamp + $this->ttlSeconds;
    }
    
    function valid(Cache $cache, Item $item)
    {
        if ($this->ttlSeconds == 0)
            return true;
        
        return ($this->getCurrentTime() - $item->timestamp) < $this->ttlSeconds;
    }
    
    function getExpiryTimestamp()
    {
        return $this->time;
    }
    
    /**
     * Included for mockability
     */
    function getCurrentTime()
    {
        return time();
    }
}
