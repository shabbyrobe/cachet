<?php
namespace Cachet\Test\Backend;

use Cachet\Backend;
use Cachet\Cache;

if (pdo_mysql_tests_valid(__NAMESPACE__, 'PDOMySQLTest')) {
    /**
     * @group backend
     */
    class PDOMySQLTest extends \Cachet\Test\BackendTestCase
    {
        use \Cachet\Test\IteratorBackendTest;

        public function getBackend()
        {
            if (!$this->backend) {
                $backend = new Backend\PDO($GLOBALS['settings']['mysql']);
                 
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

            $pdo = $this->backend->connector->connect();
            $db = $GLOBALS['settings']['mysql']['db'];
            $pdo->exec("DROP DATABASE IF EXISTS `$db`");
            $pdo->exec("CREATE DATABASE `$db`");
            $pdo->exec("USE `$db`");
            
            $this->backend->ensureTableExistsForCache('cache1');
            $this->backend->ensureTableExistsForCache('cache2');
        }
    }
}
