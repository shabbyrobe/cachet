<?php
namespace Cachet\Dependency;

class Dummy implements \Cachet\Dependency
{
    public $valid;
    
    public function __construct($valid)
    {
        $this->valid = $valid;
    }
    
    function valid(\Cachet\Cache $cache, \Cachet\Item $item)
    {
        return $this->valid;
    }
    
    function init(\Cachet\Cache $cache, \Cachet\Item $item)
    {}
}
