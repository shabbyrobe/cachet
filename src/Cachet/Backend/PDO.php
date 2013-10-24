<?php
namespace Cachet\Backend;

use Cachet\Backend;
use Cachet\Connector;
use Cachet\Item;

class PDO implements Backend, Iterable
{
    public $connector;

    public $cacheTablePrefix = 'cache_';

    private $tables;

    function __construct($pdo)
    {
        $this->connector = $pdo instanceof Connector\PDO ? $pdo : new Connector\PDO($pdo);
    }

    function get($cacheId, $key)
    {
        $pdo = $this->connector->pdo ?: $this->connector->connect();
        
        $tableName = $this->getTableName($cacheId);
        $keyHash = $this->hashKey($key);
        
        $sql = "SELECT itemData, creationTimestamp FROM `{$tableName}` WHERE keyHash=?";
        $stmt = $pdo->prepare($sql);
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
        $pdo = $this->connector->pdo ?: $this->connector->connect();

        $tableName = $this->getTableName($item->cacheId);
        
        $expiryTimestamp = null;
        if ($item->dependency && method_exists($item->dependency, 'getExpiryTimestamp'))
            $expiryTimestamp = $item->dependency->getExpiryTimestamp();
        
        $itemData = $item->compact();
        unset($itemData[Item::COMPACT_CACHE_ID]);
        unset($itemData[Item::COMPACT_TIMESTAMP]);
        
        $sql = "
            REPLACE INTO `{$tableName}` (
                cacheKey, keyHash, itemData, creationTimestamp, expiryTimestamp
            )
            VALUES(?, ?, ?, ?, ?)
        ";
        $stmt = $pdo->prepare($sql);
        
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
        $pdo = $this->connector->pdo ?: $this->connector->connect();
        
        $tableName = $this->getTableName($cacheId);
        
        $keyHash = $this->hashKey($key);
        $sql = "DELETE FROM `{$tableName}` WHERE keyHash=?";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute(array($keyHash)) === false) {
            throw new \UnexpectedValueException(
                "Cache $cacheId delete query failed: ".implode(' ', $stmt->errorInfo())
            );
        }
    }
    
    function flush($cacheId)
    {
        $pdo = $this->connector->pdo ?: $this->connector->connect();
        
        $tableName = $this->getTableName($cacheId);
        
        if ($this->connector->engine == 'mysql')
            $result = $pdo->exec("TRUNCATE TABLE `{$tableName}`");
        else
            $result = $pdo->exec("DELETE FROM `{$tableName}`");
        
        if ($result === false) {
            throw new \UnexpectedValueException(
                "Cache $cacheId flush query failed: ".implode(' ', $pdo->errorInfo())
            );
        }
    }
    
    function keys($cacheId)
    {
        $pdo = $this->connector->pdo ?: $this->connector->connect();
 
        $tableName = $this->getTableName($cacheId);
        $stmt = $pdo->query("SELECT cacheKey FROM `{$tableName}` ORDER BY cacheKey");
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
    
    private function hashKey($key)
    {
        return hash('sha256', $key);
    }
    
    private function getTableName($cacheId)
    {
        if (!isset($this->tables[$cacheId])) {
            $tableName = $this->cacheTablePrefix.preg_replace('/[^A-z\d_]/', '', $cacheId);
            $this->tables[$cacheId] = $tableName;
        }
        
        return $this->tables[$cacheId];
    }
    
    public function ensureTableExistsForCache($cache)
    {
        $cacheId = $cache instanceof \Cachet\Cache ? $cache->id : $cache;

        $tableName = $this->getTableName($cacheId);
        $pdo = $this->connector->pdo ?: $this->connector->connect();
        
        if ($this->connector->engine == 'sqlite') {
            $sql = "
                CREATE TABLE IF NOT EXISTS `{$tableName}` (
                    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                    keyHash TEXT,
                    cacheKey TEXT,
                    itemData BLOB,
                    creationTimestamp INTEGER,
                    expiryTimestamp INTEGER NULL,
                    UNIQUE (keyHash)
                );
            ";
        }
        elseif ($this->connector->engine == 'mysql') {
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
        }
        else {
            throw new \UnexpectedValueException("Engine {$this->connector->engine} unsupported");
        }

        $result = $pdo->exec($sql);
        if ($result === false) {
            throw new \UnexpectedValueException(
                "Cache $cacheId create table query failed: ".
                implode(' ', $pdo->errorInfo())
            );
        }
    }
}

