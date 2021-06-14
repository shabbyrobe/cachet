<?php
namespace Cachet\Test\Dependency;

use Cachet\Dependency\CachedTag;

class CachedTagTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        $this->backend = new \Cachet\Backend\Memory();
        $this->mainCache = new \Cachet\Cache('main', $this->backend);
        
        $this->tagCache = new \Cachet\Cache('tag', $this->backend);
        $this->mainCache->services['tagCache'] = $this->tagCache;
    }
    
    public function testValidWhenTagDoesntExist()
    {
        $this->mainCache->set('foo', 'bar', new CachedTag('tagCache', 'tag'));
        $value = $this->mainCache->get('foo');
        $this->assertEquals('bar', $value);
    }
    
    public function testInvalidWhenTagThatDidntExistGetsSet()
    {
        $this->mainCache->set('foo', 'bar', new CachedTag('tagCache', 'tag'));
        $value = $this->mainCache->get('foo');
        $this->assertEquals('bar', $value);
        
        $this->tagCache->set('tag', 'yep');
        $value = $this->mainCache->get('foo', $found);
        $this->assertFalse($found);
    }
    
    /**
     * @dataProvider dataForTagChanges
     */
    public function testTagChanges($strict, $valid, $oldTagValue, $newTagValue)
    {
        $this->tagCache->set('tag', $oldTagValue);
        $this->mainCache->set('foo', 'bar', new CachedTag('tagCache', 'tag', $strict));
        $value = $this->mainCache->get('foo');
        $this->assertEquals('bar', $value);
        
        $this->tagCache->set('tag', $newTagValue);
        $value = $this->mainCache->get('foo', $found);
        $this->assertEquals($valid, $found);
    }
    
    public function dataForTagChanges()
    {
        return [
            [!!'strict', !'valid',   1, 2],
            [!!'strict', !'valid',   true, false],
            [!!'strict', !'valid',   'foo', 'bar'],
            [!!'strict', !'valid',   [1, 2, 3], [4, 5, 6]],
            [!!'strict', !'valid',   ['foo'=>'bar'], ['foo'=>'baz']],
            [!!'strict', !'valid',   (object)['foo'=>'bar'], (object)['foo'=>'baz']],
            [!!'strict', !'valid',   (object)['foo'=>'bar'], (object)['qux'=>'baz']],
            [!!'strict', !'valid',   "0", true],
            
            [!!'strict', !!'valid',  0, 0],
            [!!'strict', !!'valid',  'foo', 'foo'],
            [!!'strict', !!'valid',  [1, 2, 3], [1, 2, 3]],
            [!!'strict', !!'valid',  (object)['foo'=>'bar'], (object)['foo'=>'bar']],
            [!!'strict', !!'valid',  ['foo'=>'bar'], ['foo'=>'bar']],
            
            [!'strict',  !'valid',   "0", true],
            [!'strict',  !'valid',   ['foo'=>'bar'], ['foo'=>'baz']],
            [!'strict',  !'valid',   (object)['foo'=>'bar'], (object)['foo'=>'baz']],
            
            [!'strict',  !!'valid',  (object)['foo'=>'bar'], (object)['foo'=>'bar']],
            [!'strict',  !!'valid',   ['foo'=>'bar'], ['foo'=>'bar']],
            [!'strict',  !!'valid',  "0", 0],
        ];
    }
}
