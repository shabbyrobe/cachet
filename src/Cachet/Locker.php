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
     * @param string $cacheId
     * @param string $key
     * @return bool Whether or not the lock was acquired
     */
    abstract function acquire($cacheId, $key, $block=true);

    /**
     * @param string $cacheId
     * @param string $key
     */
    abstract function release($cacheId, $key);

    abstract function shutdown();

    /**
     * @param string $cacheId
     * @param string $key
     * @return string
     */
    function getLockKey($cacheId, $key)
    {
        if ($this->keyHasher) {
            return call_user_func($this->keyHasher, $cacheId, $key);
        } else {
            return "{$cacheId}/$key";
        }
    }

    function __destruct()
    {
        $this->shutdown();
    }
}
