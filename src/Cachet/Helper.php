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
}

