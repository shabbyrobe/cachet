<?php
namespace Cachet;

use Cachet\Backend\Iterator;
use Cachet\Backend\IterationAdapter;

class Cache implements \ArrayAccess
{
    /** @var string */
    public $id;

    /** @var Backend */
    public $backend;

    /** @var Locker */
    public $locker;

    /**
     * Default dependency to use when none passed
     * @var Dependency
     */
    public $dependency;

    /**
     * Allows dependencies to access unserializable services when validating
     */
    public $services = array();

    private $expiringBackend;

    /**
     * @param string $id
     */
    public function __construct($id, Backend $backend, Dependency $dependency=null)
    {
        $this->id = $id;
        $this->backend = $backend;
        $this->dependency = $dependency;
    }

    /**
     * @param string $key
     */
    function get($key, &$found=null)
    {
        $item = $this->backend->get($this->id, $key);
        $value = null;
        $found = false;

        if ($item) {
            $found = $this->validateItem($item);
            if ($found) {
                $value = $item->value;
            }
        }
        return $value;
    }

    /**
     * @param string $key
     * @return bool
     */
    function has($key)
    {
        $item = $this->get($key, $found);
        return $found;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    function set($key, $value, $dependencyOrDuration=null)
    {
        $dependency = null;
        if (is_numeric($dependencyOrDuration)) {
            $dependency = new Dependency\TTL($dependencyOrDuration);
        } elseif ($dependencyOrDuration != null) {
            $dependency = $dependencyOrDuration;
        }

        if (!$dependency) {
            $dependency = $this->dependency;
        }

        $item = new Item($this->id, $key, $value, $dependency);
        if ($dependency) {
            if (!$dependency instanceof Dependency) {
                throw new \InvalidArgumentException();
            }
            $dependency->init($this, $item);
        }

        return $this->backend->set($item);
    }

    /**
     * @param string $key
     * @return void
     */
    function delete($key)
    {
        return $this->backend->delete($this->id, $key);
    }

    /**
     * Flush all entries out of the cache, leaving it pristine and clear.
     * @return void
     */
    function flush()
    {
        return $this->backend->flush($this->id);
    }

    /**
     * @param Item $item
     */
    function validateItem($item)
    {
        if (!$item instanceof Item) {
            return false;
        }

        $valid = false;
        $dependency = $item->dependency ?: $this->dependency;
        if (!$dependency || ($dependency instanceof Dependency && $dependency->valid($this, $item))) {
            $valid = true;
        }
        return $valid;
    }

    function removeInvalid()
    {
        $this->ensureIterator();
        foreach ($this->backend->items($this->id) as $key=>$item) {
            $valid = $this->validateItem($item);
            if (!$valid) {
                $this->delete($item->key);
            }
        }
    }

    /**
     * @return \Iterator
     */
    function keys()
    {
        $this->ensureIterator();
        return new Util\MapIterator($this->items(), function($item) {
            return $item->key;
        });
    }

    /**
     * @return \Iterator
     */
    function values()
    {
        $this->ensureIterator();
        return new Util\MapIterator(
            $this->items(),
            function($item, &$key) {
                $key = $item->key;
                return $item->value;
            }
        );
    }

    /**
     * @return \Iterator
     */
    function items($all=false)
    {
        $this->ensureIterator();
        $items = $this->backend->items($this->id);

        $iter = new Util\MapIterator($items, function($item, &$key) use ($all) {
            $valid = $all || $this->validateItem($item);
            $key = $item->key;
            return $valid ? $item : null;
        });

        return new \CallbackFilterIterator($iter, function($item) {
            return $item instanceof Item;
        });
    }

    private function ensureIterator()
    {
        if (!$this->backend instanceof Iterator) {
            throw new \RuntimeException("This backend does not support iteration");
        }
        elseif ($this->backend instanceof IterationAdapter && !$this->backend->iterable()) {
            throw new \RuntimeException(
                "This backend supports iteration, but only with a secondary key backend. ".
                "Please call setKeyBackend() and rebuild your cache."
            );
        }
    }

    private function strategyArgs($argv)
    {
        $argc = count($argv);
        $dependency = null;
        if (is_callable($argv[1])) {
            list ($key, $callback, $dependency) = [$argv[0], $argv[1], null];
        } elseif (is_callable($argv[2])) {
            list ($key, $callback, $dependency) = [$argv[0], $argv[2], $argv[1]];
        } else {
            throw new \InvalidArgumentException();
        }
        return [$key, $callback, $dependency];
    }

    /**
     * @param string $key
     * @param callable|Dependency $dependencyOrCallback
     * @return mixed
     */
    function wrap($key, $dependencyOrCallback, $callback=null, &$result=null)
    {
        list ($key, $callback, $dependency) = $this->strategyArgs(func_get_args());
        $found = false;
        $data = $this->get($key, $found);

        $result = 'found';
        if (!$found) {
            $result = 'set';
            $data = call_user_func($callback);
            $this->set($key, $data, $dependency);
        }
        return $data;
    }

    /**
     * @param string $key
     * @param callable|Dependency $dependencyOrCallback
     * @return mixed
     */
    function blocking($key, $dependencyOrCallback, $callback=null, &$result=null)
    {
        if (!$this->locker) {
            throw new \UnexpectedValueException("Must set a locker to use a locking strategy");
        }

        list ($key, $callback, $dependency) = $this->strategyArgs(func_get_args());
        $found = false;
        $result = 'found';
        $data = $this->get($key, $found);
        if (!$found) {
            $this->locker->acquire($this->id, $key);
            try {
                $data = $this->get($key, $found);
                if (!$found) {
                    $result = 'set';
                    $data = call_user_func($callback);
                    $this->set($key, $data, $dependency);
                }
                else {
                    $result = 'foundInLock';
                }
            }
            catch (\Exception $ex) {
                $this->locker->release($this->id, $key);
                $msg = $ex->getMessage();
                throw new \RuntimeException("Blocking strategy failed: $msg", 0, $ex);
            }
            $this->locker->release($this->id, $key);
        }
        return $data;
    }

    /**
     * If the locker is locked, this will return a stale item if one
     * is available, or block until an item becomes available.
     *
     * @param string $key
     * @param callable|Dependency $dependencyOrCallback
     * @return mixed
     */
    function safeNonblocking($key, $dependencyOrCallback, $callback=null, &$result=null)
    {
        if (!$this->locker) {
            throw new \UnexpectedValueException("Must set locker to use locking strategy");
        }

        $this->ensureBackendExpirations(!'enabled');

        list ($key, $callback, $dependency) = $this->strategyArgs(func_get_args());

        $item = $this->backend->get($this->id, $key);
        $stale = $item && !$this->validateItem($item);

        $result = 'found';
        if (!$item || $stale) {
            if ($this->locker->acquire($this->id, $key, !'block')) {
                try {
                    $item = $this->backend->get($this->id, $key);
                    if (!$item || !$this->validateItem($item)) {
                        $result = 'set';
                        $data = call_user_func($callback);
                        $this->set($key, $data, $dependency);
                        $item = $this->backend->get($this->id, $key);
                    }
                    else {
                        $result = 'foundInLock';
                    }
                }
                catch (\Exception $ex) {
                    $this->locker->release($this->id, $key);
                    $msg = $ex->getMessage();
                    throw new \RuntimeException("Safe nonblocking strategy failed: $msg", 0, $ex);
                }
                $this->locker->release($this->id, $key);
            }
            elseif (!$item) {
                // if another process has the lock and we have no stale item to return,
                // block until one becomes available.
                $this->locker->acquire($this->id, $key);
                try {
                    $item = $this->backend->get($this->id, $key);
                }
                catch (\Exception $ex) {
                    $this->locker->release($this->id, $key);
                    $msg = $ex->getMessage();
                    throw new \RuntimeException("Safe nonblocking strategy failed: $msg", 0, $ex);
                }
                $this->locker->release($this->id, $key);
                $result = 'foundWait';
            }
            else {
                $result = 'foundStale';
            }
        }
        return $item ? $item->value : null;
    }

    /**
     * If the locker is locked, this will return a stale item if one
     * is available, or null if one is not.
     *
     * @param string $key
     * @param callable|Dependency $dependencyOrCallback
     * @return mixed
     */
    function unsafeNonBlocking($key, $dependency, $callback=null, &$found=null, &$result=null)
    {
        if (!$this->locker) {
            throw new \UnexpectedValueException("Must set a locker to use a locking strategy");
        }

        $this->ensureBackendExpirations(!'enabled');

        list ($key, $callback, $dependency) = $this->strategyArgs(func_get_args());

        $item = $this->backend->get($this->id, $key);
        $stale = $item && !$this->validateItem($item);

        $result = 'found';
        if (!$item || $stale) {
            if ($this->locker->acquire($this->id, $key, !'block')) {
                try {
                    $item = $this->backend->get($this->id, $key);
                    if (!$item) {
                        $data = call_user_func($callback);
                        $this->set($key, $data, $dependency);
                        $item = $this->backend->get($this->id, $key);
                        $result = 'set';
                    }
                    else {
                        $result = 'foundInLock';
                    }
                }
                catch (\Exception $ex) {
                    $this->locker->release($this->id, $key);
                    $msg = $ex->getMessage();
                    throw new \RuntimeException("Unsafe nonblocking strategy failed: $msg", 0, $ex);
                }
                $this->locker->release($this->id, $key);
            }
            else {
                $result = $item ? 'foundStale' : 'notFound';
            }
        }

        $found = $item == true;
        return $item ? $item->value : null;
    }

    private function ensureBackendExpirations($status)
    {
        if ($this->expiringBackend === null) {
            $this->expiringBackend = property_exists($this->backend, 'useBackendExpirations');
        }

        if ($this->expiringBackend) {
            if ($this->backend->useBackendExpirations != $status) {
                throw new \RuntimeException(
                    "You must set useBackendExpirations to ".($status ? 'true' : 'false')." ".
                    "to use this caching strategy"
                );
            }
        }
    }

    /**
     * @param string $key
     */
    function offsetGet($key)
    {
        return $this->get($key, $value);
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    function offsetSet($key, $value)
    {
        return $this->set($key, $value);
    }

    /**
     * @param string $key
     */
    function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * @param string $key
     */
    function offsetUnset($key)
    {
        $this->delete($key);
    }
}
