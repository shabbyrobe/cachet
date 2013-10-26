<?php
namespace Cachet\Test\Counter;

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
    class MemcacheTest extends \Cachet\Test\CounterTestCase
    {
        public function getCounter()
        {
            $memcached = new \Memcached();
            $memcached->addServer(
                $GLOBALS['settings']['memcached']['host'], 
                $GLOBALS['settings']['memcached']['port']
            );
            return new \Cachet\Counter\Memcache($memcached);
        }
    }
}

