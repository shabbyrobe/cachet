<?php
namespace Cachet\Test\Backend;

use Cachet\Backend;
use Cachet\Cache;

class PDOMySQLTest extends \BackendTestCase
{
    public function getBackend()
    {
        $backend = new Backend\PDO($GLOBALS['settings']['mysql']);
        
        if (!$GLOBALS['settings']['mysql']['db']) {
            return $this->markTestSkipped("Please pass db");
        }
        
        try {
            $backend->connect();
        }
        catch (\PDOException $pex) {
            return $this->markTestSkipped("Cannot connect to MySQL - ".$pex);
        }
        
        return $backend;
    }
    
    public function setUp()
    {
        $pdo = Backend\PDO::createPDO($GLOBALS['settings']['mysql']);
        $db = $GLOBALS['settings']['mysql']['db'];
        $pdo->exec("DROP DATABASE IF EXISTS `$db`");
        $pdo->exec("CREATE DATABASE `$db`");
        $pdo->exec("USE `$db`");
        
        Backend\PDO::createMySQLTable($pdo, 'cache1');
        Backend\PDO::createMySQLTable($pdo, 'cache2');
    }
}
