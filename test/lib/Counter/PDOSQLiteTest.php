<?php
namespace Cachet\Test\Counter;

if (!extension_loaded('pdo') || !extension_loaded('pdo_sqlite')) {
    skip_test(__NAMESPACE__, 'PDOSQLiteCounterTest', 'PDO SQLite extension not loaded');
}
else {
    /**
     * @group counter
     */
    class PDOSQLiteCounterTest extends \Cachet\Test\CounterTestCase
    {
        public function getCounter()
        {
            $pdo = new \PDO(
                'sqlite::memory:',
                null,
                null, 
                [\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION]
            );
            $counter = new \Cachet\Counter\PDOSQLite($pdo);
            $counter->ensureTableExists();
            return $counter;
        }
    }
}
