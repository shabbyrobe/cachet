<?php
namespace Cachet\Util;

class BatchingMapIterator implements \Iterator
{
    private $batchIterator;

    private $rewind;
    private $batcher;

    public function __construct(callable $rewind, callable $batcher)
    {
        $this->rewind = $rewind;
        $this->batcher = $batcher;
    }

    public function valid()
    {
        return $this->batchIterator && $this->batchIterator->valid();
    }

    public function next()
    {
        $this->batchIterator->next();
        if (!$this->batchIterator->valid()) {
            $this->nextBatch();
        }
    }

    public function rewind()
    {
        $rewind = $this->rewind;
        $rewind();
        $this->nextBatch();
    }

    public function current()
    {
        return $this->batchIterator->current();
    }
    
    public function key()
    {
        return $this->batchIterator->key();
    }
    
    private function nextBatch()
    {
        $batcher = $this->batcher;
        $batch = $batcher();
        $this->batchIterator = $batch ? new \ArrayIterator($batch) : null; 
    }
}


