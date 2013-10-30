<?php
namespace Cachet\Test\Backend;

/**
 * @group backend
 */
class MemoryTest extends \Cachet\Test\IterableBackendTestCase
{
    public function getBackend()
    {
        return new \Cachet\Backend\Memory();
    }
}
