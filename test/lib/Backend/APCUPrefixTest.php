<?php
namespace Cachet\Test\Backend;

if (!extension_loaded('apcu')) {
    skip_test(__NAMESPACE__, "APCUPrefixTest", "apcu extension not loaded");
}
elseif (ini_get('apc.enable_cli') != 1) {
    skip_test(__NAMESPACE__, "APCUPrefixTest", "apc.enable_cli must be set");
}
else {
    /**
     * @group apcu
     * @group backend
     */
    class APCUPrefixTest extends \Cachet\Test\BackendTestCase
    {
        use \Cachet\Test\IteratorBackendTest;

        public function getBackend()
        {
            apcu_clear_cache('user');
            $this->backendPrefix = 'prefix/';
            $backend = new \Cachet\Backend\APCU('prefix');
            return $backend;
        }
    }
}


