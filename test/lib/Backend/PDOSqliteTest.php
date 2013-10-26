<?php
namespace Cachet\Test\Backend;

use Cachet\Backend;
use Cachet\Cache;

if (!extension_loaded('pdo') || !extension_loaded('pdo_sqlite')) {
    skip_test(__NAMESPACE__, 'PDOSQLiteTest', 'PDO SQLite extension not loaded');
}
else {
    class PDOSqliteTest extends \Cachet\Test\BackendTestCase
    {
        public function getBackend()
        {
            $backend = new Backend\PDO(array('dsn'=>'sqlite::memory:'));
            $backend->ensureTableExistsForCache("cache1");
            $backend->ensureTableExistsForCache("cache2");
            return $backend;
        }
    }
}
