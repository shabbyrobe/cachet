<?php
namespace Cachet\Dependency;

use Cachet\Cache;
use Cachet\Dependency;
use Cachet\Item;

class Composite implements Dependency
{
    const MODE_ALL = 'all';
    const MODE_ANY = 'any';

    public $dependencies = array();

    private $all = false;

    public function __construct($mode, $dependencies)
    {
        $this->all = $mode == self::MODE_ALL;

        if (count($dependencies) < 2) {
            throw new \InvalidArgumentException(
                "There's no point using a Composite dependency with less than 2 dependencies!"
            );
        }

        $this->dependencies = $dependencies;
    }

    function valid(Cache $cache, Item $item)
    {
        foreach ($this->dependencies as $dep) {
            $valid = $dep->valid($cache, $item);
            if (($this->all && !$valid) || (!$this->all && $valid)) {
                return !$this->all;
            }
        }
        return $this->all;
    }

    function init(Cache $cache, Item $item)
    {
        foreach ($this->dependencies as $dep) {
            $dep->init($cache, $item);
        }
    }
}
