<?php
namespace Cachet\Test\Backend;

use Cachet\Backend;
use Cachet\Cache;

$mysqlListening = is_server_listening(
    $GLOBALS['settings']['mysql']['host'], 
    $GLOBALS['settings']['mysql']['port']
);

if (!$mysqlListening) {
    class PDOMySQLTest extends \PHPUnit_Framework_TestCase
    {
        public function testDummy()
        {
            $this->markTestSkipped("MySQL server not listening");
        }
    }
}
else {
    class PDOMySQLTest extends \BackendTestCase
    {
        public function getBackend()
        {
            if (!$this->backend) {
                $backend = new Backend\PDO($GLOBALS['settings']['mysql']);
                
                if (!isset($GLOBALS['settings']['mysql']['db'])) {
                    return $this->markTestSkipped(
                        "Please set 'db' in the 'mysql' section of .cachettestrc"
                    );
                }
                
                try {
                    $backend->connector->connect();
                }
                catch (\PDOException $pex) {
                    return $this->markTestSkipped("Cannot connect to MySQL - ".$pex);
                }
                
                $this->backend = $backend;
            }
            return $this->backend;
        }
        
        public function setUp()
        {
            $this->backend = null; 
            $this->backend = $this->getBackend();

            $pdo = $this->backend->connect();
            $db = $GLOBALS['settings']['mysql']['db'];
            $pdo->exec("DROP DATABASE IF EXISTS `$db`");
            $pdo->exec("CREATE DATABASE `$db`");
            $pdo->exec("USE `$db`");
            
            Backend\PDO::createMySQLTable($pdo, 'cache1');
            Backend\PDO::createMySQLTable($pdo, 'cache2');
        }
    }
}
