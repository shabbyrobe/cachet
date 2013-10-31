<?php
namespace Cachet\Counter;

use Cachet\Connector;

class PDOMySQL implements \Cachet\Counter
{
    public $connector;
    public $tableName;

    public function __construct($connector, $tableName='cachet_counter')
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
        $table = trim(preg_replace("/[`]/", "", $this->tableName));
        $sql = 
            "INSERT INTO `$table`(keyHash, cacheKey, counter, creationTimestamp) ".
            "VALUES(:keyHash, :cacheKey, :counterInsertValue, :creationTimestamp) ".
            "ON DUPLICATE KEY UPDATE counter=counter".($by>=0 ? '+' : '-').":counterUpdateValue"
        ;

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':keyHash'=>$this->hashKey($key),
            ':cacheKey'=>$key,
            ':counterUpdateValue'=>abs($by),
            ':counterInsertValue'=>$by,
            ':creationTimestamp'=>time(),
        ]);
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
                "Set counter value query failed for table {$table}: ".implode(' ', $pdo->errorInfo())
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
                id BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                keyHash VARCHAR(64),
                cacheKey TEXT,
                counter BIGINT NOT NULL DEFAULT 0,
                creationTimestamp INT,
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


