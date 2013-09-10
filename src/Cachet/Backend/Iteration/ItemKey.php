<?php
namespace Cachet\Backend\Iteration;

trait ItemKey
{
    private $index = 0;
    private $mode;
    
    protected abstract function getCurrent();
    
    public function setMode($mode)
    {
        if ($mode != 'key' && $mode != 'item')
            throw new \InvalidArgumentException();
        $this->mode = $mode;
    }
    
    public function key()
    {
        if ($this->mode == 'key') {
            return $this->index;
        }
        else {
            $value = $this->current();
            if ($value) {
                return $value->key;
            }
        }
    }
    
    public function current()
    {
        $current = $this->getCurrent();
        if (!$current)
            return $current;
        
        if ($this->mode == 'key')
            return $current->key;
        else
            return $current;
    }
    
    public function next()
    {
        if ($this->mode == 'key')
            $this->index++;
        return parent::next();
    }
}
