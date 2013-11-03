<?php
namespace Cachet;

class Helper
{
    public static function hashMDHack($key)
    {
        return hexdec(substr(md5($key), -8));
    }

    public static function formatKey($parts)
    {
        $parts = array_filter($parts);
        return implode('/', $parts);
    }

    public static function getType($arg)
    {
        return is_object($input) ? get_class($input) : gettype($input);
    }
}
