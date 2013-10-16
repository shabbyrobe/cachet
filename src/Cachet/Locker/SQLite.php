<?php
namespace Cachet\Locker;

use Cachet\Cache;

class SQLite extends \Cachet\Locker
{
    private $auto = false;

    function __construct()
    {
        throw new \Exception("Doesn't work with PDO - can't disable autocommit");
    }

    function acquire(Cache $cache, $key, $block=true)
    {
        $this->ensureSqlite($cache);
        $pdo = $cache->backend->connect();
        $this->auto = $pdo->getAttribute(\PDO::ATTR_AUTOCOMMIT);
        if ($this->auto)
            $pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, false);
        $pdo->exec("BEGIN EXCLUSIVE TRANSACTION");
    }

    function release(Cache $cache, $key)
    {
        $this->ensureSqlite($cache);
        $pdo = $cache->backend->connect();
        $pdo->exec("COMMIT TRANSACTION");
        if ($this->auto)
            $pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, true);
    }

    function shutdown()
    {
    }

    private function ensureSqlite($cache)
    {
        if (!$cache->backend instanceof \Cachet\Backend\PDO)
            throw new \InvalidArgumentException();
        if ($cache->backend->getEngine() != 'sqlite')
            throw new \InvalidArgumentException();
    }
}
