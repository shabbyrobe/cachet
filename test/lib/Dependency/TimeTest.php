<?php
namespace Cachet\Test\Dependency;

class TimeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider dataForValid
     */
    public function testValid($valid, $currentTimestamp, $validUntil)
    {
        $dependency = $this->getMockBuilder('Cachet\Dependency\Time')
            ->setConstructorArgs(array($validUntil))
            ->setMethods(array('getCurrentTime'))
            ->getMock()
        ;
        $cache = $this->getMockBuilder('Cachet\Cache')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        
        $dependency->expects($this->any())->method('getCurrentTime')->will($this->returnValue($currentTimestamp));
        
        // created timestamp doesn't matter to this dependency
        $createdTimestamp = 1000;
        $item = new \Cachet\Item('cache', 'foo', 'bar');
        
        // does nothing, call anyway - why not.
        $dependency->init($cache, $item);
        
        $this->assertEquals($valid, $dependency->valid($cache, $item));
    }
    
    public function dataForValid()
    {
        return array(
            array(true,    900,  1000),
            
            array(false,  1000,  1000),
            array(false,  1001,  1000),
        );
    }
}
