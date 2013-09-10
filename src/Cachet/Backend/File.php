<?php
namespace Cachet\Backend;

use Cachet\Backend;
use Cachet\Dependency;
use Cachet\Item;

class File implements Backend, Iteration\Iterable
{
    public $user;
    public $group;
    public $filePerms;
    public $dirPerms;
    public $basePath;
    
    /**
     * How many intermediate directories to insert into the key path
     * I.e. a hashed key of jw3fdmx-pants becomes j/w/3/jw3fdmx-pants
     */
    public $keySplit = 3;
    
    /**
     * Dangerous - can break setting when happening concurrently.
     */
    public $purgeEmptyDirs = false;
    
    public function __construct($basePath, $options=array())
    {
        if (!is_writable($basePath))
            throw new \InvalidArgumentException("Base path must be writable");
        
        $this->basePath = $basePath;
        $this->user = isset($options['user']) ? $options['user'] : null;
        $this->group = isset($options['group']) ? $options['group'] : null;
        $this->filePerms = isset($options['filePerms']) ? $options['filePerms'] : null;
        $this->dirPerms = isset($options['dirPerms']) ? $options['dirPerms'] : null;
        
        if ($unknown = array_diff(array_keys($options), array('user', 'group', 'filePerms', 'dirPerms')))
            throw new \InvalidArgumentException("Unknown options: ".implode(', ', $unknown));
    }
    
    function get($cacheId, $key)
    {
        list ($fileName, $fullFileName) = $this->getFilePath($cacheId, $key);
        
        if (file_exists($fullFileName)) {
            return $this->decode(file_get_contents($fullFileName));
        }
    }
    
    function set(Item $item)
    {
        list ($fileName, $fullFileName) = $this->getFilePath($item->cacheId, $item->key);
        
        $fullDir = dirname($fullFileName);
        if (!file_exists($fullDir)) {
            $dir = dirname($fileName);
            $parts = preg_split('~[/\\\\]~', $dir, null, PREG_SPLIT_NO_EMPTY);
            $current = $this->basePath;
            
            // can't use mkdir recursive mode because it is affected by umask
            foreach ($parts as $part) {
                $current .= "/$part";
                if (!file_exists($current)) {
                    mkdir($current);
                    $this->applySettings($current, false);
                }
            }
        }

        file_put_contents($fullFileName, serialize($item));
        $this->applySettings($fullFileName, true);
    }
    
    function delete($cacheId, $key)
    {
        list ($fileName, $fullFileName) = $this->getFilePath($cacheId, $key);
        if (file_exists($fullFileName))
            unlink($fullFileName);
    }
    
    function flush($cacheId)
    {
        $iter = $this->getIterator($cacheId);
        
        $lastDir = null;
        foreach ($iter as $item) {
            if ($item->isFile()) {
                $currentDir = dirname($item);
                unlink($item);
            }
            else {
                $currentDir = $item.'';
            }
            
            if ($this->purgeEmptyDirs) {
                if ($lastDir != null && $currentDir != $lastDir) {
                    if (count(glob("$lastDir/*")) === 0) {
                        rmdir($lastDir);
                    }
                }
                $lastDir = $currentDir;
            }
        }
        if ($this->purgeEmptyDirs && $lastDir && count(glob("$lastDir/*")) === 0) {
            rmdir($lastDir);
        }
    }
    
    function decode($fileContents)
    {
        return @unserialize($fileContents);
    }
    
    function keys($cacheId)
    {
        $iter = $this->getIterator($cacheId);
        foreach ($iter as $cacheFile) {
            if (!file_exists($cacheFile))
                continue;
            
            $item = $this->decode(file_get_contents($cacheFile));
            if (!$item)
                throw new \UnexpectedValueException();
            
            yield $item->key;
        }
    }
    
    function items($cacheId)
    {
        $iter = $this->getIterator($cacheId);
        foreach ($iter as $cacheFile) {
            if (!file_exists($cacheFile))
                continue;
            
            $item = $this->decode(file_get_contents($cacheFile));
            if (!$item)
                throw new \UnexpectedValueException();
            
            yield $item;
        }
    }
    
    private function getIterator($cacheId, $iteratorMode=\RecursiveIteratorIterator::LEAVES_ONLY)
    {
        $hashedCache = $this->createSafeKey($cacheId);
        $path = "{$this->basePath}/$hashedCache";
        if (!file_exists($path))
            return;
        
        list ($fileName, $fullFileName) = $this->getFilePath($cacheId);
        $dir = new \RecursiveDirectoryIterator(
            $path,
            \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS
        );
        $iter = new \RecursiveIteratorIterator($dir, $iteratorMode);
        return $iter;
    }
    
    private function applySettings($name, $file=true)
    {
        if ($this->user)
            chown($name, $this->user);
        if ($this->group)
            chown($name, $this->group);
        
        if ($file) {
            if ($this->filePerms)
                chmod($name, $this->filePerms);
        }
        else {
            if ($this->dirPerms)
                chmod($name, $this->dirPerms);
        }
    }
    
    private function getFilePath($cacheId, $key=null)
    {
        $hashedCache = $this->createSafeKey($cacheId);
        $keyPath = "$hashedCache/";
        
        if ($key) {
            $hashedKey = $this->createSafeKey($key);
            
            for ($i=0; $i<$this->keySplit; $i++) {
                $keyPath .= "{$hashedKey[$i]}/";
            }
            $keyPath .= $hashedKey;
        }
        
        return array($keyPath, "{$this->basePath}/$keyPath");
    }
    
    private function createSafeKey($key)
    {
        $safeKey = preg_replace("/[^a-z\d_\-]/", "", strtolower($key));
        $hashedKey = base_convert(crc32($key), 10, 36).($safeKey ? "-$safeKey" : '');
        return $hashedKey;
    }
}
