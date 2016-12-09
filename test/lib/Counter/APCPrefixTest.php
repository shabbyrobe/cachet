<?php
namespace Cachet\Test\Counter;

if (!extension_loaded('apc') || !extension_loaded('apcu')) {
    skip_test(__NAMESPACE__, "APCPrefixTest", "Neither apc nor apcu loaded");
}
elseif (ini_get('apc.enable_cli') != 1) {
    skip_test(__NAMESPACE__, "APCPrefixTest", "apc.enable_cli must be set");
}
else {
    /**
     * @group apc
     * @group counter
     */
    class APCPrefixTest extends APCTest
    {
        public function getCounter()
        {
            apc_clear_cache('user');
            return new \Cachet\Counter\APC('prefix');
        }
    }
}

