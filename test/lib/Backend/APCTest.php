<?php
namespace Cachet\Test\Backend;

class APCTest extends \BackendTestCase
{
    public function getBackend()
    {
        if (!extension_loaded('apc') && !extension_loaded('apcu'))
            return $this->markTestSkipped("APC extension not loaded");
        
        $backend = new \Cachet\Backend\APC();
        return $backend;
    }
}
