<?php
namespace Cachet\Backend;

interface Counter
{
    /**
     * @return int The incremented value
     * @throws UnexpectedValueException when the backend could not increment the key
     */
    function increment($cacheId, $key, $by=1);

    /**
     * @return int The decremented value
     * @throws UnexpectedValueException when the backend could not decrement the key
     */
    function decrement($cacheId, $key, $by=1);
}

