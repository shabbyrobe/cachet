<?php
<<<'EOF'
namespace Cachet\Locker;

use Cachet\Cache;

class SQLite extends \Cachet\Locker
{
    private $auto = false;

    function __construct($cache)
    {
        throw new \Exception("TODO Doesn't work with PDO - can't disable autocommit");
    }

    /**
     * @param string $cacheId
     * @param string $key
     * @return bool
     */
    function acquire($cacheId, $key, $block=true)
    {
        $this->ensureSqlite($cacheId);
        $pdo = $cache->backend->connect();
        $this->auto = $pdo->getAttribute(\PDO::ATTR_AUTOCOMMIT);
        if ($this->auto) {
            $pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, false);
        }
        $pdo->exec("BEGIN EXCLUSIVE TRANSACTION");
        return true;
    }

    /**
     * @param string $cacheId
     * @param string $key
     */
    function release($cacheId, $key)
    {
        $this->ensureSqlite($cacheId);
        $pdo = $cache->backend->connect();
        $pdo->exec("COMMIT TRANSACTION");
        if ($this->auto) {
            $pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, true);
        }
    }

    function shutdown() {}

    private function ensureSqlite($cache)
    {
        if (!$cache->backend instanceof \Cachet\Backend\PDO) {
            throw new \InvalidArgumentException();
        }
        if ($cache->backend->getEngine() != 'sqlite') {
            throw new \InvalidArgumentException();
        }
    }
}
EOF;
