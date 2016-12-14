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
        $pdo->beginTransaction();

        $table = trim(preg_replace("/[`]/", "", $this->tableName));
        $sql =
            "INSERT INTO `$table`(keyHash, cacheKey, counter, creationTimestamp) ".
            "VALUES(:keyHash, :cacheKey, :counterInsertValue, :creationTimestamp) ".
            "ON DUPLICATE KEY UPDATE counter=counter".($by>=0 ? '+' : '-').":counterUpdateValue"
        ;

        $stmt = $pdo->prepare($sql);
        if ($stmt === false) {
            throw new \UnexpectedValueException(
                "MySQL counter value query failed for table {$table}: ".implode(' ', $pdo->errorInfo())
            );
        }

        $result = $stmt->execute([
            ':keyHash'=>$this->hashKey($key),
            ':cacheKey'=>$key,
            ':counterUpdateValue'=>abs($by),
            ':counterInsertValue'=>$by,
            ':creationTimestamp'=>time(),
        ]);
        if ($result === false) {
            throw new \UnexpectedValueException(
                "MySQL counter value query failed for table {$table}: ".implode(' ', $pdo->errorInfo())
            );
        }
        
        $value = $this->value($key);
        $pdo->commit();
        return $value;
    }

    private function hashKey($key)
    {
        return hash('sha256', $key);
    }

    /**
     * @param string $key
     * @param int $value
     * @return void
     */
    function set($key, $value)
    {
        $pdo = $this->connector->pdo ?: $this->connector->connect();

        $table = trim(preg_replace("/[`]/", "", $this->tableName));
        $keyHash = $this->hashKey($key);
        $stmt = $pdo->prepare(
            "REPLACE INTO `$table`(keyHash, cacheKey, counter, creationTimestamp) ".
            "VALUES(:keyHash, :cacheKey, :counter, :creationTimestamp)"
        );
        if ($stmt === false) {
            throw new \UnexpectedValueException(
                "MySQL counter value query failed for table {$table}: ".implode(' ', $pdo->errorInfo())
            );
        }

        $result = $stmt->execute([
            ':keyHash'=>$keyHash,
            ':cacheKey'=>$key,
            ':counter'=>$value,
            ':creationTimestamp'=>time(),
        ]);
        if ($result === false) {
            throw new \UnexpectedValueException(
                "MySQL counter value query failed for table {$table}: ".implode(' ', $pdo->errorInfo())
            );
        }
    }

    /**
     * @param string $key
     * @return int
     */
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

    /**
     * @param string $key
     * @param int $by
     * @return int
     */
    function increment($key, $by=1)
    {
        return $this->change($key, $by);
    }

    /**
     * @param string $key
     * @param int $by
     * @return int
     */
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
