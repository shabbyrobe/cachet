<?php
namespace Cachet\Util;

class MapIterator implements \Iterator
{
    private $current;
    private $index;
    private $key;
    private $inner;
    private $map;

    public function __construct($iterable, callable $map)
    {
        if (is_array($iterable))
            $iterable = new \ArrayIterator($iterable);

        $this->inner = $iterable;
        $this->map = $map;
    }

    public function valid()
    {
        $valid = $this->inner->valid();
        if ($valid && $this->key === null) {
            $current = $this->inner->current();
            $map = $this->map;
            $key = null;
            $this->current = $map($current, $key);

            $this->key = $key === null ? $this->index++ : $key;
        }
        return $valid;
    }

    public function next()
    {
        $this->inner->next();
        $this->current = null;
        $this->key = null;
    }

    public function rewind()
    {
        $this->inner->rewind();
        $this->current = null;
        $this->key = null;
        $this->index = 0;
    }

    public function current()
    {
        return $this->current;
    }
    
    public function key()
    {
        return $this->key;
    }
}

