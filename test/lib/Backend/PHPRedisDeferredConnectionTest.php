<?php
namespace Cachet\Test\Backend;

use Cachet\Backend;
use Cachet\Item;
use Cachet\Dependency;

class PHPRedisDeferredConnectionTest extends PHPRedisTest
{
    public function getBackend()
    {
        return new \Cachet\Backend\PHPRedis($this->getRedisInfo());
    }
    
    protected function getRedisInfo()
    {
        if (!isset($GLOBALS['settings']['redis']) || !$GLOBALS['settings']['redis']['host'])
            $this->markTestSkipped("Please supply a redis host in .cachettestrc");
        
        $info = [
            'host'=>$GLOBALS['settings']['redis']['host'], 
            'port'=>isset($GLOBALS['settings']['redis']['port']) 
                ? $GLOBALS['settings']['redis']['port'] 
                : 6379
            ,
            'database'=>$GLOBALS['settings']['redis']['database'],
        ];
        return $info;
    }
}
