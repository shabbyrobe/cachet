<?php
namespace Cachet\Util;

/**
 * This is useful for situations where you have a service that will yield batches
 * of items in pages, but you want to consider them one by one in a foreach loop.
 */
class BatchingMapIterator implements \Iterator
{
    private $batchIterator;

    private $rewind;
    private $batcher;
    private $useKeys;
    private $index;

    /**
     * If $useKeys is false, the keys in each batch will be ignored. This is so 
     * that you can simply return raw arrays from the batch without worrying about
     * whether numeric keys from a later batch will clobber an earlier batch.
     */
    public function __construct(callable $rewind, callable $batcher, $useKeys=false)
    {
        $this->rewind = $rewind;
        $this->batcher = $batcher;
        $this->useKeys = $useKeys;
    }

    public function valid()
    {
        return $this->batchIterator && $this->batchIterator->valid();
    }

    public function next()
    {
        $this->batchIterator->next();
        ++$this->index;
        if (!$this->batchIterator->valid()) {
            $this->nextBatch();
        }
    }

    public function rewind()
    {
        $rewind = $this->rewind;
        $rewind();
        $this->index = 0;
        $this->nextBatch();
    }

    public function current()
    {
        return $this->batchIterator->current();
    }
    
    public function key()
    {
        if ($this->useKeys)
            return $this->batchIterator->key();
        else
            return $this->index;
    }
    
    private function nextBatch()
    {
        $batcher = $this->batcher;
        $batch = $batcher();
        $this->batchIterator = $batch ? new \ArrayIterator($batch) : null; 
    }
}
