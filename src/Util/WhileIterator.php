<?php
namespace Cachet\Util;

/**
 * This is useful for turning things like a PDOStatement into a rewindable iterator:
 *
 * $stmt = $pdo->prepare('...');
 * $iter = new WhileIterator(
 *     function() use ($stmt) {
 *         $stmt->execute();
 *     },
 *     function(&$key, &$valid) {
 *         $value = $stmt->fetch(\PDO::FETCH_ASSOC);
 *         $valid = $value !== false;
 *         return $value;
 *     }
 * );
 */
class WhileIterator implements \Iterator
{
    private $current;
    private $index;
    private $key;
    private $valid;

    private $rewind;
    private $source;

    public function __construct(callable $rewind, callable $source)
    {
        $this->rewind = $rewind;
        $this->source = $source;
    }

    public function valid()
    {
        return $this->valid; 
    }

    private function yieldItem()
    {
        $valid = null;
        $key = null;

        $source = $this->source;
        $this->current = $source($key, $valid);

        $this->valid = $valid === null ? $this->current !== null: $valid;
        $this->key = $key === null ? $this->index++ : $key;
    }

    public function next()
    {
        $this->yieldItem();
    }

    public function rewind()
    {
        $this->index = 0;
        $rewind = $this->rewind;
        $rewind();
        $this->yieldItem();
    }

    public function current()
    {
        return $this->current;
    }
    
    public function key()
    {
        return $this->key;
    }
}
