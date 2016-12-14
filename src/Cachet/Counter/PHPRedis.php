<?php
namespace Cachet\Counter;

class PHPRedis implements \Cachet\Counter
{
    public $connector;
    public $prefix;

    public function __construct($redis, $prefix=null)
    {
        if (!$redis instanceof \Cachet\Connector\PHPRedis)
            $this->connector = new \Cachet\Connector\PHPRedis($redis);
        else
            $this->connector = $redis;

        $this->prefix = $prefix;
    }

    /**
     * @param string $key
     * @return int
     */
    function value($key)
    {
        $redis = $this->connector->redis ?: $this->connector->connect();
        $key = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        $value = $redis->get($key);
        if (!is_numeric($value) && !is_bool($value) && $value !== null) {
            $type = \Cachet\Helper::getType($value);
            throw new \UnexpectedValueException(
                "Redis counter expected numeric value, found $type at key $key"
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
        $redis = $this->connector->redis ?: $this->connector->connect();
        $key = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        $redis->set($key, $value);
    }

    /**
     * @param string $key
     * @param int $by
     * @return int
     */
    function increment($key, $by=1)
    {
        $redis = $this->connector->redis ?: $this->connector->connect();
        $key = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        return $redis->incrBy($key, $by);
    }

    /**
     * @param string $key
     * @param int $by
     * @return int
     */
    function decrement($key, $by=1)
    {
        $redis = $this->connector->redis ?: $this->connector->connect();
        $key = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        return $redis->decrBy($key, $by);
    }
}
