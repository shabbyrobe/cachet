<?php
namespace Cachet\Counter;

class APC implements \Cachet\Counter
{
    public $prefix;

    function set($key, $value)
    {
        if (!is_int($value))
            throw new \InvalidArgumentException();
        apc_store($key, $value);
    }

    function value($key)
    {
        $fullKey = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        $value = apc_fetch($fullKey);
        if (!is_int($value))
            throw new \UnexpectedValueException();
        return $value ?: 0;
    }

    function increment($key, $by=1)
    {
        $fullKey = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        $value = apc_inc($fullKey, $by, $success);
        if (!$success)
            throw new \UnexpectedValueException("APC could not increment the key at $fullKey");

        return $value;
    }

    function decrement($key, $by=1)
    {
        $fullKey = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        $value = apc_dec($fullKey, $by, $success);
        if (!$success)
            throw new \UnexpectedValueException("APC could not decrement the key at $fullKey");

        return $value;
    }
}
