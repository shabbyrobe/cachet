<?php
namespace Cachet\Test\Backend;

if (!extension_loaded('apc') && !extension_loaded('apcu_bc')) {
    skip_test(__NAMESPACE__, "APCTest", "Neither apc nor apcu_bc loaded");
}
elseif (ini_get('apc.enable_cli') != 1) {
    skip_test(__NAMESPACE__, "APCTest", "apc.enable_cli must be set");
}
else {
    /**
     * @group apc
     * @group backend
     */
    class APCTest extends \Cachet\Test\BackendTestCase
    {
        use \Cachet\Test\IteratorBackendTest;

        public $backendPrefix;

        public function getBackend()
        {
            apc_clear_cache('user');
            $backend = new \Cachet\Backend\APC();
            return $backend;
        }

        public function testWithTTL()
        {
            $backend = $this->getBackend();
            $dep = new \Cachet\Dependency\TTL(300);
            $backend->set(new \Cachet\Item('cache', 'foo', 'bar', $dep));
            $iter = new \APCIterator('user', "~^{$this->backendPrefix}cache/foo$~");
            $ttl = $iter->current()['ttl'];
            
            // surely not longer than 2 seconds!
            $this->assertTrue($ttl >= 298 && $ttl <= 300);
        }
    }
}

