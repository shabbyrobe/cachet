<?php
namespace Cachet\Counter;

use Cachet\Connector;

class Memcache implements \Cachet\Counter
{
    const NOT_FOUND = 16;
    const INVALID_ARGUMENTS = 38;

    public $counterTTL = null;
    public $prefix;

    private $cacheId = 'counter';
    
    public function __construct($memcache, $prefix=null, $locker=null)
    {
        $this->connector = $memcache instanceof Connector\Memcache
            ? $memcache
            : new Connector\Memcache($memcache)
        ;
        $this->prefix = $prefix;
        $this->locker = $locker;
    }

    function set($key, $value)
    {
        $memcache = $this->connector->memcache ?: $this->connector->connect();
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, $this->cacheId, $key]);
        if (!$memcache->set($formattedKey, $value, $this->counterTTL))
            throw $this->memcacheException($memcache, "Could not set value");
    }

    function value($key)
    {
        $memcache = $this->connector->memcache ?: $this->connector->connect();
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, $this->cacheId, $key]);
        $value = $memcache->get($formattedKey);
        $this->ensureValidValue($memcache, $formattedKey, $value);
        return (int)$value ?: 0;
    }

    private function change($method, $key, $by=1)
    {
        $memcache = $this->connector->memcache ?: $this->connector->connect();
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, $this->cacheId, $key]);
        $value = $memcache->$method($formattedKey, abs($by));
        if ($value === false) {
            if ($memcache->getResultCode() == self::NOT_FOUND) {
                if ($method == 'decrement')
                    throw new \OutOfBoundsException("Memcache counters cannot be decremented past 0");

                $value = $this->initialise($memcache, $method, $key, $formattedKey, $by);
            }
            else{
                throw $this->memcacheException($memcache);
            }
        }
        else {
            $this->ensureValidValue($memcache, $formattedKey, $value);
        }
        return (int)$value ?: 0;
    }

    private function initialise($memcache, $method, $key, $formattedKey, $by)
    {
        $check = false;
        if ($this->locker) {
            $this->locker->acquire($this->cacheId, $key);
            $check = $memcache->get($formattedKey);
            if ($check !== false)
                $value = $memcache->$method($formattedKey, abs($by));
        }

        if ($check === false) {
            $this->set($key, $by);
            $value = $by; 
        } 

        if ($this->locker)
            $this->locker->release($this->cacheId, $key);
        
        return $value;
    }

    function increment($key, $by=1)
    {
        return $this->change('increment', $key, $by);
    }

    function decrement($key, $by=1)
    {
        return $this->change('decrement', $key, -$by);
    }

    private function memcacheException($memcache, $text=null)
    {
        $code = $memcache->getResultCode();
        $message = $memcache->getResultMessage();
        return new \UnexpectedValueException( "Memcache failed with code $code, $message. $text");
    }
    
    private function ensureValidValue($memcache, $formattedKey, $value)
    {
        if (!is_numeric($value) && !is_bool($value) && $value !== null) {
            $type = \Cachet\Helper::getType($value);
            throw new \UnexpectedValueException(
                "Memcache counter expected numeric value, found $type at key $key"
            );
        }
    }
}
