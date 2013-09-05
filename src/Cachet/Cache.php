<?php
namespace Cachet;

class Cache
{
    public $id;
    
    /**
     * @var Cachet\Backend
     */
    public $backend;
    
    /**
     * Default dependency to use when none passed
     * @var Cachet\Dependency
     */
    public $dependency;
    
    /**
     * Allows dependencies to access unserializable services when validating
     */
    public $services = array();
    
    public $deleteInvalid = true;
    
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
            if (!$item instanceof Item) {
                if ($this->deleteInvalid)
                    $this->delete($key);
            }
            else {
                $dependency = $item->dependency ?: $this->dependency;
                if (!$dependency || ($dependency instanceof Dependency && $dependency->valid($this, $item))) {
                    $value = $item->value;
                    $found = true;
                }
                else {
                    if ($this->deleteInvalid)
                        $this->delete($key);
                }
            }
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
    
    function wrap($key, $callback)
    {
        $args = func_get_args();
        $argc = func_num_args();
        
        $dependency = null;
        if ($argc == 2)
            list ($key, $callback) = $args;
        elseif ($argc == 3)
            list ($key, $dependency, $callback) = $args;
        else
            throw new \InvalidArgumentException();
        
        $found = false;
        $data = $this->get($key, $found);
        if (!$found) {
            $data = $callback();
            $this->set($key, $data, $dependency);
        }
        
        return $data;
    }
}
