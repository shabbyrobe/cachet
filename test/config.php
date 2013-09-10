<?php
$base = realpath(__DIR__.'/../');
spl_autoload_register(function($class) use ($base) {
    if (strpos($class, 'Cachet\Test')===0) {
        require $base.'/test/lib/'.str_replace('\\', '/', substr($class, 11)).'.php';
    }
});

require $base.'/src/Cachet.php';
Cachet::register();

if (!class_exists('PHPUnit_Framework_Exception'))
    require_once 'PHPUnit/Autoload.php';

abstract class CachetTestCase extends \PHPUnit_Framework_TestCase
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
