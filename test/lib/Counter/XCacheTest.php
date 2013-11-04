<?php
namespace Cachet\Test\Counter;

if (!extension_loaded('xcache')) {
    skip_test(__NAMESPACE__, "XCacheTest", "xcache extension not loaded");
}
else {
    /**
     * @group counter
     */
    class XCacheTest extends \Cachet\Test\CounterTestCase
    {
        public function getCounter()
        {
            xcache_unset('counter/value');
            xcache_unset('counter/value2');
            return new \Cachet\Counter\XCache();
        }

        public function getMaximumCounterValue()
        {
            return PHP_INT_MAX;
        }
    }
}

