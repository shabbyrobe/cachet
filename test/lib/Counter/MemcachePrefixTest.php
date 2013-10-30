<?php
namespace Cachet\Test\Counter;

if (!extension_loaded('memcached')) {
    skip_test(__NAMESPACE__, "MemcachePrefixTest", "Memcached not loaded");
}
elseif (!is_server_listening(
    $GLOBALS['settings']['memcached']['host'],
    $GLOBALS['settings']['memcached']['port']
)) {
    skip_test(__NAMESPACE__, "MemcachePrefixTest", "Memcached not listening");
}
else {
    /**
     * @group counter
     */
    class MemcachePrefixTest extends MemcacheTest
    {
        public function getCounter()
        {
            $memcached = new \Memcached();
            $memcached->addServer(
                $GLOBALS['settings']['memcached']['host'], 
                $GLOBALS['settings']['memcached']['port']
            );
            $memcached->delete('prefix/counter/value');
            $memcached->delete('prefix/counter/value2');
            return new \Cachet\Counter\Memcache($memcached, 'prefix');
        }
    }
}
