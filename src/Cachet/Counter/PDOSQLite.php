<?php
namespace Cachet\Counter;

use Cachet\Connector;

class PDOSQLite implements \Cachet\Counter
{
    public $connector;
    public $tableName;

    public function __construct($connector, $tableName='counter')
    {
        $this->connector = $connector instanceof Connector\PDO 
            ? $connector 
            : new Connector\PDO($connector)
        ;
        $this->tableName = $tableName;
    }

    private function change($key, $by=1)
    {
        $pdo = $this->connector->pdo ?: $this->connector->connect();
        $pdo->beginTransaction();

        $table = trim(preg_replace("/[`]/", "", $this->tableName));
        $sql = 
            "UPDATE `$table` ".
            "SET counter=counter".($by>=0 ? '+' : '-').abs((int)$by)." ".
            "WHERE keyHash=?"
        ;
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$this->hashKey($key)]);
        $rowsUpdated = $stmt->rowCount();
        if ($rowsUpdated == 0) {
            $this->set($key, 0);
        }
        $pdo->commit();
    }

    private function hashKey($key)
    {
        return hash('sha256', $key);
    }

    function set($key, $value)
    {
        $pdo = $this->connector->pdo ?: $this->connector->connect();
        $table = trim(preg_replace("/[`]/", "", $this->tableName));
        $keyHash = $this->hashKey($key);
        $stmt = $pdo->prepare(
            "REPLACE INTO `$table`(keyHash, cacheKey, counter, creationTimestamp) ".
            "VALUES(:keyHash, :cacheKey, :counter, :creationTimestamp)"
        );
        $result = $stmt->execute([
            ':keyHash'=>$keyHash,
            ':cacheKey'=>$key,
            ':counter'=>$value,
            ':creationTimestamp'=>time(),
        ]);
        if ($result === false) {
            throw new \UnexpectedValueException(
                "Create counter table {$table} query failed: ".implode(' ', $pdo->errorInfo())
            );
        }
    }

    function value($key)
    {
        $table = trim(preg_replace("/[`]/", "", $this->tableName));
        $sql = "SELECT counter FROM `$table` WHERE keyHash=?";
        $pdo = $this->connector->pdo ?: $this->connector->connect();
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$this->hashKey($key)]);
        if ($result === false) {
            throw new \UnexpectedValueException(
                "Query failed: ".implode(' ', $pdo->errorInfo())
            );
        }
        return $stmt->fetchColumn() ?: 0;
    }

    function increment($key, $by=1)
    {
        return $this->change($key, $by);
    }

    function decrement($key, $by=1)
    {
        return $this->change($key, -$by);
    }

    public function ensureTableExists()
    {
        $table = trim(preg_replace("/[`]/", "", $this->tableName));
        $pdo = $this->connector->pdo ?: $this->connector->connect();
        $sql = "
            CREATE TABLE IF NOT EXISTS `{$table}` (
                id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                keyHash TEXT,
                cacheKey TEXT,
                counter INTEGER,
                creationTimestamp INTEGER,
                UNIQUE (keyHash)
            );
        ";

        $result = $pdo->exec($sql);
        if ($result === false) {
            throw new \UnexpectedValueException(
                "Create counter table {$table} query failed: ".implode(' ', $pdo->errorInfo())
            );
        }
    }
}

