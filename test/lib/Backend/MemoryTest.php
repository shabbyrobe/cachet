<?php
namespace Cachet\Test\Backend;

use Cachet\Dependency;

class MemoryTest extends \BackendTestCase
{
    public function getBackend()
    {
        return new \Cachet\Backend\Memory();
    }
}
