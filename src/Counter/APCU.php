<?php
namespace Cachet\Counter;

/**
 * Increments and decrements are not guaranteed to be fully atomic without a locker.
 * They are atomic if the value is already set, but if it isn't there's no protection
 * without a locker.
 */
class APCU implements \Cachet\Counter
{
    public $prefix;
    public $counterTTL;
    public $locker;

    private $cacheId = 'counter';

    function __construct($prefix=null, \Cachet\Locker $locker=null, $counterTTL=null)
    {
        $this->counterTTL = $counterTTL;
        $this->prefix = $prefix;
        $this->locker = $locker;
    }

    /**
     * @param string $key
     * @param int $value
     * @return void
     */
    function set($key, $value)
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException();
        }
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, $this->cacheId, $key]);
        if (!apcu_store($formattedKey, $value, $this->counterTTL)) {
            throw new \UnexpectedValueException("APC could not set the value at $formattedKey");
        }
    }

    /**
     * @param string $key
     * @return int
     */
    function value($key)
    {
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, $this->cacheId, $key]);
        $value = apcu_fetch($formattedKey);
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
        $formattedKey = \Cachet\Helper::formatKey([$this->prefix, $this->cacheId, $key]);
        $value = $method($formattedKey, abs($by), $success);
        if (!$success) {
            $check = false;
            if ($this->locker) {
                $this->locker->acquire($this->cacheId, $key);
                $check = apcu_fetch($formattedKey);
                if ($check !== false)
                    $value = $method($formattedKey, abs($by), $success);
            }
            if ($check === false) {
                $this->set($key, $by);
                $value = $by;
            }
            if ($this->locker) {
                $this->locker->release($this->cacheId, $key);
            }
        }
        return $value;
    }

    /**
     * @param string $key
     * @param int $by
     * @return int
     */
    function increment($key, $by=1)
    {
        return $this->change('apcu_inc', $key, $by);
    }

    /**
     * @param string $key
     * @param int $by
     * @return int
     */
    function decrement($key, $by=1)
    {
        return $this->change('apcu_dec', $key, -$by);
    }
}
