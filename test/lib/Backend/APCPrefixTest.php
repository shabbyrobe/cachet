<?php
namespace Cachet\Test\Backend;

if (!extension_loaded('apc') || !extension_loaded('apcu')) {
    skip_test(__NAMESPACE__, "APCPrefixTest", "Neither apc nor apcu loaded");
}
elseif (ini_get('apc.enable_cli') != 1) {
    skip_test(__NAMESPACE__, "APCPrefixTest", "apc.enable_cli must be set");
}
else {
    /**
     * @group backend
     */
    class APCPrefixTest extends \Cachet\Test\IterableBackendTestCase
    {
        public function getBackend()
        {
            apc_clear_cache('user');
            $backend = new \Cachet\Backend\APC('prefix');
            return $backend;
        }
    }
}


