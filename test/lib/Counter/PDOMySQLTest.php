<?php
namespace Cachet\Test\Counter;

if (pdo_mysql_tests_valid(__NAMESPACE__, 'PDOMySQLTest')) {
    /**
     * @group counter
     */
    class PDOMySQLTest extends \Cachet\Test\CounterTestCase
    {
        public function getCounter()
        {
            $connector = new \Cachet\Connector\PDO($GLOBALS['settings']['mysql']);
            $counter = new \Cachet\Counter\PDOMySQL($connector, "cachet_counter_test");
            return $counter;
        }

        public function setUp()
        {
            $counter = $this->getCounter();
            $counter->connector->connect()->exec("DROP TABLE IF EXISTS `{$counter->tableName}`");
            $counter->ensureTableExists();
        }

        public function getMaximumCounterValue()
        {
            return "9223372036854775807";
        }
    }
}
