<?php
namespace Cachet;

interface Counter
{
    /**
     * @param string $key
     * @return int
     */
    function value($key);

    /**
     * @param string $key
     * @param int $value
     * @return void
     */
    function set($key, $value);

    /**
     * This does not require that a counter already exist - you should be able to call
     * it without calling ``set`` first
     *
     * @param string $key
     * @param int $by
     * @return int The incremented value
     * @throws UnexpectedValueException when the backend could not increment the key
     */
    function increment($key, $by=1);

    /**
     * This does not require that a counter already exist - you should be able to call
     * it without calling ``set`` first
     *
     * @param string $key
     * @param int $by
     * @return int The decremented value
     * @throws UnexpectedValueException when the backend could not decrement the key
     */
    function decrement($key, $by=1);
}
