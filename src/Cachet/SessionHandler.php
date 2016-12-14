<?php
namespace Cachet;

class SessionHandler implements \SessionHandlerInterface
{
    /** @var Cache|null */
    private $cache;

    /** @var string */
    private $prefix;

    /** @var int|null */
    private $ttl;

    /** @var bool */
    public $runGc = false;

    public static $instance;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
        if ($cache->backend instanceof Backend\Session) {
            throw new \InvalidArgumentException(
                "You can't use PHP sessions as the backend for PHP sessions!"
            );
        }
        elseif ($cache->backend instanceof Backend\File) {
            trigger_error(
                "Backend\File is really just a slower version of PHP's default session mechanism.",
                E_USER_WARNING
            );
        }
    }

    public static function register(Cache $cache, $options=[])
    {
        if (!static::$instance) {
            static::$instance = $instance = new static($cache);
            session_set_save_handler(static::$instance, true);

            if (isset($options['runGc'])) {
                $instance->runGc = $options['runGc'] == true;
            }
        }
    }

    public function close()
    {
        $this->cache = null;
        return true;
    }

    /**
     * @param string $sessionId 
     * @return bool
     */
    public function destroy($sessionId)
    {
        $this->cache->delete("{$this->prefix}/{$sessionId}");
        return true;
    }

    /**
     * @param string $maxLifetime
     */
    public function gc($maxLifetime)
    {
        // TODO: do something with amxlifetime
        if ($this->runGc) {
            $this->cache->removeInvalid();
        }
        return true;
    }

    /**
     * @param string $savePath
     * @param string $name
     * @return bool
     */
    public function open($savePath, $name)
    {
        $this->ttl = ini_get("session.gc_maxlifetime");
        if ($this->ttl <= 0) {
            $this->ttl = null;
        }
        $this->prefix = ($savePath ? "$savePath/" : "")."$name";
        return true;
    }

    /**
     * @param string $sessionId
     * @return string
     */
    public function read($sessionId)
    {
        $ret = $this->cache->get("{$this->prefix}/{$sessionId}");
        if ($ret === null) {
            $ret = "";
        }
        return $ret;
    }

    /**
     * @param string $sessionId
     * @param string $sessionData
     * @return bool
     */
    public function write($sessionId, $sessionData)
    {
        $this->cache->set("{$this->prefix}/{$sessionId}", $sessionData, $this->ttl);
        return true;
    }
}
