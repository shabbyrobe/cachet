<?php
namespace Cachet\Counter;

class XCache implements \Cachet\Counter
{
    public $counterTTL = null;

    function increment($key, $by=1)
    {
        $formatted = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        $value = xcache_inc($formatted, $by, $this->counterTTL);
        return $value;
    }

    function decrement($key, $by=1)
    {
        $formatted = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        $value = xcache_dec($formatted, $by, $this->counterTTL);
        return $value;
    }
}
