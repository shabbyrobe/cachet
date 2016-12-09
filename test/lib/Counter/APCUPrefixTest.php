<?php
namespace Cachet\Test\Counter;

if (!extension_loaded('apcu')) {
    skip_test(__NAMESPACE__, "APCUPrefixTest", "apcu extension not loaded");
}
elseif (ini_get('apc.enable_cli') != 1) {
    skip_test(__NAMESPACE__, "APCUPrefixTest", "apc.enable_cli must be set");
}
else {
    /**
     * @group apcu
     * @group counter
     */
    class APCUPrefixTest extends APCUTest
    {
        public function getCounter()
        {
            apcu_clear_cache();
            return new \Cachet\Counter\APCU('prefix');
        }
    }
}

