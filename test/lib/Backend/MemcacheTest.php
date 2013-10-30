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
    /**
     * @group backend
     */
    class MemcacheTest extends \Cachet\Test\BackendTestCase
    {
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
            return $backend;
        }

        public function testFlush()
        {
            $backend = $this->getBackend();
            $this->setExpectedException('RuntimeException');
            $backend->flush('cache');
        }

        public function testFlushEmpty()
        {
            $backend = $this->getBackend();
            $this->setExpectedException('RuntimeException');
            $backend->flush('cache');
        }

        public function testUnsafeFlush()
        {
            $backend = $this->getBackend();
            $backend->unsafeFlush = true;
            $backend->set(new \Cachet\Item('cache', 'foo', 'bar'));
            $this->assertNotNull($backend->get('cache', 'foo'));
            $backend->flush('cache');

            $item = $backend->get('cache', 'foo');
            $this->assertNull($item);
        }
    }
}

