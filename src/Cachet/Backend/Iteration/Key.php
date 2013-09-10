<?php
namespace Cachet\Backend\Iteration;

class Key extends \IteratorIterator
{
    private $index = 0;
    
    public function key()
    {
        return $this->index;
    }
    
    public function current()
    {
        $current = parent::current();
        if ($current)
            return $current->key;
    }
    
    public function next()
    {
        $this->index++;
        return parent::next();
    }
}
