<?php
namespace Cachet;

class SessionHandler implements \SessionHandlerInterface
{
    private $cache;
    private $prefix;
    private $ttl;
    
    public $runGc = false;
    
    public static $instance;
    
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }
    
    public static function register(Cache $cache)
    {
        if (!static::$instance) {
            static::$instance = new static($cache);
            session_set_save_handler(static::$instance);
        }
    }
    
    public function close()
    {
        $this->cache = null;
        return true;
    }
    
    public function destroy($sessionId) 
    {
        $this->cache->delete("{$this->prefix}/{$sessionId}");
        return true;
    }
    
    public function gc($maxLifetime)
    {
        if ($this->runGc)
            $this->cache->removeInvalid();
        
        return true;
    }
    
    public function open($savePath, $name)
    {
        $this->ttl = ini_get("session.gc_maxlifetime");
        if ($this->ttl <= 0)
            $this->ttl = null;
        
        $this->prefix = "$savePath/$name";
        return true;
    }
    
    public function read($sessionId)
    {
        return $this->cache->get("{$this->prefix}/{$sessionId}");
    }
    
    public function write($sessionId, $sessionData)
    {
        $this->cache->set("{$this->prefix}/{$sessionId}", $sessionData);
        return true;
    }
}
