<?php
namespace Cachet\Backend\Iteration;

class BackendFetcher implements \Iterator
{
    private $current;
    private $key;
    private $count;
    
    public function __construct($backend, $cacheId, $keys)
    {
        $this->backend = $backend;
        $this->cacheId = $cacheId;
        
        if (is_array($keys))
            $keys = new \ArrayIterator($keys);
        
        $this->keys = $keys;
        $this->count = count($this->keys);
    }
    
    public function current()
    {
        return $this->current;
    }
    
    public function key()
    {
        return $this->key;
    }
    
    public function next()
    {
        $this->current = null;
        $this->key = null;
    }
    
    public function rewind()
    {
        $this->current = null;
        $this->key = null;
        $this->keys->rewind();
    }
    
    public function valid()
    {
        if (!$this->current) {
            while ($this->keys->valid()) {
                $key = $this->keys->current();
                $this->keys->next();
                $item = $this->backend->get($this->cacheId, $key);
                if ($item) {
                    $this->current = $item;
                    $this->key = $item->key;
                    break;
                }
            }
        }
        
        return !!$this->current;
    }
}
