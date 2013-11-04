<?php
namespace Cachet\Test\Counter;

if (!extension_loaded('pdo') || !extension_loaded('pdo_mysql')) {
    skip_test(__NAMESPACE__, 'PDOMySQLTest', 'PDO MySQL extension not loaded');
}
elseif (!is_server_listening(
    $GLOBALS['settings']['mysql']['host'], 
    $GLOBALS['settings']['mysql']['port']
)) {
    skip_test(__NAMESPACE__, 'PDOMySQLTest', 'MySQL server not listening');
}
elseif (!isset($GLOBALS['settings']['mysql']['db'])) {
    skip_test(
        __NAMESPACE__, 'PDOMySQLTest', 
       "Please set 'db' in the 'mysql' section of .cachettestrc"
    );
}
else {
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
