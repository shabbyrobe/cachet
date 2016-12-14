<?php
namespace Cachet\Counter;

class XCache implements \Cachet\Counter
{
    public $counterTTL = null;
    public $prefix;

    function __construct($prefix=null, $counterTTL=null)
    {
        $this->counterTTL = $counterTTL;
        $this->prefix = $prefix;
    }

    /**
     * @param string $key
     * @return int
     */
    function value($key)
    {
        $formatted = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        $value = xcache_get($formatted);
        if (!is_numeric($value) && !is_bool($value) && $value !== null) {
            $type = \Cachet\Helper::getType($value);
            throw new \UnexpectedValueException(
                "XCache counter expected numeric value, found $type at key $key"
            );
        }
        return (int) $value ?: 0;
    }

    /**
     * @param string $key
     * @param int $value
     * @return void
     */
    function set($key, $value)
    {
        $formatted = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        xcache_set($formatted, $value, $this->counterTTL);
    }

    /**
     * @param string $key
     * @param int $by
     * @return int
     */
    function increment($key, $by=1)
    {
        $formatted = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        $value = xcache_inc($formatted, $by, $this->counterTTL);
        return $value;
    }

    /**
     * @param string $key
     * @param int $by
     * @return int
     */
    function decrement($key, $by=1)
    {
        $formatted = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        $value = xcache_dec($formatted, $by, $this->counterTTL);
        return $value;
    }
}
