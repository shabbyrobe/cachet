<?php
namespace Cachet\Locker;

class Semaphore extends \Cachet\Locker
{
    private $sem = [];

    public function __construct(callable $keyHasher=null)
    {
        if (!function_exists('sem_get'))
            throw new \RuntimeException("PHP must be compiled with --enable-sysvsem");

        parent::__construct($keyHasher);
    }

    function acquire($cacheId, $key, $block=true)
    {
        if (!$block)
            throw new \InvalidArgumentException("Non-blocking lock not supported by Sempahore");

        $id = $this->getId($cacheId, $key);
        $this->sem[$id] = $sem = sem_get($id);
        sem_acquire($sem);
    }

    function release($cacheId, $key)
    {
        $id = $this->getId($cacheId, $key);
        $sem = $this->sem[$id];
        unset($this->sem[$id]);
        sem_release($sem);
    }

    function shutdown()
    {
        foreach ($this->sem as $id=>$sem) {
            sem_release($sem);
            unset($this->sem[$id]);
        }

        if ($this->sem)
            throw new \UnexpectedValueException();
    }

    private function getId($cacheId, $key)
    {
        $id = $this->getLockKey($cacheId, $key);
        if (!is_int($id) || $id > PHP_INT_MAX || $id < ~PHP_INT_MAX)
            $id = \Cachet\Helper::hashToInt32($id);
        return $id;
    }
}
