<?php
namespace Cachet\Backend\PDO;

class MySQLCounter implements \Cachet\Backend\Counter
{
    public $backend;

    public function __construct(\Cachet\Backend\PDO $backend)
    {
        $this->backend = $backend;
    }

    function increment($cacheId, $key, $by=1)
    {
    }

    function decrement($cacheId, $key, $by=1)
    {
    }
}

