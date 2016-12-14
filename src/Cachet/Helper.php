<?php
namespace Cachet;

class Helper
{
    public static function hashToInt32($key)
    {
        return hexdec(substr(md5($key), -8));
    }

    public static function formatKey(array $parts)
    {
        $parts = array_filter($parts);
        return implode('/', $parts);
    }

    /**
     * @suppress PhanTypeMismatchArgumentInternal
     */
    public static function getType($arg)
    {
        return is_object($arg) ? get_class($arg) : gettype($arg);
    }
}
