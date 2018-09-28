<?php
namespace Cachet\Util;

class ReindexingIterator extends \IteratorIterator
{
    private $index;

    function key()
    {
        return $this->index;
    }

    function rewind()
    {
        $this->index = 0;
        return parent::rewind();
    }

    function next()
    {
        ++$this->index;
        return parent::next();
    }
}

