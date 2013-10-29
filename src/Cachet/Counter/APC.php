<?php
namespace Cachet\Counter;

class APC implements \Cachet\Counter
{
    public $prefix;
    public $counterTTL;

    function set($key, $value)
    {
        if (!is_int($value))
            throw new \InvalidArgumentException();

        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        if (!apc_store($formattedKey, $value, $this->counterTTL)) {
            throw new \UnexpectedValueException("APC could not set the value at $formattedKey");
        }
    }

    function value($key)
    {
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        $value = apc_fetch($formattedKey);
        if (!is_numeric($value) && !is_bool($value) && $value !== null) {
            $type = \Cachet\Helper::getType($value);
            throw new \UnexpectedValueException(
                "APC counter expected numeric value, found $type at key $key"
            );
        }
        return $value ?: 0;
    }

    function increment($key, $by=1)
    {
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        $value = apc_inc($formattedKey, $by, $success);
        if (!$success) {
            $success = apc_store($formattedKey, $by, $this->counterTTL);
            if (!$success)
                throw new \UnexpectedValueException("APC could not increment the key at $formattedKey");
        }

        return $value;
    }

    function decrement($key, $by=1)
    {
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        $value = apc_dec($formattedKey, $by, $success);
        if (!$success) {
            $success = apc_store($formattedKey, -$by);
            if (!$success)
                throw new \UnexpectedValueException("APC could not increment the key at $formattedKey");
        }

        return $value;
    }
}
