<?php
namespace Cachet\Connector;

class PHPRedis
{
    public $redis;

    private $redisInfo;

    function __construct($redis)
    {
        if ($redis instanceof \Redis) {
            $this->redis = $redis;
        }
        else {
            if (is_string($redis))
                $redis = ['host'=>$redis];

            if (isset($redis['db']))
                $redis['database'] = $redis['db'];

            $this->redisInfo = array_merge(
                [
                    'host'=>null,
                    'port'=>6379,
                    'timeout'=>10,
                    'persistent'=>false,
                    'database'=>null,
                ],
                $redis
            );
        }
    }

    function connect()
    {
        if ($this->redis)
            return $this->redis;

        if (!$this->redisInfo) {
            throw new \RuntimeException(
                "Can't reconnect to Redis when passing a Redis instance to the constructor"
            );
        }

        $this->redis = new \Redis();
        $info = $this->redisInfo;
        if (!$info['persistent'])
            $this->redis->connect($info['host'], $info['port'], $info['timeout']);
        else
            $this->redis->pconnect($info['host'], $info['port'], $info['timeout']);

        if ($info['database'])
            $this->redis->select($info['database']);

        return $this->redis;
    }

    function disconnect()
    {
        if ($this->redis) {
            $this->redis->close();
            $this->redis = null;
        }
    }
}
