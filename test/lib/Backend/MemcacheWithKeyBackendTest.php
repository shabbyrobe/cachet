<?php
namespace Cachet\Test\Backend;

use Cachet\Backend;

if (!extension_loaded('memcached')) {
    skip_test(__NAMESPACE__, "MemcacheTest", "Memcached not loaded");
}
elseif (!is_server_listening(
    $GLOBALS['settings']['memcached']['host'],
    $GLOBALS['settings']['memcached']['port']
)) {
    skip_test(__NAMESPACE__, "MemcacheTest", "Memcached not listening");
}
else {
    class MemcacheTest extends \Cachet\Test\BackendTestCase
    {
        public function setUp()
        {
            $memcached = $this->getMemcached();
            $memcached->flush();
        }
        
        public function getBackend()
        {
            $backend = $this->createMemcacheBackend();
            $backend->setKeyBackend(new \Cachet\Backend\Memory());
            
            return $backend;
        }
        
        protected function createMemcacheBackend()
        {
            $memcached = $this->getMemcached();
            $backend = new \Cachet\Backend\Memcache($memcached);
            return $backend;
        }   
        
        protected function getMemcached()
        {
            $memcached = new \Memcached();
            $memcached->addServer(
                $GLOBALS['settings']['memcached']['host'], 
                $GLOBALS['settings']['memcached']['port']
            );
            return $memcached;
        }
    }
}
