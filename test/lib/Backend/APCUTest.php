<?php
namespace Cachet\Test\Backend;

if (!extension_loaded('apcu')) {
    skip_test(__NAMESPACE__, "APCUTest", "apcu extension not loaded");
}
elseif (ini_get('apc.enable_cli') != 1 && ini_get('apcu.enable_cli') != 1) {
    skip_test(__NAMESPACE__, "APCUTest", "apcu.enable_cli or apc.enable_cli must be set");
}
else {
    /**
     * @group apcu
     * @group backend
     */
    class APCUTest extends \Cachet\Test\BackendTestCase
    {
        use \Cachet\Test\IteratorBackendTest;

        public $backendPrefix;

        public function getBackend()
        {
            apcu_clear_cache();
            $backend = new \Cachet\Backend\APCU();
            return $backend;
        }

        public function testWithTTL()
        {
            $backend = $this->getBackend();
            $dep = new \Cachet\Dependency\TTL(300);
            $backend->set(new \Cachet\Item('cache', 'foo', 'bar', $dep));
            $iter = new \APCUIterator('user', "~^{$this->backendPrefix}cache/foo$~");
            $ttl = $iter->current()['ttl'];
            
            // surely not longer than 2 seconds!
            $this->assertTrue($ttl >= 298 && $ttl <= 300);
        }
    }
}


