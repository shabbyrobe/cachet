<?php
namespace Cachet\Test\Counter;

if (!extension_loaded('apcu')) {
    skip_test(__NAMESPACE__, "APCUTest", "apcu extension not loaded");
}
elseif (ini_get('apc.enable_cli') != 1) {
    skip_test(__NAMESPACE__, "APCTest", "apc.enable_cli must be set");
}
else {
    /**
     * @group apcu
     * @group counter
     */
    class APCUTest extends \Cachet\Test\CounterTestCase
    {
        public function getCounter()
        {
            apcu_clear_cache();
            return new \Cachet\Counter\APCU();
        }

        public function getMaximumCounterValue()
        {
            return PHP_INT_MAX;
        }
    }
}


