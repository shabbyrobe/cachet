<?php
namespace Cachet\Backend\Iteration;

use Cachet\Item;
use Cachet\Backend;

/**
 * If your cache backend doesn't support iteration, this allows a second
 * backend that does support iteration to be used for the keys.
 *
 * This can slow down cache set and delete operations significantly, but has
 * no bearing on get operations.
 */
abstract class Adapter implements Backend, Iterable
{
    protected $keyBackend;
    
    public function iterable()
    {
        return $this->keyBackend;
    }
    
    public function setKeyBackend(Iterable $backend)
    {
        if ($backend instanceof Adapter)
            throw new \InvalidArgumentException("Key backend must not be an iteration adapter backend");
        
        $this->keyBackend = $backend;
    }
    
    function keys($cacheId)
    {
        return new Key($this->keyBackend->items($cacheId));
    }
    
    function items($cacheId)
    {
        $keys = $this->keys($cacheId);
        return new BackendFetcher($this, $cacheId, $keys);
    }
    
    function set(Item $item)
    {
        $this->setInStore($item);
        
        if ($this->keyBackend) {
            $keyItem = clone $item;
            $keyItem->value = $item->key;
            
            // keys should only be considered invalid when there is no corresponding value
            $keyItem->dependency = null;
            
            $this->keyBackend->set($keyItem);
        }
    }

    function delete($cacheId, $key)
    {
        if ($this->keyBackend)
            $this->keyBackend->delete($cacheId, $key);
        
        $this->deleteFromStore($cacheId, $key);
    }
    
    function flush($cacheId)
    {
        // the keys in the backend will probably be needed to flush the store.
        // this is not ideal - a better solution is needed.
        $this->flushStore($cacheId);
        
        if ($this->keyBackend) {
            $this->keyBackend->flush($cacheId);
        }
    }
            
    protected abstract function setInStore(Item $item);
    protected abstract function deleteFromStore($cacheId, $key);
    protected abstract function flushStore($cacheId);
}
