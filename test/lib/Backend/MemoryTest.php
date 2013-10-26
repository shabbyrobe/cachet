<?php
namespace Cachet\Test\Backend;

class MemoryTest extends \Cachet\Test\BackendTestCase
{
    public function getBackend()
    {
        return new \Cachet\Backend\Memory();
    }
}
