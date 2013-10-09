<?php
namespace Cachet\Util;

class Hash
{
    public static function mdhack($key)
    {
        return hexdec(substr(md5($key), -8));
    }

    public static function reallyShit($key)
    {
        $key = (string) $key;
        $len = strlen($key);
        for ($hash = $i = 0; $i < $len; ++$i) {
            $hash += ord($key[$i]);
            if ($hash >= PHP_INT_MAX)
                $hash = ~PHP_INT_MAX + ($hash - PHP_INT_MAX);
        }
        return $hash;
    }

    public static function jenkinsOneAtATime($key)
    {   
        $key = (string) $key;
        $len = strlen($key);
        for ($hash = $i = 0; $i < $len; ++$i) {
            $hash += ord($key[$i]);
            $hash = ($hash + ($hash << 10)) & 0xFFFFFFFF;
            $hash ^= (($hash >> 6)  & 0x3FFFFFF);
        }   
        $hash = ($hash + ($hash << 3)) & 0xFFFFFFFF;
        $hash ^= (($hash >> 11) & 0x1FFFFF);
        $hash = ($hash + ($hash << 15)) & 0xFFFFFFFF;
        return $hash;
    }
}

