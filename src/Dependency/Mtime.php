<?php
namespace Cachet\Dependency;

use Cachet\Cache;
use Cachet\Dependency;
use Cachet\Item;

/**
 * File-based Cache Dependency
 *
 * Supports invalidating items cached based on a file modification time.
 *
 * Example use::
 *
 *     $fileName = '/tmp/file.txt';
 *
 *     // set the mtime to now and create if it doesn't exist
 *     touch($fileName);
 *
 *     $dcache = new Cache(new BlahBlahBackend());
 *     $dcache->set('foo', 'bar', new Dependency\File($fileName));
 *
 *     $value = $dcache->get('foo'); // returns 'bar'
 *
 *     // resets the mtime which will invalidate the cache item
 *     touch($fileName);
 *
 *     $value = $dcache->get('foo'); // returns null
 *
 * @author Blake Williams
 */
class Mtime implements Dependency
{
    public $file;
    public $time;

    public function __construct($file)
    {
        $this->file = $file;
    }

    function valid(Cache $cache, Item $item)
    {
        if (!file_exists($this->file))
            return false;
        return filemtime($this->file) <= $this->time;
    }

    function init(Cache $cache, Item $item)
    {
        if (file_exists($this->file))
            $this->time = filemtime($this->file);
    }
}
