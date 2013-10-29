<?php
namespace Cachet\Test\Backend;

if (!extension_loaded('xcache')) {
    skip_test(__NAMESPACE__, "XCacheTest", "xcache extension not loaded");
}
else {
    class XCacheTest extends \Cachet\Test\BackendTestCase
    {
        public function getBackend()
        {
            $backend = new \Cachet\Backend\APC();
            return $backend;
        }
    }
}


