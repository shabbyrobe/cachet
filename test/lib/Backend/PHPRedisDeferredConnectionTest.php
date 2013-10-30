<?php
namespace Cachet\Test\Backend;

use Cachet\Backend;
use Cachet\Item;
use Cachet\Dependency;

if (!extension_loaded('redis')) {
    skip_test(__NAMESPACE__, "PHPRedisDeferredConnectionTest", "Redis extension not loaded");
}
elseif (!is_server_listening(
    $GLOBALS['settings']['redis']['host'], 
    $GLOBALS['settings']['redis']['port']
)) {
    skip_test(__NAMESPACE__, "PHPRedisDeferredConnectionTest", "Redis server not listening");
}
else {
    /**
     * @group backend
     */
    class PHPRedisDeferredConnectionTest extends PHPRedisTest
    {
        public function getBackend()
        {
            return new \Cachet\Backend\PHPRedis($this->getRedisInfo());
        }
        
        protected function getRedisInfo()
        {
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
}

