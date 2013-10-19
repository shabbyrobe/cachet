<?php
namespace Cachet\Counter;

class PHPRedis implements \Cachet\Counter
{
    public $connector;

    public function __construct($redis, $prefix=null)
    {
        if (!$redis instanceof \Cachet\Connector\PHPRedis)
            $this->connector = new \Cachet\Connector\PHPRedis($redis);
        else
            $this->connector = $redis;

        $this->prefix = $prefix;
    }

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
        return (int)$value ?: 0;
    }

    function increment($key, $by=1)
    {
        $redis = $this->connector->redis ?: $this->connector->connect();
        $key = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        return $redis->incrBy($key, $by);
    }

    function decrement($key, $by=1)
    {
        $redis = $this->connector->redis ?: $this->connector->connect();
        $key = \Cachet\Helper::formatKey([$this->prefix, 'counter', $key]);
        return $redis->decrBy($key, $by);
    }
}
