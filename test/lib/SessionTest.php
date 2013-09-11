<?php
namespace Cachet\Test;

/*
class SessionTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $p = proc_open("php -S localhost:1999", [["pipe", "r"], ["pipe", "w"], ["pipe", "w"]], $pipes, __DIR__);
        $s = microtime(true);
        while (true) {
            usleep(50000);
            $sock = @fsockopen('localhost', 1999, $errno, $errstr, 1);
            if ($sock !== false)
                break;
            elseif (microtime(true) - $s > 5)
                break;
        }
        $this->p = $p;
    }
    
    public function tearDown()
    {
        proc_terminate($this->p);
    }
    
    public function testDestroy()
    {
        
    }
}

if (!debug_backtrace()) {
    
}
*/
