<?php
namespace Cachet\Backend\Iteration;

interface Iterable
{
    function keys($cacheId);
    function items($cacheId);
}

/*
trait Iterable implements \Iterator
{
    abstract function keys();
    abstract function items();
    
    public function current()
    {
    }
    
    public function key()
    {
    }
    
    public function next()
    {
    }
    
    public function rewind()
    {
    }
    
    public function valid()
    {
    }
}
*/
