<?php
$base = realpath(__DIR__.'/../');
define("BASE_PATH", $base);

require BASE_PATH.'/vendor/autoload.php';

$GLOBALS['settings'] = [
    'redis'=>[
        'host'=>'127.0.0.1',
        'port'=>6379,
        'database'=>15,
    ],
    'memcached'=>[
        'host'=>'127.0.0.1',
        'port'=>11211,
    ],
    'mysql'=>[
        'host'=>'127.0.0.1',
        'port'=>3306,
    ],
];

if (getenv('CACHET_CONFIG')) {
    $cachetConfigFile = getenv('CACHET_CONFIG');
    if (!file_exists($cachetConfigFile)) {
        throw new RuntimeException("missing config file $cachetConfigFile");
    }
} else {
    $cachetConfigFile = $base.'/.cachettestrc';
}

if (file_exists($cachetConfigFile)) {
    foreach (parse_ini_file($cachetConfigFile, !!'sections') as $section=>$items) {
        $GLOBALS['settings'][$section] = array_merge($GLOBALS['settings'][$section], $items);
    }
}

abstract class CachetTestCase extends \PHPUnit\Framework\TestCase
{
    public function dataValidValues()
    {
        return array(
            array(1),
            array(0),
            array(false),
            array(null),
            array("test"),
            array("0"),
            array(""),
            array(1.111),
            array(array()),
            array(array(1, 2, 3)),
            array(array("foo"=>"bar")),
            array((object) array("foo"=>"bar")),
            array(new \stdClass),
        );
    }
}

function skip_test($namespace, $class, $message)
{
    eval(
        "namespace $namespace { class $class extends \PHPUnit\Framework\TestCase { ".
        "function testDummy() { ".
        "\$this->markTestSkipped(\"".addslashes($message)."\"); ".
        "} } }"
    );
}

function is_server_listening($host, $port)
{
    $sock = @fsockopen($host, $port, $errno, $errstr, 1);
    if ($sock === false) {
        return false; 
    }
    else {
        fclose($sock);
        return true;
    }
}

function memcached_create_testing()
{
    if (!extension_loaded('memcached'))
        throw new \Exception("memcached extension not found");
    
    if (!isset($GLOBALS['settings']['memcached']) || !$GLOBALS['settings']['redis']['port'])
        throw new \Exception("Please supply a memcached host in .cachettestrc");

    $memcached = new \Memcached();
    $memcached->addServer(
        $GLOBALS['settings']['memcached']['host'], 
        $GLOBALS['settings']['memcached']['port']
    );
    return $memcached;
}

function redis_create_testing()
{
    if (!extension_loaded('redis'))
        throw new \Exception("phpredis extension not found");
    
    if (!isset($GLOBALS['settings']['redis']) || !$GLOBALS['settings']['redis']['host'])
        throw new \Exception("Please supply a redis host in .cachettestrc");
    
    $redisListening = is_server_listening(
        $GLOBALS['settings']['redis']['host'], 
        $GLOBALS['settings']['redis']['port']
    );
    if (!$redisListening)
        throw new \Exception("Redis server not running");
    
    $redis = new \Redis();
    $redis->connect(
        $GLOBALS['settings']['redis']['host'], 
        isset($GLOBALS['settings']['redis']['port']) ? $GLOBALS['settings']['redis']['port'] : 6379
    );
    $redis->select($GLOBALS['settings']['redis']['database']);
    return $redis;
}

function pdo_mysql_tests_valid($namespace, $testClass)
{
    if (!extension_loaded('pdo') || !extension_loaded('pdo_mysql')) {
        skip_test($namespace, $testClass, 'PDO MySQL extension not loaded');
    }
    elseif (!is_server_listening(
        $GLOBALS['settings']['mysql']['host'], 
        $GLOBALS['settings']['mysql']['port']
    )) {
        skip_test($namespace, $testClass, 'MySQL server not listening');
    }
    elseif (!isset($GLOBALS['settings']['mysql']['db'])) {
        skip_test($namespace, $testClass, "Please set 'db' in the 'mysql' section of .cachettestrc");
    }
    else {
        return true;
    }
}

function throw_on_error()
{
	static $set=false;
	if (!$set) {
		set_error_handler(function ($errno, $errstr, $errfile, $errline) {
			$reporting = error_reporting();
			if ($reporting > 0 && ($reporting & $errno)) {
				throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
			}
		});
		$set = true;
	}
}

function tryget(&$var)
{
    if (isset($var))
        return $var;
    else
        return null;
}

function ends_with($str, $test)
{
	$len = strlen($test);
	
	// can't do ! with DirectoryIterator!!??
	if (!$len || $str == false)
		return false;
	return substr_compare($str, $test, -$len, $len) === 0;
}

