<?php
namespace Cachet\Backend;

interface Iterator
{
    /**
     * @param string $cacheId
     * @return \Iterator|array
     */
    function keys($cacheId);

    /**
     * @param string $cacheId
     * @return \Iterator|array
     */
    function items($cacheId);
}
