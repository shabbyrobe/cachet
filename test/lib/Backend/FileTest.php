<?php
namespace Cachet\Test\Backend;

use Cachet\Backend;
use Cachet\Cache;
use Cachet\Item;

/**
 * @group backend
 */
class FileTest extends \Cachet\Test\BackendTestCase
{
    use \Cachet\Test\IteratorBackendTest;
    use \Cachet\Test\IteratorBackendYieldMemoryTest;

    public function setUp()
    {
        $this->path = sys_get_temp_dir()."/".uniqid('', true);
        mkdir($this->path);
    }

    function getBackend()
    {
        return new Backend\File($this->path);
    }
    
    public function tearDown()
    {
        $flags = \FilesystemIterator::KEY_AS_PATHNAME
            | \FilesystemIterator::CURRENT_AS_FILEINFO 
            | \FilesystemIterator::SKIP_DOTS
        ;
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->path, $flags),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iter as $item) {
            if (is_file($item))
                unlink($item);
            elseif (is_dir($item))
                rmdir($item);
        }
        rmdir($this->path);
    }
    
    public function testSetFilePath()
    {
        $file = new Backend\File($this->path);
        $cache = new Cache('cache', $file);
        $cache->set('foo', 'bar');
        
        $itemFile = "{$this->path}/1cwqkjo-cache/1/k/t/1ktddy0-foo";
        $this->assertTrue(file_exists($itemFile));
        $item = unserialize(file_get_contents($itemFile));
        $this->assertTrue($item instanceof Item);
        $this->assertEquals('bar', $item->value);
    }
    
    public function testFilePermissions()
    {
        $file = new Backend\File($this->path, array('filePerms'=>0600));
        $cache = new Cache('cache', $file);
        $cache->set('foo', 'bar');

        $itemFile = "{$this->path}/1cwqkjo-cache/1/k/t/1ktddy0-foo";
        $perms = fileperms($itemFile) & 0x1FF;
        $this->assertEquals(0600, $perms);
    }
    
    public function testDirPermissions()
    {
        // HACK: use some weird permissions so it's obvious that it has been set
        $file = new Backend\File($this->path, array('dirPerms'=>0707));
        $cache = new Cache('cache', $file);
        $cache->set('foo', 'bar');
        
        $current = "{$this->path}";
        foreach (array("1cwqkjo-cache", "1", "k", "t") as $part) {
            $current .= "/$part";
            $perms = fileperms($current) & 0x1FF;
            $this->assertEquals(0707, $perms);
        }
    }
}
