<?php
namespace Cachet\Test\Backend;

use Cachet\Backend;
use Cachet\Cache;

class PDOMySQLTest extends \BackendTestCase
{
    public function getBackend()
    {
        if (!$this->backend) {
            $backend = new Backend\PDO($GLOBALS['settings']['mysql']);
            
            if (!isset($GLOBALS['settings']['mysql']['db'])) {
                return $this->markTestSkipped("Please pass db name");
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
