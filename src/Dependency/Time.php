<?php
namespace Cachet\Dependency;

use Cachet\Cache;
use Cachet\Dependency;
use Cachet\Item;

class Time implements Dependency
{
    public $time;

    function __construct($time)
    {
        $this->time = $time;
    }

    function init(Cache $cache, Item $item)
    {}

    function valid(Cache $cache, Item $item)
    {
        return $this->time > $this->getCurrentTime();
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
