<?php
namespace Cachet\Backend;

use Cachet\Backend;
use Cachet\Item;

class File implements Backend, Iterable
{
    /**
     * How many intermediate directories to insert into the key path
     * I.e. a hashed key of jw3fdmx-pants becomes j/w/3/jw3fdmx-pants
     */
    public $keySplit = 3;
     
    public function __construct($basePath, $fileOptions=[])
    {
        $this->fileUtil = new \Cachet\Util\File($basePath, $fileOptions);
    }
    
    function get($cacheId, $key)
    {
        $data = $this->fileUtil->read($this->getFilePath($cacheId, $key), $found);
        if ($found) {
            return $this->decode($data);
        }
    }
    
    function set(Item $item)
    {
        $filePath = $this->getFilePath($item->cacheId, $item->key);
        $this->fileUtil->write($filePath, serialize($item));
    }
    
    function delete($cacheId, $key)
    {
        $filePath = $this->getFilePath($cacheId, $key);
        $this->fileUtil->delete($filePath);
    }

    function flush($cacheId)
    {
        $filePath = $this->getFilePath($cacheId);
        $this->fileUtil->flush($filePath);
    }
    
    function decode($fileContents)
    {
        return @unserialize($fileContents);
    }
    
    function keys($cacheId)
    {
        $filePath = $this->getFilePath($cacheId);
        $iter = $this->fileUtil->getIterator($filePath);
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
        $filePath = $this->getFilePath($cacheId);
        $iter = $this->fileUtil->getIterator($filePath);
        foreach ($iter as $cacheFile) {
            if (!file_exists($cacheFile))
                continue;
            
            $item = $this->decode(file_get_contents($cacheFile));
            if (!$item)
                throw new \UnexpectedValueException();
            
            yield $item;
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
        
        return $keyPath;
    }
    
    private function createSafeKey($key)
    {
        $safeKey = preg_replace("/[^a-z\d_\-]/", "", strtolower($key));
        $hashedKey = base_convert(abs(crc32($key)), 10, 36).($safeKey ? "-$safeKey" : '');
        return $hashedKey;
    }
}

