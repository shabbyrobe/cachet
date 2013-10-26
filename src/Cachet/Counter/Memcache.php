<?php
namespace Cachet\Counter;

use Cachet\Connector;

class Memcache implements \Cachet\Counter
{
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
        return $memcache->set($key, $value);
    }

    function value($key)
    {
        $memcache = $this->connector->memcache ?: $this->connector->connect();
        $value = $memcache->get($key);
        if (!is_numeric($value) && !is_bool($value) && $value !== null) {
            $type = \Cachet\Helper::getType($value);
            throw new \UnexpectedValueException(
                "Redis counter expected numeric value, found $type at key $key"
            );
        }
        return (int)$value ?: 0;
    }

    function increment($key, $by=1)
    {
        $memcache = $this->connector->memcache ?: $this->connector->connect();
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        $value = $memcache->increment($formattedKey, $by, null, $this->counterTTL);
        return $value;
    }

    function decrement($key, $by=1)
    {
        $memcache = $this->connector->memcache ?: $this->connector->connect();
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        $value = $memcache->decrement($formattedKey, $by, null, $this->counterTTL);
        return $value;
    }
}
