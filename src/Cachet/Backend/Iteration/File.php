<?php
namespace Cachet\Backend\Iteration;

use Cachet\Backend;

class File extends \IteratorIterator
{
    use ItemKey;
    
    public function __construct($path, Backend\File $backend, $mode)
    {
        $this->setMode($mode);
        $this->backend = $backend;
        $this->path = $path;
        $dir = new \RecursiveDirectoryIterator(
            $path,
            \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS
        );
        $inner = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::LEAVES_ONLY);
        parent::__construct($inner);
    }
    
    protected function getCurrent()
    {
        $current = parent::current();
        if (!$current)
            return;
        
        $item = $this->backend->decode(file_get_contents($current));
        if (!$item)
            throw new \UnexpectedValueException();
        
        return $item;
    }
}
