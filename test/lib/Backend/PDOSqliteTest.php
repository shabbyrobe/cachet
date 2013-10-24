<?php
namespace Cachet\Test\Backend;

use Cachet\Backend;
use Cachet\Cache;

class PDOSqliteTest extends \BackendTestCase
{
    public function getBackend()
    {
        $backend = new Backend\PDO(array('dsn'=>'sqlite::memory:'));
        $backend->ensureTableExistsForCache("cache1");
        $backend->ensureTableExistsForCache("cache2");
        return $backend;
    }
}
