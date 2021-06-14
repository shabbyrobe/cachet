<?php
namespace Cachet\Test\Dependency;

use Cachet\Dependency;
use Cachet\Item;

class CompositeTest extends \PHPUnit\Framework\TestCase
{
    public function testAllModeValid()
    {
        $composite = new Dependency\Composite('all', array(
            new Dependency\Dummy(true),
            new Dependency\Dummy(true),
            new Dependency\Dummy(true),
        ));
        
        $cache = $this->getMockBuilder('Cachet\Cache')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        
        $this->assertTrue($composite->valid($cache, new Item('a', 'b', 'c')));
    }
    
    public function testAllModeInvalidWhenOneInvalid()
    {
        $composite = new Dependency\Composite('all', array(
            new Dependency\Dummy(true),
            new Dependency\Dummy(false),
            new Dependency\Dummy(true),
        ));
        
        $cache = $this->getMockBuilder('Cachet\Cache')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        
        $this->assertFalse($composite->valid($cache, new Item('a', 'b', 'c')));
    }
    
    public function testAllModeInvalidWhenAllInvalid()
    {
        $composite = new Dependency\Composite('all', array(
            new Dependency\Dummy(false),
            new Dependency\Dummy(false),
            new Dependency\Dummy(false),
        ));
        
        $cache = $this->getMockBuilder('Cachet\Cache')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        
        $this->assertFalse($composite->valid($cache, new Item('a', 'b', 'c')));
    }
    
    public function testAnyModeValidWhenAllValid()
    {
        $composite = new Dependency\Composite('any', array(
            new Dependency\Dummy(true),
            new Dependency\Dummy(true),
            new Dependency\Dummy(true),
        ));
        
        $cache = $this->getMockBuilder('Cachet\Cache')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        
        $this->assertTrue($composite->valid($cache, new Item('a', 'b', 'c')));
    }
    
    public function testAnyModeValidWhenOneInvalid()
    {
        $composite = new Dependency\Composite('any', array(
            new Dependency\Dummy(true),
            new Dependency\Dummy(false),
            new Dependency\Dummy(true),
        ));
        
        $cache = $this->getMockBuilder('Cachet\Cache')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        
        $this->assertTrue($composite->valid($cache, new Item('a', 'b', 'c')));
    }
    
    public function testAnyModeInvalidWhenAllInvalid()
    {
        $composite = new Dependency\Composite('any', array(
            new Dependency\Dummy(false),
            new Dependency\Dummy(false),
            new Dependency\Dummy(false),
        ));
        
        $cache = $this->getMockBuilder('Cachet\Cache')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        
        $this->assertFalse($composite->valid($cache, new Item('a', 'b', 'c')));
    }
    
    public function testInit()
    {
        $d1 = $this->getMockBuilder('Cachet\Dependency\Dummy')
            ->setConstructorArgs(array(true))
            ->setMethods(array('init'))
            ->getMock()
        ;
        $d1->expects($this->once())->method('init');
        
        $d2 = $this->getMockBuilder('Cachet\Dependency\Dummy')
            ->setConstructorArgs(array(true))
            ->setMethods(array('init'))
            ->getMock()
        ;
        $d2->expects($this->once())->method('init');
        
        $composite = new Dependency\Composite('any', array($d1, $d2));
        
        $cache = $this->getMockBuilder('Cachet\Cache')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        
        $composite->init($cache, new Item('a', 'b', 'c'));
    }
}
