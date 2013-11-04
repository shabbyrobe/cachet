<?php
namespace Cachet\Test\Counter;

if (!extension_loaded('xcache')) {
    skip_test(__NAMESPACE__, "XCachePrefixTest", "xcache extension not loaded");
}
else {
    /**
     * @group counter
     */
    class XCachePrefixTest extends XCacheTest
    {
        public function getCounter()
        {
            xcache_unset('prefix/counter/value');
            xcache_unset('prefix/counter/value2');
            return new \Cachet\Counter\XCache('prefix');
        }
    }
}


