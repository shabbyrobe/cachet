<?php
namespace Cachet\Test\Backend;

use Cachet\Backend;

if (!extension_loaded('memcached')) {
    skip_test(__NAMESPACE__, "MemcacheWithKeyBackendTest", "Memcached not loaded");
}
elseif (!is_server_listening(
    $GLOBALS['settings']['memcached']['host'],
    $GLOBALS['settings']['memcached']['port']
)) {
    skip_test(__NAMESPACE__, "MemcacheWithKeyBackendTest", "Memcached not listening");
}
else {
    /**
     * @group backend
     */
    class MemcacheWithKeyBackendTest extends \Cachet\Test\BackendTestCase
    {
        use \Cachet\Test\IteratorBackendTest;

        public function setUp()
        {
            $backend = $this->getBackend();
            $backend->connector->connect()->flush();
        }
        
        public function getBackend()
        {
            $memcached = new \Memcached();
            $memcached->addServer(
                $GLOBALS['settings']['memcached']['host'], 
                $GLOBALS['settings']['memcached']['port']
            );
            $backend = new \Cachet\Backend\Memcache($memcached);
            $backend->setKeyBackend(new \Cachet\Backend\Memory());
            return $backend;
        }
    }
}
