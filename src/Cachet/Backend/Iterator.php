<?php
namespace Cachet\Backend;

interface Iterator
{
    function keys($cacheId);

    function items($cacheId);
}
