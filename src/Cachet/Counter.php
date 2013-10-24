<?php
namespace Cachet;

interface Counter
{
    function value($key);

    function set($key, $value);

    /**
     * @return int The incremented value
     * @throws UnexpectedValueException when the backend could not increment the key
     */
    function increment($key, $by=1);

    /**
     * @return int The decremented value
     * @throws UnexpectedValueException when the backend could not decrement the key
     */
    function decrement($key, $by=1);
}

