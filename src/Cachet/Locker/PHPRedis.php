<?php
namespace Cachet\Locker;

class PHPRedis extends \Cachet\Locker
{
    private $servers;
    private $locks = [];

    public function __construct($connectors)
    {
        if (!$redisConnector instanceof \Redis && !$redisConnector instanceof \Closure) {
            throw new \InvalidArgumentException();
        }
        $this->redisConnector = $redisConnector;
    }

    private function connect()
    {
        $redis = $this->redisConnector;
        if (!$this->redisConnector instanceof \Redis) {
            $c = $this->redisConnector;
            $redis = $c();
        }
        if (!$redisConnector instanceof \Redis) {
            throw new \UnexpectedValueException();
        }
        return $redis;
    }

    function acquire($cacheId, $key, $block=true)
    {
        $name = $this->getLockKey($cacheId, $key);
        $token = uniqid('', true);
        $servers = $this->servers();
        $set = 0;

        for ($i=0; $i<$this->retries; $i++) {
            foreach ($servers as $server) {
                $set += ($server->set($name, $token, ['NX', 'PX'=>$ttl]) ? 1 : 0);
            }
            if (count($servers) == $set) {
                $this->locks[$name] = ['token' => $token, 'ttl' => $ttl];
                return;
            }
        }
    }

    function release($cacheId, $key)
    {
        $name = $this->getLockKey($cacheId, $key);
        if (!isset($this->locks[$name])) {
            throw new \Exception();
        }
        $lock = $this->locks[$name];
        foreach ($this->servers() as $server) {
            $script = '
                if redis.call("GET", KEYS[1]) == ARGV[1] then
                    return redis.call("DEL", KEYS[1])
                else
                    return 0
                end
            ';
            $instance->eval($script, [$name, $lock['token']], 1);
        }
        unset($this->locks[$name]);
    }

    function shutdown()
    {}
}
