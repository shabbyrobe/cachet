<?php
namespace Cachet\Test;

if (!extension_loaded('apc') && !extension_loaded('apcu')) {
    class SessionHandlerAcceptanceTest extends \PHPUnit_Framework_TestCase
    {
        public function testDummy()
        {
            return $this->markTestSkipped("APC must be loaded for this test");
        }
    }
}
else {
    class SessionHandlerAcceptanceTest extends \PHPUnit_Framework_TestCase
    {
        public $url = "http://localhost:1999/sessionhandler/";

        public function setUp()
        {
            $apcExtension = extension_loaded('apcu') ? 'apcu' : 'apc';
            $iniOpts = "-d ".implode(" -d ", [
                'extension='.trim(`php-config --extension-dir`).'/'.$apcExtension.'.so',
                'session.use_trans_sid=1',
                'session.use_only_cookies=0',
            ]);
            $cmd = "php -n $iniOpts -S localhost:1999";
            
            $p = proc_open(
                $cmd, 
                [["pipe", "r"], ["pipe", "w"], ["pipe", "w"]], 
                $pipes, 
                BASE_PATH.'/test/web'
            );
            $s = microtime(true);
            $connected = false;
            while (true) {
                usleep(50000);
                $sock = @fsockopen('localhost', 1999, $errno, $errstr, 1);
                if ($sock !== false) {
                    $connected = true;
                    break;
                }
                elseif (microtime(true) - $s > 3) {
                    break;
                }
            }
            if (!$connected) {
                fclose($pipes[0]);
                $out = stream_get_contents($pipes[1]);
                $err = stream_get_contents($pipes[2]);
                $rc = proc_close($p);
                throw new \RuntimeException("Could not set up web test: $rc $out $err");
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
             
            $set = file_get_contents("{$this->url}/set.php?PHPSESSID=$sessId&key=$key&value=$value");
            $this->assertEquals('OK', $set);
            
            $out = file_get_contents("{$this->url}/get.php?PHPSESSID=$sessId&key=$key");
            $this->assertEquals($value, $out);
        }
        
        public function testConcurrentSessions()
        {
            $sessId1 = md5(uniqid('sess-', true));
            $sessId2 = md5(uniqid('sess-', true));
            
            $set = file_get_contents("{$this->url}/set.php?PHPSESSID=$sessId1&key=foo&value=bar");
            $this->assertEquals('OK', $set);
            
            $set = file_get_contents("{$this->url}/set.php?PHPSESSID=$sessId2&key=foo&value=baz");
            $this->assertEquals('OK', $set);
            
            $out = file_get_contents("{$this->url}/get.php?PHPSESSID=$sessId1&key=foo");
            $this->assertEquals("bar", $out);
            
            $out = file_get_contents("{$this->url}/get.php?PHPSESSID=$sessId2&key=foo");
            $this->assertEquals("baz", $out);
        }
        
        public function testSetDestroy()
        {
            $sessId1 = md5(uniqid('sess-', true));
            $sessId2 = md5(uniqid('sess-', true));
            
            $set = file_get_contents("{$this->url}/set.php?PHPSESSID=$sessId1&key=foo&value=bar");
            $this->assertEquals('OK', $set);
            
            $set = file_get_contents("{$this->url}/set.php?PHPSESSID=$sessId2&key=foo&value=baz");
            $this->assertEquals('OK', $set);
            
            $out = file_get_contents("{$this->url}/destroy.php?PHPSESSID=$sessId1");
            $this->assertEquals('OK', $set);
            
            $out = file_get_contents("{$this->url}/get.php?PHPSESSID=$sessId1&key=foo");
            $this->assertEmpty($out);
            
            $out = file_get_contents("{$this->url}/get.php?PHPSESSID=$sessId2&key=foo");
            $this->assertEquals('baz', $out);
        }
    }
}
