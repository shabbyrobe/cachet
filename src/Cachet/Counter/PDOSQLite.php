<?php
namespace Cachet\Counter;

class PDOSQLite implements \Cachet\Counter
{
    public $connector;

    public function __construct($connector)
    {
        $this->connector = $connector instanceof Connector\PDO 
            ? $connector 
            : new Connector\PDO($connector)
        ;
    }

    private function change($cacheId, $key, $by=1)
    {
        $pdo = $this->connector->pdo ?: $this->connector->connect();
        $pdo->beginTransaction();

        $table = $this->backend->ensureTable($cacheId);
        $stmt = $pdo->prepare(
            "UPDATE `$table` ".
            "SET itemData=itemData".($by>=0 ? '+' : '-').((int)$by)." ".
            "WHERE keyHash=?"
        );
        $keyHash = $this->backend->hashKey($key);
        $result = $stmt->execute([$keyHash]);
        $rowsUpdated = $stmt->rowCount();
        if ($rowsUpdated == 0) {
            $stmt = $pdo->prepare(
                "INSERT INTO `$table`(keyHash, cacheKey, itemData, creationTimestamp) ".
                "VALUES(:keyHash, :cacheKey, :itemData, :creationTimestamp)"
            );
            $stmt->execute([
                ':keyHash'=>$keyHash,
                ':cacheKey'=>$key,
                ':itemData'=>$by,
                ':creationTimestamp'=>time(),
            ]);
        }
        $pdo->commit();
    }

    function increment($cacheId, $key, $by=1)
    {
        return $this->change($cacheId, $key, $by);
    }

    function decrement($cacheId, $key, $by=1)
    {
        return $this->change($cacheId, $key, -$by);
    }
}


