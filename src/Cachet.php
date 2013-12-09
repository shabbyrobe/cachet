<?php
class Cachet
{
    public static $classes = array(
        'Cachet'=>'Cachet.php',
        'Cachet\Backend'=>'Cachet/Backend.php',
        'Cachet\Backend\APC'=>'Cachet/Backend/APC.php',
        'Cachet\Backend\Cascading'=>'Cachet/Backend/Cascading.php',
        'Cachet\Backend\File'=>'Cachet/Backend/File.php',
        'Cachet\Backend\Iterable'=>'Cachet/Backend/Iterable.php',
        'Cachet\Backend\IterationAdapter'=>'Cachet/Backend/IterationAdapter.php',
        'Cachet\Backend\Iterator\Fetching'=>'Cachet/Backend/Iterator/Fetching.php',
        'Cachet\Backend\Memcache'=>'Cachet/Backend/Memcache.php',
        'Cachet\Backend\Memory'=>'Cachet/Backend/Memory.php',
        'Cachet\Backend\PDO'=>'Cachet/Backend/PDO.php',
        'Cachet\Backend\PHPRedis'=>'Cachet/Backend/PHPRedis.php',
        'Cachet\Backend\Session'=>'Cachet/Backend/Session.php',
        'Cachet\Backend\Sharding'=>'Cachet/Backend/Sharding.php',
        'Cachet\Backend\XCache'=>'Cachet/Backend/XCache.php',
        'Cachet\Cache'=>'Cachet/Cache.php',
        'Cachet\Connector\Memcache'=>'Cachet/Connector/Memcache.php',
        'Cachet\Connector\PDO'=>'Cachet/Connector/PDO.php',
        'Cachet\Connector\PHPRedis'=>'Cachet/Connector/PHPRedis.php',
        'Cachet\Counter'=>'Cachet/Counter.php',
        'Cachet\Counter\APC'=>'Cachet/Counter/APC.php',
        'Cachet\Counter\Memcache'=>'Cachet/Counter/Memcache.php',
        'Cachet\Counter\PDOMySQL'=>'Cachet/Counter/PDOMySQL.php',
        'Cachet\Counter\PDOSQLite'=>'Cachet/Counter/PDOSQLite.php',
        'Cachet\Counter\PHPRedis'=>'Cachet/Counter/PHPRedis.php',
        'Cachet\Counter\SafeCache'=>'Cachet/Counter/SafeCache.php',
        'Cachet\Counter\XCache'=>'Cachet/Counter/XCache.php',
        'Cachet\Dependency'=>'Cachet/Dependency.php',
        'Cachet\Dependency\CachedTag'=>'Cachet/Dependency/CachedTag.php',
        'Cachet\Dependency\Composite'=>'Cachet/Dependency/Composite.php',
        'Cachet\Dependency\Dummy'=>'Cachet/Dependency/Dummy.php',
        'Cachet\Dependency\Mtime'=>'Cachet/Dependency/Mtime.php',
        'Cachet\Dependency\Permanent'=>'Cachet/Dependency/Permanent.php',
        'Cachet\Dependency\TTL'=>'Cachet/Dependency/TTL.php',
        'Cachet\Dependency\Time'=>'Cachet/Dependency/Time.php',
        'Cachet\Helper'=>'Cachet/Helper.php',
        'Cachet\Item'=>'Cachet/Item.php',
        'Cachet\Locker'=>'Cachet/Locker.php',
        'Cachet\Locker\File'=>'Cachet/Locker/File.php',
        'Cachet\Locker\SQLite'=>'Cachet/Locker/SQLite.php',
        'Cachet\Locker\Semaphore'=>'Cachet/Locker/Semaphore.php',
        'Cachet\SessionHandler'=>'Cachet/SessionHandler.php',
        'Cachet\Util\BatchingMapIterator'=>'Cachet/Util/BatchingMapIterator.php',
        'Cachet\Util\File'=>'Cachet/Util/File.php',
        'Cachet\Util\MapIterator'=>'Cachet/Util/MapIterator.php',
        'Cachet\Util\ReindexingIterator'=>'Cachet/Util/ReindexingIterator.php',
        'Cachet\Util\WhileIterator'=>'Cachet/Util/WhileIterator.php',
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
