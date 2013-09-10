<?php
namespace Cachet\Backend;

interface Iterable
{
    function keys($cacheId);
    function items($cacheId);
}
