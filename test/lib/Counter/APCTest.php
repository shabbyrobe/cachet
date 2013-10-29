<?php
namespace Cachet\Test\Counter;

if (!extension_loaded('apc') || !extension_loaded('apcu')) {
    skip_test(__NAMESPACE__, "APCTest", "Neither apc nor apcu loaded");
}
elseif (ini_get('apc.enable_cli') != 1) {
    skip_test(__NAMESPACE__, "APCTest", "apc.enable_cli must be set");
}
else {
    class APCTest extends \Cachet\Test\CounterTestCase
    {
        public function getCounter()
        {
            return new \Cachet\Counter\APC();
        }
    }
}

