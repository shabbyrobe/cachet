<?php
namespace Cachet\Counter;

use Cachet\Connector;

class PDOSQLite implements \Cachet\Counter
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
            "UPDATE `$table` ".
            "SET counter=counter".($by>=0 ? '+' : '-').abs((int) $by)." ".
            "WHERE keyHash=?"
        ;
        $stmt = $pdo->prepare($sql);
        if ($stmt === false) {
            throw new \RuntimeException(
                "PDO sqlite counter query failed: ".implode(' ', $pdo->errorInfo())
            );
        }

        $keyHash =$this->hashKey($key);
        $result = $stmt->execute([$keyHash]);
        if ($result === false) {
            throw new \RuntimeException(
                "PDO sqlite counter query failed: ".implode(' ', $pdo->errorInfo())
            );
        }

        $rowsUpdated = $stmt->rowCount();
        $value = null;
        if ($rowsUpdated == 0) {
            $this->set($key, $by);
            $value = $by;
        }
        else {
            $stmt = $pdo->prepare("SELECT counter FROM `$table` WHERE keyHash=?");
            if ($stmt === false) {
                throw new \RuntimeException(
                    "PDO sqlite counter query failed: ".implode(' ', $pdo->errorInfo())
                );
            }

            if (!$stmt->execute([$keyHash])) {
                throw new \RuntimeException(
                    "PDO sqlite counter query failed: ".implode(' ', $pdo->errorInfo())
                );
            }

            $value = $stmt->fetchColumn(0);
        }
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
            throw new \RuntimeException(
                "PDO sqlite counter query failed: ".implode(' ', $pdo->errorInfo())
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
                "PDO sqlite counter query failed: ".implode(' ', $pdo->errorInfo())
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
        if ($stmt === false) {
            throw new \UnexpectedValueException(
                "PDO sqlite counter query failed: ".implode(' ', $pdo->errorInfo())
            );
        }

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
