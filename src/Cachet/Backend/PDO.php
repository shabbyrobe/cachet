<?php
namespace Cachet\Backend;

use Cachet\Backend;
use Cachet\Item;

class PDO implements Backend, Iterable, Counter
{
    private $tables;
    private $engine;
    private $pdo;
    private $params;
    private $connector;
    
    private $counter;

    public function __construct($dbInfo)
    {
        if (is_array($dbInfo)) {
            $this->params = $dbInfo;
        }
        elseif (is_callable($dbInfo)) {
            $this->connector = $dbInfo;
        }
        elseif (is_object($pdo)) {
            $this->pdo = $dbInfo;
        }
        else {
            throw new \InvalidArgumentException();
        }
    }
    
    public function connect()
    {
        if (!$this->pdo) {
            if ($this->params)
                $this->pdo = self::createPDO($this->params);
            elseif ($this->connector)
                $this->pdo = call_user_func($this->connector);
            else {
                throw new \RuntimeException(
                    "You need to pass connection parameters or a connector callback rather than a"
                    ." PDO instance if you want to be able to reconnect"
                );
            }
        }
        
        if (!$this->engine) {
            $this->engine = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
            if ($this->engine != 'mysql' && $this->engine != 'sqlite')
                throw new \RuntimeException("Only works with mysql and sqlite (for now)");
        }

        return $this->pdo;
    }
    
    public function disconnect()
    {
        $this->pdo = null;
    }
    
    /**
     * Creates a Connector from an array of connection parameters.
     * @param array Parameters to use to create the connection
     * @return PDO
     */
    public static function createPDO(array $params)
    {
        $options = $host = $port = $database = $user = $password = null;
        
        foreach ($params as $k=>$v) {
            $k = strtolower($k);
            if (strpos($k, "host")===0 || $k == 'server' || $k == 'sys')
                $host = $v;
            elseif ($k=='port')
                $port = $v;
            elseif ($k=="database" || strpos($k, "db")===0)
                $database = $v;
            elseif ($k[0] == 'p')
                $password = $v;
            elseif ($k[0] == 'u')
                $user = $v;
            elseif ($k=='options')
                $options = $v;
        }
       
        if (!isset($params['dsn'])) {
            $dsn = (isset($params['engine']) ? $params['engine'] : 'mysql').":host={$host};";
            if ($port) $dsn .= "port=".$port.';';
            if (!empty($database)) $dsn .= "dbname={$database};";
        }
        else {
            $dsn = $params['dsn'];
        }
        
        if (!isset($options[\PDO::ATTR_ERRMODE]))
            $options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
        
        return new \PDO($dsn, $user, $password, $options);
    }
    
    function get($cacheId, $key)
    {
        if (!$this->pdo)
            $this->connect();
        
        $tableName = $this->ensureTable($cacheId);
        $keyHash = $this->hashKey($key);
        
        $sql = "SELECT itemData, creationTimestamp FROM `{$tableName}` WHERE keyHash=?";
        $stmt = $this->pdo->prepare($sql);
        if ($stmt->execute(array($keyHash)) === false) {
            throw new \UnexpectedValueException(
                "Cache $cacheId get query failed: ".implode(' ', $stmt->errorInfo())
            );
        }
        
        $record = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $ret = null;
        if ($record) {
            $itemData = unserialize($record['itemData']);
            $itemData[Item::COMPACT_CACHE_ID] = $cacheId;
            $itemData[Item::COMPACT_TIMESTAMP] = $record['creationTimestamp'];
            $ret = Item::uncompact($itemData);
        }
        return $ret;
    }
    
    function set(Item $item)
    {
        if (!$this->pdo)
            $this->connect();
        
        $tableName = $this->ensureTable($item->cacheId);
        
        $expiryTimestamp = null;
        if ($item->dependency && method_exists($item->dependency, 'getExpiryTimestamp'))
            $expiryTimestamp = $item->dependency->getExpiryTimestamp();
        
        $itemData = $item->compact();
        unset($itemData[Item::COMPACT_CACHE_ID]);
        unset($itemData[Item::COMPACT_TIMESTAMP]);
        
        $sql = "
            REPLACE INTO `{$tableName}` (cacheKey, keyHash, itemData, creationTimestamp, expiryTimestamp) 
            VALUES(?, ?, ?, ?, ?)
        ";
        $stmt = $this->pdo->prepare($sql);
        
        $keyHash = $this->hashKey($item->key);
        $params = [$item->key, $keyHash, serialize($itemData), $item->timestamp, $expiryTimestamp];
        if ($stmt->execute($params) === false) {
            throw new \UnexpectedValueException(
                "Cache $cacheId set query failed: ".implode(' ', $stmt->errorInfo())
            );
        }
    }
    
    function delete($cacheId, $key)
    {
        if (!$this->pdo)
            $this->connect();
        
        $tableName = $this->ensureTable($cacheId);
        
        $keyHash = $this->hashKey($key);
        $sql = "DELETE FROM `{$tableName}` WHERE keyHash=?";
        $stmt = $this->pdo->prepare($sql);
        
        if ($stmt->execute(array($keyHash)) === false) {
            throw new \UnexpectedValueException(
                "Cache $cacheId delete query failed: ".implode(' ', $stmt->errorInfo())
            );
        }
    }
    
    function flush($cacheId)
    {
        if (!$this->pdo)
            $this->connect();
        
        $tableName = $this->ensureTable($cacheId);
        
        if ($this->engine == 'mysql')
            $result = $this->pdo->exec("TRUNCATE TABLE `{$tableName}`");
        else
            $result = $this->pdo->exec("DELETE FROM `{$tableName}`");
        
        if ($result === false) {
            throw new \UnexpectedValueException(
                "Cache $cacheId flush query failed: ".implode(' ', $this->pdo->errorInfo())
            );
        }
    }
    
    function keys($cacheId)
    {
        if (!$this->pdo)
            $this->connect();
        
        $tableName = $this->ensureTable($cacheId);
        
        $stmt = $this->pdo->query("SELECT cacheKey FROM `{$tableName}` ORDER BY cacheKey");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
    }
    
    function items($cacheId)
    {
        foreach ($this->keys($cacheId) as $key) {
            $item = $this->get($cacheId, $key);
            if ($item)
                yield $item;
        }
    }
    
    function increment($cacheId, $key, $by=1)
    {
        if (!isset($this->counter)) {
            $this->counter = $this->createEngineCounter();
        }
        return $this->counter->increment($cacheId, $key, $by);
    }

    function decrement($cacheId, $key, $by=1)
    {
        if (!isset($this->counter)) {
            $this->counter = $this->createEngineCounter();
        }
        return $this->counter->decrement($cacheId, $key, $by);
    }

    private function createEngineCounter()
    {
        switch ($this->engine) {
            case 'mysql' :  return new PDO\MySQLCounter ($this);  break;
            case 'sqlite':  return new PDO\SQLiteCounter($this);  break;

            default:
                throw new \RuntimeException("PDO engine $engine does not support counters");
        }
    }

    private function hashKey($key)
    {
        return hash('sha256', $key);
    }
    
    private function ensureTable($cacheId)
    {
        if (!isset($this->tables[$cacheId])) {
            $tableName = 'cache_'.preg_replace('/[^A-z\d_]/', '', $cacheId);
            $this->tables[$cacheId] = $tableName;
            
            if ($this->engine == 'sqlite') {
                $result = $this->pdo->exec("
                    CREATE TABLE IF NOT EXISTS `{$tableName}` (
                        id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                        keyHash TEXT,
                        cacheKey TEXT,
                        itemData BLOB,
                        creationTimestamp INTEGER,
                        expiryTimestamp INTEGER NULL,
                        UNIQUE (keyHash)
                    );
                ");
                
                if ($result === false) {
                    throw new \UnexpectedValueException(
                        "Cache $cacheId create table query failed: ".implode(' ', $this->pdo->errorInfo())
                    );
                }
            }
        }
        
        return $this->tables[$cacheId];
    }
    
    public static function createMySQLTable($pdo, $cacheId)
    {
        $tableName = 'cache_'.preg_replace('/[^A-z\d_]/', '', $cacheId);
        
        $sql = "
            CREATE TABLE IF NOT EXISTS `{$tableName}` (
                id BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                keyHash VARCHAR(64),
                cacheKey TEXT,
                itemData LONGBLOB,
                creationTimestamp INT,
                expiryTimestamp INT NULL,
                UNIQUE (keyHash)
            ) ENGINE=InnoDB;
        ";
        $pdo->exec($sql);
    }
}
