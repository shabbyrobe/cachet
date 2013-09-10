<?php
class Cachet
{
    public static $classes = array(
        'Cachet\Backend'=>'Cachet/Backend.php',
        'Cachet\Backend\APC'=>'Cachet/Backend/APC.php',
        'Cachet\Backend\Cascading'=>'Cachet/Backend/Cascading.php',
        'Cachet\Backend\File'=>'Cachet/Backend/File.php',
        'Cachet\Backend\Memcached'=>'Cachet/Backend/Memcached.php',
        'Cachet\Backend\Memory'=>'Cachet/Backend/Memory.php',
        'Cachet\Backend\PDO'=>'Cachet/Backend/PDO.php',
        'Cachet\Backend\PHPRedis'=>'Cachet/Backend/PHPRedis.php',
        'Cachet\Backend\Session'=>'Cachet/Backend/Session.php',
        'Cachet\Backend\Sharding'=>'Cachet/Backend/Sharding.php',
        'Cachet\Backend\XCache'=>'Cachet/Backend/XCache.php',
        'Cachet\Cache'=>'Cachet/Cache.php',
        'Cachet\Dependency'=>'Cachet/Dependency.php',
        'Cachet\Dependency\CachedTag'=>'Cachet/Dependency/CachedTag.php',
        'Cachet\Dependency\Composite'=>'Cachet/Dependency/Composite.php',
        'Cachet\Dependency\Mtime'=>'Cachet/Dependency/Mtime.php',
        'Cachet\Dependency\Permanent'=>'Cachet/Dependency/Permanent.php',
        'Cachet\Dependency\TTL'=>'Cachet/Dependency/TTL.php',
        'Cachet\Dependency\Time'=>'Cachet/Dependency/Time.php',
        'Cachet\Item'=>'Cachet/Item.php',
    );
    
    public static function register()
    {
        spl_autoload_register(array(__CLASS__, 'load'));
    }

    public static function load($class)
    {
        if (isset(static::$classes[$class])) {
            require __DIR__.'/'.static::$classes[$class];
            return true;
        }
        elseif (strpos($class, 'Cachet\\')===0) {
            require __DIR__.'/'.str_replace('\\', '/', str_replace('../', '', $class)).'.php';
            return true;
        }
    }
}
