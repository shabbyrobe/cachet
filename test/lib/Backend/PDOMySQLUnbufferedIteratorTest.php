<?php
namespace Cachet\Test\Backend;

use Cachet\Backend;
use Cachet\Cache;

if (pdo_mysql_tests_valid(__NAMESPACE__, 'PDOMySQLUnbufferedIteratorTest')) {
    /**
     * @group backend
     */
    class PDOMySQLUnbufferedIteratorTest extends PDOMySQLTest
    {
        use \Cachet\Test\IterableBackendYieldMemoryTest;

        public function getBackend()
        {
            $backend = parent::getBackend();
            $backend->mysqlUnbufferedIteration = true;
            return $backend;
        }

        public function setUp()
        {
            parent::setUp();
            $this->backend->ensureTableExistsForCache('a');
        }
    }
}

