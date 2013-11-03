<?php
namespace Cachet;

abstract class Locker
{
    public $keyHasher = null;

    public function __construct(callable $keyHasher=null)
    {
        $this->keyHasher = $keyHasher;
    }

    /**
     * @return bool Whether or not the lock was acquired
     */
    abstract function acquire($cacheId, $key, $block=true);

    abstract function release($cacheId, $key);

    abstract function shutdown();

    function getLockKey($cacheId, $key)
    {
        if ($this->keyHasher)
            return call_user_func($this->keyHasher, $cacheId, $key);
        else
            return "$cacheId/$key";
    }

    function __destruct()
    {
        $this->shutdown();
    }
}

