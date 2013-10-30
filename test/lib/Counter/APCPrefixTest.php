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
     * @group counter
     */
    class APCPrefixTest extends \Cachet\Test\CounterTestCase
    {
        public function getCounter()
        {
            apc_delete("prefix/counter/value");
            apc_delete("prefix/counter/value2");
            return new \Cachet\Counter\APC('prefix');
        }
    }
}

