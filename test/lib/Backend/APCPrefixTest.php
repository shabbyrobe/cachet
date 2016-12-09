<?php
namespace Cachet\Test\Backend;

if (!extension_loaded('apc') && !extension_loaded('apcu_bc')) {
    skip_test(__NAMESPACE__, "APCPrefixTest", "Neither apc nor apcu_bc loaded");
}
elseif (ini_get('apc.enable_cli') != 1) {
    skip_test(__NAMESPACE__, "APCPrefixTest", "apc.enable_cli must be set");
}
else {
    /**
     * @group apc
     * @group backend
     */
    class APCPrefixTest extends \Cachet\Test\BackendTestCase
    {
        use \Cachet\Test\IteratorBackendTest;

        public function getBackend()
        {
            apc_clear_cache('user');
            $this->backendPrefix = 'prefix/';
            $backend = new \Cachet\Backend\APC('prefix');
            return $backend;
        }
    }
}


