<?php
namespace Cachet\Test\Backend;

if (!extension_loaded('xcache')) {
    skip_test(__NAMESPACE__, "XCacheTest", "xcache extension not loaded");
}
else {
    /**
     * @group backend
     */
    class XCacheTest extends \Cachet\Test\BackendTestCase
    {
        public function getBackend()
        {
            xcache_unset_by_prefix("cache");
            $backend = new \Cachet\Backend\XCache();
            return $backend;
        }

        /**
         * We can't test whether the TTL actually causes an expiration as I
         * can't work out how to trigger xcache's garbage collection
         */
        public function testWithTTL()
        {
            $backend = $this->getBackend();
            $dep = new \Cachet\Dependency\TTL(1234);
            $backend->set(new \Cachet\Item('cache', 'foo', 'bar', $dep));
            $item = $backend->get('cache', 'foo');
            $this->assertNull($item->dependency);
        }
    }
}


