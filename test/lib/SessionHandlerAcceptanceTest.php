<?php
namespace Cachet\Test;

class SessionHandlerAcceptanceTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!extension_loaded('apc') && !extension_loaded('apcu'))
            return $this->markTestSkipped("APC must be loaded for this test");
        
        $iniOpts = "-d ".implode(" -d ", [
            'apc.enabled=1',
            'session.use_trans_sid=1',
            'session.use_only_cookies=0',
        ]);
        $cmd = "php -n $iniOpts -S localhost:1999";
        
        $p = proc_open($cmd, [["pipe", "r"], ["pipe", "w"], ["pipe", "w"]], $pipes, BASE_PATH.'/test/web');
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
    
    public function testSetGet()
    {
        $key = uniqid('key-', true);
        $value = uniqid('value-', true);
        $sessId = md5(uniqid('sess-', true));
        
        $set = file_get_contents("http://localhost:1999/sessionhandler/set.php?PHPSESSID=$sessId&key=$key&value=$value");
        $this->assertEquals('OK', $set);
        
        $out = file_get_contents("http://localhost:1999/sessionhandler/get.php?PHPSESSID=$sessId&key=$key");
        $this->assertEquals($value, $out);
    }
    
    public function testConcurrentSessions()
    {
        $sessId1 = md5(uniqid('sess-', true));
        $sessId2 = md5(uniqid('sess-', true));
        
        $set = file_get_contents("http://localhost:1999/sessionhandler/set.php?PHPSESSID=$sessId1&key=foo&value=bar");
        $this->assertEquals('OK', $set);
        
        $set = file_get_contents("http://localhost:1999/sessionhandler/set.php?PHPSESSID=$sessId2&key=foo&value=baz");
        $this->assertEquals('OK', $set);
        
        $out = file_get_contents("http://localhost:1999/sessionhandler/get.php?PHPSESSID=$sessId1&key=foo");
        $this->assertEquals("bar", $out);
        
        $out = file_get_contents("http://localhost:1999/sessionhandler/get.php?PHPSESSID=$sessId2&key=foo");
        $this->assertEquals("baz", $out);
    }
    
    public function testSetDestroy()
    {
        $sessId1 = md5(uniqid('sess-', true));
        $sessId2 = md5(uniqid('sess-', true));
        
        $set = file_get_contents("http://localhost:1999/sessionhandler/set.php?PHPSESSID=$sessId1&key=foo&value=bar");
        $this->assertEquals('OK', $set);
        
        $set = file_get_contents("http://localhost:1999/sessionhandler/set.php?PHPSESSID=$sessId2&key=foo&value=baz");
        $this->assertEquals('OK', $set);
        
        $out = file_get_contents("http://localhost:1999/sessionhandler/destroy.php?PHPSESSID=$sessId1");
        $this->assertEquals('OK', $set);
        
        $out = file_get_contents("http://localhost:1999/sessionhandler/get.php?PHPSESSID=$sessId1&key=foo");
        $this->assertEmpty($out);
        
        $out = file_get_contents("http://localhost:1999/sessionhandler/get.php?PHPSESSID=$sessId2&key=foo");
        $this->assertEquals('baz', $out);
    }
}
