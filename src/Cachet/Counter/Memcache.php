<?php
namespace Cachet\Counter;

class Memcache implements \Cachet\Counter
{
    public $counterTTL = null;
    
    public function __construct()
    {
        
    }

    function set($key, $value)
    {
        return $this->memcached->set($key, $value);
    }

    function value()
    {
        return $this->memcached->get($key);
    }

    function increment($key, $by=1)
    {
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        $value = $this->memcached->increment($formattedKey, $by, null, $this->counterTTL);
        return $value;
    }

    function decrement($key, $by=1)
    {
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        $value = $this->memcached->decrement($formattedKey, $by, null, $this->counterTTL);
        return $value;
    }
}
