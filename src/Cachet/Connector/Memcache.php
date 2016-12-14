<?php
namespace Cachet\Connector;

class Memcache
{
    /** @var \Memcached|null */
    public $memcache;

    private $servers = [];

    /** @var callable|null */
    private $creatorCallback = null;

    /** @var string|null */
    private $persistentId;

    /**
     * @param \Memcached|array|string $memcache
     * @param string $persistentId
     */
    public function __construct($memcache, $persistentId=null)
    {
        if ($memcache instanceof \Memcached) {
            $this->memcache = $memcache;
        }
        else {
            $this->persistentId = $persistentId;
            if (is_string($memcache)) {
                $memcache = [['server'=>$memcache]];
            }
            if (is_array($memcache)) {
                foreach ($memcache as $server) {
                    $this->servers[] = array_merge(
                        [
                            'server'=>'',
                            'port'=>11211,
                            'weight'=>0,
                        ],
                        $server
                    );
                }
            } elseif (is_callable($memcache)) {
                $this->creatorCallback = $memcache;
            } else {
                throw new \InvalidArgumentException();
            }
        }
    }

    function connect()
    {
        if (!$this->memcache) {
            if ($this->creatorCallback) {
                $this->memcache = call_user_func($this->creatorCallback, $this);
            }
            elseif ($this->servers) {
                $this->memcache = $this->persistentId
                    ? new \Memcached($this->persistentId)
                    : new \Memcached()
                ;
                $this->memcache->addServers($this->servers);
            }
            else {
                throw new \RuntimeException(
                    "Can't reconnect to Memcache when passing a Memcache instance to the constructor"
                );
            }
        }
        return $this->memcache;
    }

    function disconnect()
    {
        $this->memcache->quit();
        $this->memcache = null;
    }
}
