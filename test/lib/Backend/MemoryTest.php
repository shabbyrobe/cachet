<?php
namespace Cachet\Test\Backend;

/**
 * @group backend
 */
class MemoryTest extends \Cachet\Test\BackendTestCase
{
    use \Cachet\Test\IterableBackendTest;

    public function getBackend()
    {
        return new \Cachet\Backend\Memory();
    }
}
