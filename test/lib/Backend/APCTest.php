<?php
namespace Cachet\Test\Backend;

if (!extension_loaded('apc') && !extension_loaded('apcu')) {
    class APCTest extends \PHPUnit_Framework_TestCase
    {
        public function testDummy()
        {
            return $this->markTestSkipped("APC extension not loaded");
        }
    }
}
else {
    class APCTest extends \BackendTestCase
    {
        public function getBackend()
        {
            $backend = new \Cachet\Backend\APC();
            return $backend;
        }
    }
}

