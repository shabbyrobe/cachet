<?php
namespace Cachet\Backend;

use Cachet\Backend;
use Cachet\Item;

class Session implements Backend, Iterator
{
    private $baseKey;

    public function __construct($baseKey=null)
    {
        $this->baseKey = $baseKey ?: 'cache';
    }

    /**
     * @param string $cacheId
     * @param string $key
     */
    function get($cacheId, $key)
    {
        if (session_id() === '') {
            session_start();
        }
        $ret = null;
        if (isset($_SESSION[$this->baseKey][$cacheId][$key])) {
            $ret = $_SESSION[$this->baseKey][$cacheId][$key];
        }
        return $ret;
    }

    function set(Item $item)
    {
        if (session_id() === '') {
            session_start();
        }
        $_SESSION[$this->baseKey][$item->cacheId][$item->key] = $item;
    }

    /**
     * @param string $cacheId
     * @param string $key
     * @return void
     */
    function delete($cacheId, $key)
    {
        if (session_id() === '') {
            session_start();
        }
        unset($_SESSION[$this->baseKey][$cacheId][$key]);
    }

    /**
     * @param string $cacheId
     * @return void
     */
    function flush($cacheId)
    {
        if (session_id() === '') {
            session_start();
        }
        unset($_SESSION[$this->baseKey][$cacheId]);
    }

    /**
     * @param string $cacheId
     * @return \Iterator|array
     */
    function keys($cacheId)
    {
        if (session_id() === '') {
            session_start();
        }
        if (isset($_SESSION[$this->baseKey][$cacheId])) {
            return array_keys($_SESSION[$this->baseKey][$cacheId]);
        } else {
            return [];
        }
    }

    /**
     * @param string $cacheId
     * @return \Iterator|array
     */
    function items($cacheId)
    {
        if (session_id() === '') {
            session_start();
        }
        if (isset($_SESSION[$this->baseKey][$cacheId])) {
            return array_values($_SESSION[$this->baseKey][$cacheId]);
        } else {
            return [];
        }
    }
}
