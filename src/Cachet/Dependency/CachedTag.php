<?php
namespace Cachet\Dependency;

use Cachet\Cache;
use Cachet\Dependency;
use Cachet\Item;

class CachedTag implements Dependency
{
    public $tagValue;
    public $tag;
    public $cacheService;
    
    function __construct($cacheService, $tag)
    {
        $this->cacheService = $cacheService;
        $this->tag = $tag;
    }
    
    function init(Cache $cache, Item $item)
    {
        if (!isset($cache->services[$this->cacheService]))
            throw new \UnexpectedValueException("CachedTag dependency: missing cache service {$this->cacheService}");
        if (!$cache->services[$this->cacheService] instanceof Cache)
            throw new \UnexpectedValueException("CachedTag cache service {$this->cacheService} was not an instance of Cachet\Cache");
        
        $this->tagValue = $cache->services[$this->cacheService]->get($this->tag);
    }
    
    function valid(Cache $cache, Item $item)
    {
        if (!isset($cache->services[$this->cacheService]) || !$cache->services[$this->cacheService] instanceof Cache)
            return false;
           
        $currentValue = $cache->services[$this->cacheService]->get($this->tag);
        
        return $currentValue === $this->tagValue;
    }
}
