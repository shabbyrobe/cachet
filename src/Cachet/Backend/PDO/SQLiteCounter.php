<?php
namespace Cachet\Backend\PDO;

class SQLiteCounter implements \Cachet\Backend\Counter
{
    public $backend;

    public function __construct(\Cachet\Backend\PDO $backend)
    {
        $this->backend = $backend;
    }

    private function change($cacheId, $key, $by=1)
    {
        $pdo = $this->backend->connect();
        $pdo->beginTransaction();

        $table = $this->backend->ensureTable($cacheId);
        $stmt = $pdo->prepare("UPDATE `$table` SET itemData=itemData".($by>=0 ? '+' : '-').((int)$by)." WHERE keyHash=?");
        $keyHash = $this->backend->hashKey($key);
        $rowsUpdated = $stmt->execute([$keyHash]);
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

    function decrement($cacheId, $key, $by=1)
    {
    }
}

