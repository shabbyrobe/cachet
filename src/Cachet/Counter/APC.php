<?php
namespace Cachet\Counter;

class APC implements \Cachet\Counter
{
    public $prefix;
    public $counterTTL;
    public $locker;

    function __construct($prefix=null, \Cachet\Locker $locker=null, $counterTTL=null)
    {
        $this->counterTTL = $counterTTL;
        $this->prefix = $prefix;
        $this->locker = $locker;
    }

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

    private function change($method, $key, $by)
    {
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        $value = $method($formattedKey, abs($by), $success);
        if (!$success) {
            $value = $by; 
            $success = apc_store($formattedKey, $value, $this->counterTTL);
            if (!$success) {
                throw new \UnexpectedValueException(
                    "APC could not increment or decrement the key at $formattedKey"
                );
            }
        }
        return $value;
    }

    function increment($key, $by=1)
    {
        return $this->change('apc_inc', $key, $by);
    }

    function decrement($key, $by=1)
    {
        return $this->change('apc_dec', $key, -$by);
    }
}
