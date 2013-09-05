<?php
namespace Cachet\Test\Dependency;

class TTLTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataForValid
     */
    public function testValid($valid, $ttl, $createdTimestamp, $currentTimestamp)
    {
        $dependency = $this->getMockBuilder('Cachet\Dependency\TTL')
            ->setConstructorArgs(array($ttl))
            ->setMethods(array('getCurrentTime'))
            ->getMock()
        ;
        $cache = $this->getMockBuilder('Cachet\Cache')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        
        $dependency->expects($this->any())->method('getCurrentTime')->will($this->returnValue($currentTimestamp));
        
        $item = new \Cachet\Item('cache', 'foo', 'bar', null, $createdTimestamp);
        
        $dependency->init($cache, $item);
        $this->assertEquals($ttl + $createdTimestamp, $dependency->time);
        
        $this->assertEquals($valid, $dependency->valid($cache, $item));
    }
    
    public function dataForValid()
    {
        return array(
            array(true,   300,  1000,  1000),
            array(true,   300,  1000,  1299),
            
            // even if the timestamp is less than the item creation time, it should
            // still be valid
            array(true,   300,  1000,  999),
            
            array(false,  300,  1000,  1300),
            array(false,  300,  1000,  1301),
            
            array(true,     1,  1000,  1000),
            array(false,    1,  1000,  1001),
            
            // TTL of zero - always valid
            array(true,     0,  1000,  1000),
            array(true,     0,  1000,   999),
            array(true,     0,  1000,  1001),
        );
    }
}
