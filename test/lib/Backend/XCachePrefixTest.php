<?php
namespace Cachet\Test\Backend;

if (!extension_loaded('xcache')) {
    skip_test(__NAMESPACE__, "XCachePrefixTest", "xcache extension not loaded");
}
else {
    /**
     * @group backend
     */
    class XCachePrefixTest extends XCacheTest
    {
        public function getBackend()
        {
            xcache_unset_by_prefix("prefix/");
            $backend = new \Cachet\Backend\XCache("prefix");
            return $backend;
        }
    }
}



