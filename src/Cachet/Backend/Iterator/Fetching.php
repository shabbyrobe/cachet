<?php
namespace Cachet\Backend\Iterator;

use Cachet\Backend;

class Fetching extends \IteratorIterator
{
    function __construct($cacheId, $keyIterator, Backend $backend)
    {
        if (is_array($keyIterator))
            $keyIterator = new \ArrayIterator($keyIterator);
    
        if (!$keyIterator instanceof \Iterator)
            throw new \InvalidArgumentException();

        parent::__construct($keyIterator);
        $this->cacheId = $cacheId;
        $this->backend = $backend;
    }

    function key()
    {
        return parent::current();
    }

    function current()
    {
        return $this->backend->get($cacheId, parent::current()); 
    }
}
