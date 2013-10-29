<?php
namespace Cachet\Counter;

use Cachet\Connector;

class Memcache implements \Cachet\Counter
{
    const NOT_FOUND = 16;
    const INVALID_ARGUMENTS = 38;

    public $counterTTL = null;
    public $prefix;
    
    public function __construct($memcache, $prefix=null)
    {
        $this->connector = $memcache instanceof Connector\Memcache
            ? $memcache
            : new Connector\Memcache($memcache)
        ;
        $this->prefix = $prefix;
    }

    function set($key, $value)
    {
        $memcache = $this->connector->memcache ?: $this->connector->connect();
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        if (!$memcache->set($formattedKey, $value, $this->counterTTL))
            throw $this->memcacheException($memcache, "Could not set value");
    }

    function value($key)
    {
        $memcache = $this->connector->memcache ?: $this->connector->connect();
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        $value = $memcache->get($formattedKey);
        $this->ensureValidValue($memcache, $formattedKey, $value);
        return (int)$value ?: 0;
    }

    private function change($method, $key, $by=1)
    {
        $memcache = $this->connector->memcache ?: $this->connector->connect();
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        $value = $memcache->$method($formattedKey, $by);
        if ($value === false) {
            if ($memcache->getResultCode() == self::NOT_FOUND) {
                if ($method == 'decrement')
                    throw new \OutOfBoundsException("Memcache counters cannot be decremented past 0");
                $value = $by;
                $this->set($key, $value);
            }
            else {
                throw $this->memcacheException($memcache);
            }
        }
        else {
            $this->ensureValidValue($memcache, $formattedKey, $value);
        }
        return (int)$value ?: 0;
    }

    function increment($key, $by=1)
    {
        return $this->change('increment', $key, $by);
    }

    function decrement($key, $by=1)
    {
        return $this->change('decrement', $key, $by);
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
