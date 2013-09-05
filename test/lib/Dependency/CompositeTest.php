<?php
namespace Cachet\Test\Dependency;

use Cachet\Dependency;
use Cachet\Item;

class CompositeTest extends \PHPUnit_Framework_TestCase
{
    public function testAllModeValid()
    {
        $composite = new Dependency\Composite('all', array(
            new \DummyDependency(true),
            new \DummyDependency(true),
            new \DummyDependency(true),
        ));
        
        $cache = $this->getMockBuilder('Cachet\Cache')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        
        $this->assertTrue($composite->valid($cache, new Item()));
    }
    
    public function testAllModeInvalidWhenOneInvalid()
    {
        $composite = new Dependency\Composite('all', array(
            new \DummyDependency(true),
            new \DummyDependency(false),
            new \DummyDependency(true),
        ));
        
        $cache = $this->getMockBuilder('Cachet\Cache')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        
        $this->assertFalse($composite->valid($cache, new Item()));
    }
    
    public function testAllModeInvalidWhenAllInvalid()
    {
        $composite = new Dependency\Composite('all', array(
            new \DummyDependency(false),
            new \DummyDependency(false),
            new \DummyDependency(false),
        ));
        
        $cache = $this->getMockBuilder('Cachet\Cache')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        
        $this->assertFalse($composite->valid($cache, new Item()));
    }
    
    public function testAnyModeValidWhenAllValid()
    {
        $composite = new Dependency\Composite('any', array(
            new \DummyDependency(true),
            new \DummyDependency(true),
            new \DummyDependency(true),
        ));
        
        $cache = $this->getMockBuilder('Cachet\Cache')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        
        $this->assertTrue($composite->valid($cache, new Item()));
    }
    
    public function testAnyModeValidWhenOneInvalid()
    {
        $composite = new Dependency\Composite('any', array(
            new \DummyDependency(true),
            new \DummyDependency(false),
            new \DummyDependency(true),
        ));
        
        $cache = $this->getMockBuilder('Cachet\Cache')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        
        $this->assertTrue($composite->valid($cache, new Item()));
    }
    
    public function testAnyModeInvalidWhenAllInvalid()
    {
        $composite = new Dependency\Composite('any', array(
            new \DummyDependency(false),
            new \DummyDependency(false),
            new \DummyDependency(false),
        ));
        
        $cache = $this->getMockBuilder('Cachet\Cache')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        
        $this->assertFalse($composite->valid($cache, new Item()));
    }
    
    public function testInit()
    {
        $d1 = $this->getMockBuilder('DummyDependency')
            ->setConstructorArgs(array(true))
            ->setMethods(array('init'))
            ->getMock()
        ;
        $d1->expects($this->once())->method('init');
        
        $d2 = $this->getMockBuilder('DummyDependency')
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
        
        $composite->init($cache, new Item());
    }
}
