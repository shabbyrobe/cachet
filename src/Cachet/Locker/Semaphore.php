<?php
namespace Cachet\Locker;

use Cachet\Cache;

class Semaphore extends \Cachet\Locker
{
    private $sem = [];

    function acquire(Cache $cache, $key, $block=true)
    {
        if (!$block)
            throw new \InvalidArgumentException("Non-blocking lock not supported by Sempahore");
         
        $id = $this->getId($cache, $key);
        $this->sem[$id] = $sem = sem_get($id);
        sem_acquire($sem);
    }

    function release(Cache $cache, $key)
    {
        $id = $this->getId($cache, $key);
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
    
    private function getId($cache, $key)
    {
        $id = $this->getLockKey($cache, $key);
        if (!is_int($id) || $id > PHP_INT_MAX || $id < ~PHP_INT_MAX)
            $id = \Cachet\Util\Hash::mdhack($id);
        return $id;
    }
}

