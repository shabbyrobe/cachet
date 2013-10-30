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
    /**
     * @group counter
     */
    class MemcacheTest extends \Cachet\Test\CounterTestCase
    {
        public function getCounter()
        {
            $memcached = new \Memcached();
            $memcached->addServer(
                $GLOBALS['settings']['memcached']['host'], 
                $GLOBALS['settings']['memcached']['port']
            );
            $memcached->delete('counter/value');
            $memcached->delete('counter/value2');
            return new \Cachet\Counter\Memcache($memcached);
        }

        /**
         * @dataProvider dataForIncrement
         */
        function testDecrementByWhenUnset($initial, $by)
        {
            $this->setExpectedException("OutOfBoundsException");
            $this->getCounter()->decrement('value', 1);
        }
    }
}

