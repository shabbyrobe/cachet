<?php
namespace Cachet\Dependency;

use Cachet\Cache;
use Cachet\Dependency;
use Cachet\Item;

class Permanent implements Dependency
{
    function init(Cache $cache, Item $item)
    {}
    
    function valid(Cache $cache, Item $item)
    {
        return true;
    }
}
