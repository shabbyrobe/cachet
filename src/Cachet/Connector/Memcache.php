<?php
namespace Cachet\Connector;

class Memcache
{
    public $memcache;
    
    private $servers = [];
    private $creatorCallback = null;
    private $persistentId;

    private static $useMemcached = null;

    public function __construct($memcache, $persistentId=null)
    {
        if ($memcache instanceof \Memcached || $memcache instanceof \Memcache) {
            $this->memcache = $memcache;
        }
        else {
            $this->persistentId = $persistentId;
            if (is_string($memcache))
                $memcache = [['server'=>$memcache]];
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
            }
            elseif (is_callable($memcache)) {
                $this->creatorCallback = $memcache;
            }
            else {
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
                if (static::$useMemcached === null) {
                    static::detectMemcache();
                }

                if (static::$useMemcached) {
                    $this->memcache = $this->persistentId 
                        ? new \Memcached($this->persistentId)
                        : new \Memcached()
                    ;
                    $this->memcache->addServers($this->servers);
                }
                else {
                    $this->memcache = new \Memcache();
                    foreach ($this->servers as $server) {
                        $this->memcache->addServer(
                            $server['host'],
                            $server['port'],
                            !!$this->persistentId,
                            $server['weight'] ?: null
                        );
                    }
                }
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
        if ($this->memcache instanceof \Memcached)
            $this->memcache->quit();
        elseif ($this->memchace instanceof \Memcache)
            $this->memcache->close();
        $this->memcache = null;
    }

    private static function detectMemcache()
    {
        if (extension_loaded('memcached'))
            static::$useMemcached = true;
        elseif (extension_loaded('memcache'))
            static::$useMemcached = false;
        else
            throw new \RuntimeException("Neither memcached nor memcache extensions loaded");
    }
}

