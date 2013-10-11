<?php
namespace Cachet;

class Item
{
    const COMPACT_CACHE_ID = 0;
    const COMPACT_KEY = 1;
    const COMPACT_VALUE = 2;
    const COMPACT_TIMESTAMP = 3;
    const COMPACT_DEPENDENCY = 4;
    
    public $cacheId;
    public $key;
    public $value;
    public $dependency;
    public $timestamp;
    
    public function __construct(
        $cacheId=null,
        $key=null,
        $value=null,
        $dependency=null,
        $timestamp=null
    ) {
        $this->cacheId = $cacheId;
        $this->key = $key;
        $this->value = $value;
        $this->timestamp = $timestamp ?: time();
        $this->dependency = $dependency;
    }
    
    public function compact()
    {
        $compact = array(
            self::COMPACT_CACHE_ID=>$this->cacheId,
            self::COMPACT_KEY=>$this->key,
            self::COMPACT_VALUE=>$this->value,
            self::COMPACT_TIMESTAMP=>$this->timestamp
        );
        
        if ($this->dependency)
            $compact[self::COMPACT_DEPENDENCY] = $this->dependency;
        
        return $compact;
    }
    
    /**
     * Returns null if the compacted value is not an array with the correct number of
     * elements.
     */
    public static function uncompact($compacted)
    {
        if (!is_array($compacted))
            return null;
        
        // must at least contain cacheId, key, value and timestamp
        $count = count($compacted);
        if ($count < 4)
            return null;
        
        if (!isset($compacted[4]))
            $compacted[] = null;
        
        $item = new static;
        list (
            $item->cacheId,
            $item->key,
            $item->value,
            $item->timestamp,
            $item->dependency
        ) = $compacted;
        return $item;
    }
}
