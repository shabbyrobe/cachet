<?php
namespace Cachet\Test\Backend;

use Cachet\Backend;

class MemcachedTest extends \BackendTestCase
{
    public function setUp()
    {
        $memcached = $this->getMemcached();
        $memcached->flush();
    }
    
    public function getBackend()
    {
        $backend = $this->createMemcacheBackend();
        $backend->setKeyBackend(new \Cachet\Backend\Memory());
        
        return $backend;
    }
    
    protected function createMemcacheBackend()
    {
        $memcached = $this->getMemcached();
        $backend = new \Cachet\Backend\Memcached($memcached);
        return $backend;
    }   
    
    protected function getMemcached()
    {
        if (!extension_loaded('memcached'))
            $this->markTestSkipped("memcached extension not found");
        
        if (!isset($GLOBALS['settings']['memcached']) || !$GLOBALS['settings']['memcached']['server'])
            $this->markTestSkipped("Please supply a memcached server in .cachettestrc");
        
        $memcached = new \Memcached();
        $memcached->addServer(
            $GLOBALS['settings']['memcached']['server'], 
            isset($GLOBALS['settings']['memcached']['port']) ? $GLOBALS['settings']['memcached']['port'] : 11211
        );
        return $memcached;
    }
}
