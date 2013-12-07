<?php
namespace Cachet\Test\Backend;

use Cachet\Backend;
use Cachet\Cache;

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
    skip_test(__NAMESPACE__, 'PDOMySQLTest', "Please set 'db' in the 'mysql' section of .cachettestrc");
}
else {
    /**
     * @group backend
     */
    class PDOMySQLTest extends \Cachet\Test\BackendTestCase
    {
        use \Cachet\Test\IterableBackendTest;

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
