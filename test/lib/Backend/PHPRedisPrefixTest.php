<?php
namespace Cachet\Test\Backend;

if (!extension_loaded('redis')) {
    skip_test(__NAMESPACE__, "PHPRedisPrefixTest", "Redis extension not loaded");
}
elseif (!is_server_listening(
    $GLOBALS['settings']['redis']['host'], 
    $GLOBALS['settings']['redis']['port']
)) {
    skip_test(__NAMESPACE__, "PHPRedisPrefixTest", "Redis server not listening");
}
else {
    /**
     * @group backend
     */
    class PHPRedisPrefixTest extends PHPRedisTest
    {
        public function getBackend()
        {
            $this->backendPrefix = 'prefix/';
            return new \Cachet\Backend\PHPRedis($this->redis, 'prefix');
        }
    }
}

