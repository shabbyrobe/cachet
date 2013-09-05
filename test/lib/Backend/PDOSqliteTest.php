<?php
namespace Cachet\Test\Backend;

use Cachet\Backend;
use Cachet\Dependency;
use Cachet\Item;
use Cachet\Cache;

class PDOSqliteTest extends \CachetTestCase
{
    /**
     * @dataProvider dataValidValues
     */
    public function testSetGet($value)
    {
        $pdo = new Backend\PDO(array('dsn'=>'sqlite::memory:'));
        $cache = new Cache('cache', $pdo);
        
        $cache->set('foo', $value);
        $result = $cache->get('foo', $found);
        $this->assertEquals($value, $result);
        $this->assertTrue($found);
    }
    
    /**
     * @dataProvider dataValidValues
     */
    public function testSetDelete($value)
    {
        $pdo = new Backend\PDO(array('dsn'=>'sqlite::memory:'));
        $cache = new Cache('cache', $pdo);
        
        $cache->set('foo', $value);
        $this->assertTrue($cache->has('foo'));
        $cache->delete('foo');
        $this->assertFalse($cache->has('foo'));
    }
    
    public function testFlush()
    {
        $pdo = new Backend\PDO(array('dsn'=>'sqlite::memory:'));
        $cache = new Cache('cache', $pdo);
        
        $cache->set('foo', 'yep');
        $cache->set('bar', 'yep');
        $this->assertTrue($cache->has('foo'));
        $this->assertTrue($cache->has('bar'));
        
        $cache->flush();
        $this->assertFalse($cache->has('foo'));
        $this->assertFalse($cache->has('bar'));
    }
}
