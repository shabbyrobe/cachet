<?php
namespace Cachet\Locker;

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

    /**
     * @param string $cacheId
     * @param string $key
     * @return bool
     */
    function acquire($cacheId, $key, $block=true)
    {
        $name = $this->getLockKey($cacheId, $key);
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

    /**
     * @param string $cacheId
     * @param string $key
     */
    function release($cacheId, $key)
    {
        $name = $this->getLockKey($cacheId, $key);
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
