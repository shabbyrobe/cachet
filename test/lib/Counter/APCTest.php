<?php
namespace Cachet\Test\Counter;

if (!extension_loaded('apc') && !extension_loaded('apcu_bc')) {
    skip_test(__NAMESPACE__, "APCTest", "Neither apc nor apcu_bc loaded");
}
elseif (ini_get('apc.enable_cli') != 1) {
    skip_test(__NAMESPACE__, "APCTest", "apc.enable_cli must be set");
}
else {
    /**
     * @group apc
     * @group counter
     */
    class APCTest extends \Cachet\Test\CounterTestCase
    {
        public function getCounter()
        {
            apc_clear_cache('user');
            return new \Cachet\Counter\APC();
        }

        public function getMaximumCounterValue()
        {
            return PHP_INT_MAX;
        }
    }
}

