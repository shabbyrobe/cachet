<?php
namespace Cachet\Locker;

use Cachet\Cache;

class File extends \Cachet\Locker
{
    private $path;
    private $fileLimit;
    private $files = [];

    public $fileNameFormat = "<path>/<key>.lock";

    public function __construct($path, callable $keyHasher=null)
    {
        parent::__construct($keyHasher);

        $this->path = $path;
        if (!is_writable($path))
            throw new \InvalidArgumentException("Path $path is not writable");
    }

    function acquire(Cache $cache, $key)
    {
        $name = $this->getFileName($cache, $key);
        $dir = dirname($name);
        if (

        $this->files[$name] = $file = fopen($name, 'w');
        flock($file, LOCK_EX);
    }

    function release(Cache $cache, $key)
    {
        $name = $this->getFileName($cache, $key);
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

    private function getFileName($cache, $key)
    {
        return strtr($this->fileNameFormat, [
            "<key>"=>preg_replace('/[^A-z0-9]/', '-', $this->getLockKey($cache, $key)),
            "<path>"=>$this->path,
        ]);
    }
}

