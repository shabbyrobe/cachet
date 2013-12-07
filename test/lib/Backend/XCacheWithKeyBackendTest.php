<?php
namespace Cachet\Test\Backend;

if (!extension_loaded('xcache')) {
    skip_test(__NAMESPACE__, "XCacheWithKeyBackendTest", "xcache extension not loaded");
}
else {
    /**
     * @group backend
     */
    class XCacheWithKeyBackendTest extends XCacheTest
    {
        use \Cachet\Test\IterableBackendTest;

        public function setUp()
        {
            $backend = $this->getBackend();
            $backend->connector->connect()->flush();
        }
        
        public function getBackend()
        {
            xcache_unset_by_prefix("cache");
            $backend = new \Cachet\Backend\XCache();
            $backend->setKeyBackend(new \Cachet\Backend\Memory());
            return $backend;
        }
    }
}
