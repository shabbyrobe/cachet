<?php
namespace Cachet\Locker;

use Cachet\Cache;

class File extends \Cachet\Locker
{
    private $path;
    private $files = [];
    private $fileUtil;

    public function __construct($basePath, callable $keyHasher, $fileOptions=[])
    {
        parent::__construct($keyHasher);
        $this->fileUtil = new \Cachet\Util\File($basePath, $fileOptions);
    }

    function acquire(Cache $cache, $key, $block=true)
    {
        $name = $this->getLockKey($cache, $key);
        $this->files[$name] = $file = $this->fileUtil->open($name, "w");

        if ($block) {
            flock($file, LOCK_EX);
            return true;
        } 
        else {
            flock($file, LOCK_EX | LOCK_NB, $wouldBlock);
            return !$wouldBlock;
        }
    }

    function release(Cache $cache, $key)
    {
        $name = $this->getLockKey($cache, $key);
        $handle = $this->files[$name];
        flock($handle, LOCK_UN);
        fclose($handle);
        unset($this->files[$name]);
    }

    function shutdown()
    {
        foreach ($this->files as $file) {
            flock($file, LOCK_UN);
            fclose($file);
        }
        $this->files = [];
    }
}

