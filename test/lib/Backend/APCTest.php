<?php
namespace Cachet\Test\Backend;

if (!extension_loaded('apc') && !extension_loaded('apcu')) {
    skip_test(__NAMESPACE__, "APCTest", "APC extension not loaded");
}
else {
    class APCTest extends \Cachet\Test\BackendTestCase
    {
        public function getBackend()
        {
            $backend = new \Cachet\Backend\APC();
            return $backend;
        }
    }
}

