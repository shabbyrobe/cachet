<?php
namespace Cachet\Test\Counter;

if (!extension_loaded('xcache')) {
    skip_test(__NAMESPACE__, "XCacheTest", "xcache extension not loaded");
}
else {
    class XCacheTest extends \Cachet\Test\CounterTestCase
    {
        public function getCounter()
        {
            return new \Cachet\Counter\XCache();
        }
    }
}


