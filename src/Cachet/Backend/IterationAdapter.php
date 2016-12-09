<?php
namespace Cachet\Backend;

use Cachet\Item;
use Cachet\Backend;

/**
 * If your cache backend doesn't support iteration, this allows a second
 * backend that does support iteration to be used for the keys.
 *
 * This can slow down cache set and delete operations significantly, but has
 * no bearing on get operations.
 */
abstract class IterationAdapter implements Backend, Iterator
{
    protected $keyBackend;

    public function iterable()
    {
        return !!$this->keyBackend;
    }

    public function setKeyBackend(Iterator $backend)
    {
        if ($backend instanceof static) {
            throw new \InvalidArgumentException("Key backend must not be an iteration adapter backend");
        }
        $this->keyBackend = $backend;
    }

    function keys($cacheId)
    {
        if (!$this->keyBackend) {
            throw new \RuntimeException("Please call setKeyBackend() if you wish to iterate by key");
        }
        return new \Cachet\Util\MapIterator($this->keyBackend->items($cacheId), function($item) {
            return $item->value;
        });
    }

    function items($cacheId)
    {
        $fetching = new Iterator\Fetching($cacheId, $this->keys($cacheId), $this);
        return new \CallbackFilterIterator($fetching, function($current) {
            return $current instanceof Item;
        });
    }

    function set(Item $item)
    {
        $this->setInStore($item);

        if ($this->keyBackend) {
            $keyItem = clone $item;
            $keyItem->value = $item->key;

            // keys should only be considered invalid when there is no corresponding value
            $keyItem->dependency = null;

            $this->keyBackend->set($keyItem);
        }
    }

    function delete($cacheId, $key)
    {
        if ($this->keyBackend) {
            $this->keyBackend->delete($cacheId, $key);
        }
        $this->deleteFromStore($cacheId, $key);
    }

    function flush($cacheId)
    {
        // the keys in the backend will probably be needed to flush the store.
        // this is not ideal - a better solution is needed.
        $this->flushStore($cacheId);

        if ($this->keyBackend) {
            $this->keyBackend->flush($cacheId);
        }
    }

    protected abstract function setInStore(Item $item);
    protected abstract function deleteFromStore($cacheId, $key);
    protected abstract function flushStore($cacheId);
}
