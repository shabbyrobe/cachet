<?php
namespace Cachet\Backend;

use Cachet\Backend;
use Cachet\Item;

class Cascading implements Backend, Iteration\Iterable
{
    private $backends;
    private $reverseBackends;
    
    public function __construct(array $backends)
    {
        if (count($backends) < 2)
            throw new \InvalidArgumentException("No point cascading with less than two backends!");
        
        $this->backends = $backends;
        $this->reverseBackends = array_reverse($backends);
    }
    
    function get($cacheId, $key)
    {
        $item = null;
        $missedBackends = array();
        foreach ($this->backends as $backend) {
            $item = $backend->get($cacheId, $key);
            if ($item)
                break;
            else
                $missedBackends[] = $backend;
        }
        
        if ($item) {
            foreach (array_reverse($missedBackends) as $missedBackend)
                $missedBackend->set($item);
        }
        
        return $item;
    }
    
    function set(Item $item)
    {
        foreach ($this->reverseBackends as $backend) {
            $backend->set($item);
        }
    }
    
    function delete($cacheId, $key)
    {
        foreach ($this->reverseBackends as $backend) {
            $backend->delete($cacheId, $key);
        }
    }
    
    function flush($cacheId)
    {
        foreach ($this->reverseBackends as $backend) {
            $backend->flush($cacheId);
        }
    }
    
    function keys($cacheId)
    {
        foreach ($this->reverseBackends[0]->keys() as $key)
            yield $key;
    }
    
    function items($cacheId)
    {
        foreach ($this->reverseBackends[0]->items() as $item)
            yield $item;
    }
}
