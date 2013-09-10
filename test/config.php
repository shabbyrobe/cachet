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

abstract class BackendTestCase extends CachetTestCase
{
    abstract function getBackend();
    
    public function testGetNonexistent()
    {
        $backend = $this->getBackend();
        $item = $backend->get('cache', 'doesNotExist');
        $this->assertNull($item);
    }
    
    public function testSetGet()
    {
        $backend = $this->getBackend();
        $backend->set(new \Cachet\Item('cache', 'key', 'value'));
        
        $item = $backend->get('cache', 'key');
        $this->assertNotNull($item);
        $this->assertTrue($item instanceof \Cachet\Item);
        $this->assertEquals('key', $item->key);
        $this->assertEquals('value', $item->value);
    }
    
    public function testSetDifferentCaches()
    {
        $backend = $this->getBackend();
        $backend->set(new \Cachet\Item('cache1', 'key', 'value1'));
        $backend->set(new \Cachet\Item('cache2', 'key', 'value2'));
        
        $this->assertEquals('value1', $backend->get('cache1', 'key')->value);
        $this->assertEquals('value2', $backend->get('cache2', 'key')->value);
    }
    
    public function testDeleteNonexistent()
    {
        $backend = $this->getBackend();
        $backend->delete('cache', 'doesNotExist');
        
        // assert that we made it this far!
        $this->assertTrue(true);
    }
    
    public function testSetDelete()
    {
        $backend = $this->getBackend();
        $backend->set(new \Cachet\Item('cache', 'key1', 'value'));
        $backend->set(new \Cachet\Item('cache', 'key2', 'value'));
        
        $backend->delete('cache', 'key1');
        $this->assertNull($backend->get('cache', 'key1'));
        $this->assertNotNull($backend->get('cache', 'key2'));
    }
    
    public function testFlush()
    {
        $backend = $this->getBackend();
        $backend->set(new \Cachet\Item('cache1', 'key1', 'value'));
        $backend->set(new \Cachet\Item('cache1', 'key2', 'value'));
        $backend->set(new \Cachet\Item('cache2', 'key1', 'value'));
        
        $backend->flush('cache1');
        $this->assertNull($backend->get('cache1', 'key1'));
        $this->assertNull($backend->get('cache1', 'key2'));
        $this->assertNotNull($backend->get('cache2', 'key1'));
    }
    
    public function testFlushEmpty()
    {
        $backend = $this->getBackend();
        $backend->flush('foo');
        
        // assert that we made it this far!
        $this->assertTrue(true);
    }
    
    public function testKeys()
    {
        $backend = $this->getBackend();
        $backend->set(new \Cachet\Item('cache1', 'key1', 'value1'));
        
        // backends don't care if your item is valid
        $backend->set(new \Cachet\Item('cache1', 'key2', 'value2', new \Cachet\Dependency\Dummy(false)));
        
        $backend->set(new \Cachet\Item('cache1', 'key3', 'value3'));
        $backend->set(new \Cachet\Item('cache2', 'key1', 'value1'));
        
        $keys = $backend->keys('cache1');
        $keys = is_array($keys) ? $keys : iterator_to_array($keys);
        sort($keys);
        $this->assertEquals(["key1", "key2", "key3"], $keys);
    }
    
    public function testItems()
    {
        $backend = $this->getBackend();
        $item1 = new \Cachet\Item('cache1', 'key1', 'value1');
        $backend->set($item1);
        
        // backends don't care if your item is valid
        $item2 = new \Cachet\Item('cache1', 'key2', 'value2', new \Cachet\Dependency\Dummy(false));
        $backend->set($item2);
        
        $item3 = new \Cachet\Item('cache1', 'key3', 'value3');
        $backend->set($item3);
        
        $backend->set(new \Cachet\Item('cache2', 'key1', 'value1'));
        
        $items = $backend->items('cache1');
        $items = is_array($items) ? $items : iterator_to_array($items);
        usort($items, function($a, $b) {
            return strcmp($a->key, $b->key);
        });
        $this->assertEquals([$item1, $item2, $item3], $items);
    }
}

$GLOBALS['settings'] = [
    'redis'=>[
        'server'=>null,
    ],
];

if (file_exists($base.'/.cachettestrc')) {
    foreach (parse_ini_file($base.'/.cachettestrc', !!'sections') as $section=>$items) {
        $GLOBALS['settings'][$section] = array_merge($GLOBALS['settings'][$section], $items);
    }
}
