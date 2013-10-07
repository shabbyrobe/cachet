<?php
namespace Cachet;

abstract class Locker
{
    public $keyHasher = null;

    public function __construct(callable $keyHasher=null)
    {
        $this->keyHasher = $keyHasher;
    }

    abstract function acquire(Cache $cache, $key);
    abstract function release(Cache $cache, $key);
    abstract function shutdown();

    function getLockKey(Cache $cache, $key)
    {
        if ($this->keyHasher)
            return call_user_func($this->keyHasher, $cache, $key);
        else
            return "$cache->id/$key";
    }

    function __destruct()
    {
        $this->shutdown();
    }
}

