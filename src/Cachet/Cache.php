<?php
namespace Cachet;

use Cachet\Backend\Iterable;
use Cachet\Backend\IterationAdapter;

class Cache implements \ArrayAccess
{
    public $id;
    
    /**
     * @var Cachet\Backend
     */
    public $backend;

    /**
     * @var Cachet\Locker
     */
    public $locker;
    
    /**
     * Default dependency to use when none passed
     * @var Cachet\Dependency
     */
    public $dependency;
    
    /**
     * Allows dependencies to access unserializable services when validating
     */
    public $services = array();
    
    public function __construct($id, Backend $backend, Dependency $dependency=null)
    {
        $this->id = $id;
        $this->backend = $backend;
        $this->dependency = $dependency;
    }
    
    function get($key, &$found=null)
    {
        $item = $this->backend->get($this->id, $key);
        $value = null;
        $found = false;
        
        if ($item) {
            $found = $this->validateItem($item);
            if ($found)
                $value = $item->value;
        }
        return $value;
    }
    
    function has($key)
    {
        $item = $this->get($key, $found);
        return $found;
    }
    
    function set($key, $value, $dependencyOrDuration=null)
    {
        $dependency = null;
        if (is_numeric($dependencyOrDuration))
            $dependency = new Dependency\TTL($dependencyOrDuration);
        elseif ($dependencyOrDuration != null)
            $dependency = $dependencyOrDuration;
        
        if (!$dependency)
            $dependency = $this->dependency;
        
        $item = new Item($this->id, $key, $value, $dependency);
        if ($dependency) {
            if (!$dependency instanceof Dependency)
                throw new \InvalidArgumentException();
            
            $dependency->init($this, $item);
        }
        
        return $this->backend->set($item);
    }
    
    function delete($key)
    {
        return $this->backend->delete($this->id, $key);
    }
    
    function flush()
    {
        return $this->backend->flush($this->id);
    }
 
    private function wrapArgs($argv)
    {
        $argc = count($argv);
        $dependency = null;
        if ($argc == 2)
            list ($key, $callback, $dependency) = [$argv[0], $argv[1], null];
        elseif ($argc == 3)
            list ($key, $callback, $dependency) = [$argv[0], $argv[2], $argv[1]];
        else
            throw new \InvalidArgumentException();

        if (!is_callable($callback))
            throw new \InvalidArgumentException();

        return [$key, $callback, $dependency];
    }
    
    function wrap($key, $callback)
    {
        list ($key, $callback, $dependency) = $this->wrapArgs(func_get_args());
        $found = false;
        $data = $this->get($key, $found);
        if (!$found) {
            $data = $callback();
            $this->set($key, $data, $dependency);
        }
        return $data;
    }
    
    function blocking($key, $callback)
    {
        if (!$this->locker)
            throw new \UnexpectedValueException();

        list ($key, $callback, $dependency) = $this->wrapArgs(func_get_args());
        $found = false;
        $data = $this->get($key, $found);
        if (!$found) {
            $this->locker->acquire($this, $key);
            try {
                $data = $this->get($key, $found);
                if (!$found) {
                    $data = $callback();
                    $this->set($key, $data, $dependency);
                }
            }
            finally {
                $this->locker->release($this, $key);
            }
        }
        return $data;
    }
    
    function nonblocking($key, $callback)
    {
        if (!$this->locker)
            throw new \UnexpectedValueException();

        list ($key, $callback, $dependency) = $this->wrapArgs(func_get_args());

        $item = $this->backend->get($this->id, $key);
        $stale = $item && !$this->validateItem($item);

        if (!$item || $stale) {
            $this->locker->acquire($this, $key);
            try {
                $item = $this->backend->get($this->id, $key);
                if (!$item) {
                    $data = $callback();
                    $this->set($key, $data, $dependency);
                    $item = $this->backend->get($this->id, $key);
                }
            }
            finally {
                $this->locker->release($this, $key);
            }
        }
        return $item ? $item->value : null;
    }
    
    function removeInvalid()
    {
        $this->ensureIterable();
        foreach ($this->backend->items($this->id) as $key=>$item) {
            $valid = $this->validateItem($item);
            if (!$valid) {
                $this->delete($item->key);
            }
        }
    }
    
    function keys()
    {
        $this->ensureIterable();
        foreach ($this->items() as $item) {
            yield $item->key;
        }
    }
    
    function values()
    {
        $this->ensureIterable();
        foreach ($this->items() as $item) {
            yield $item->key => $item->value;
        }
    }
    
    function items($all=false)
    {
        $this->ensureIterable();
        foreach ($this->backend->items($this->id) as $key=>$item) {
            $valid = $all || $this->validateItem($item);
            if ($valid) {
                yield $item->key => $item;
            }
        }
    }
    
    private function validateItem($item)
    {
        if (!$item instanceof Item)
            return false;
        
        $valid = false;
        
        $dependency = $item->dependency ?: $this->dependency;
        if (!$dependency || ($dependency instanceof Dependency && $dependency->valid($this, $item))) {
            $valid = true;
        }
        
        return $valid;
    }

    private function ensureIterable()
    {   
        if (!$this->backend instanceof Iterable)
            throw new \RuntimeException("This backend does not support iteration");
        elseif ($this->backend instanceof IterationAdapter && !$this->backend->iterable())
            throw new \RuntimeException("This backend supports iteration, but only with a secondary key backend. Please call setKeyBackend() and rebuild your cache.");
    }
    
    function offsetGet($key)
    {
        return $this->get($key, $value);
    }
    
    function offsetSet($key, $value)
    {
        return $this->set($key, $value);
    }
    
    function offsetExists($key)
    {
        return $this->has($key);
    }
    
    function offsetUnset($key)
    {
        $this->delete($key);
    }
}
