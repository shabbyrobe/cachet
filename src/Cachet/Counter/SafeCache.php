<?php
namespace Cachet\Counter;

class SafeCache implements \Cachet\Counter
{
    /** @var \Cachet\Cache */
    public $cache;

    /** @var \Cachet\Locker */
    public $locker;

    /** @var callable */
    public $addCallback;

    /** @var callable */
    public $subCallback;

    public function __construct(\Cachet\Cache $cache, \Cachet\Locker $locker)
    {
        $this->locker = $locker;
        $this->cache = $cache;

        if (extension_loaded('bcmath')) {
            $this->addCallback = 'bcadd';
            $this->subCallback = 'bcsub';
        }
        elseif (extension_loaded('gmp')) {
            $this->addCallback = function ($a, $b) {
                return gmp_strval(gmp_add($a, $b));
            };
            $this->subCallback = function ($a, $b) {
                return gmp_strval(gmp_sub($a, $b));
            };
        }
        else {
            throw new \RuntimeException("Neither bcmath nor gmp extensions present");
        }
    }

    /**
     * @param string $key
     * @return int
     */
    function value($key)
    {
        $formattedKey = \Cachet\Helper::formatKey(['counter', $key]);
        $value = $this->cache->get($formattedKey, $found) ?: 0;
        return $value;
    }

    /**
     * @param string $key
     * @param int $value
     * @return void
     */
    function set($key, $value)
    {
        $formattedKey = \Cachet\Helper::formatKey(['counter', $key]);
        $this->cache->set($formattedKey, $value);
    }

    /**
     * @param string $key
     * @param int $by
     * @return int
     */
    function increment($key, $by=1)
    {
        $addCallback = $this->addCallback;
        $this->locker->acquire($this->cache->id, $key);
        $value = $this->value($key); 
        $value = $addCallback($value, $by);
        $this->set($key, $value);
        $this->locker->release($this->cache->id, $key);
        return $value;
    }

    /**
     * @param string $key
     * @param int $by
     * @return int
     */
    function decrement($key, $by=1)
    {
        $subCallback = $this->subCallback;
        $this->locker->acquire($this->cache->id, $key);
        $value = $this->value($key); 
        $value = $subCallback($value, $by);
        $this->set($key, $value);
        $this->locker->release($this->cache->id, $key);
        return $value;
    }
}

