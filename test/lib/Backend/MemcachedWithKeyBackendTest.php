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
            return $this->markTestSkipped("memcached extension not found");
        
        if (!isset($GLOBALS['settings']['memcached']) || !$GLOBALS['settings']['memcached']['host'])
            return $this->markTestSkipped("Please supply a memcached host in .cachettestrc");
        
        $sock = @fsockopen(
            $GLOBALS['settings']['memcached']['host'], 
            $GLOBALS['settings']['memcached']['port'],
            $errno,
            $errstr,
            1
        );
        if ($sock === false)
            $this->markTestSkipped("Memcache server not running");
        
        fclose($sock);
        
        $memcached = new \Memcached();
        $memcached->addServer(
            $GLOBALS['settings']['memcached']['host'], 
            $GLOBALS['settings']['memcached']['port']
        );
        return $memcached;
    }
}
