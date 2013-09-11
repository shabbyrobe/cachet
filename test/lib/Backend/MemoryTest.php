<?php
namespace Cachet\Test\Backend;


class MemoryTest extends \BackendTestCase
{
    public function getBackend()
    {
        return new \Cachet\Backend\Memory();
    }
}
