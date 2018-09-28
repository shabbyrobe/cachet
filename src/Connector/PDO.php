<?php
namespace Cachet\Connector;

class PDO
{
    /** @var \PDO|null */
    public $pdo;

    public $engine;

    private $params;
    private $creatorCallback;

    public function __construct($dbInfo)
    {
        if (is_string($dbInfo)) {
            $dbInfo = ['dsn'=>$dbInfo];
        }
        if (is_array($dbInfo)) {
            $this->params = $dbInfo;
        }
        elseif (is_callable($dbInfo)) {
            $this->creatorCallback = $dbInfo;
        }
        elseif (is_object($dbInfo)) {
            $this->pdo = $dbInfo;
            $this->engine = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        }
        else {
            throw new \InvalidArgumentException(
                "Must pass a connection info array, a callback that returns a PDO ".
                "or a PDO instance, found ".\Cachet\Helper::getType($dbInfo)
            );
        }
    }

    public function connect()
    {
        if (!$this->pdo) {
            if ($this->params) {
                $this->pdo = self::createPDO($this->params);
            } elseif ($this->creatorCallback) {
                $this->pdo = call_user_func($this->creatorCallback);
            } else {
                throw new \RuntimeException(
                    "You need to pass connection parameters or a connector callback rather than a ".
                    "PDO instance if you want to be able to reconnect"
                );
            }
        }

        if (!$this->engine) {
            $this->engine = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        }
        if ($this->engine != 'mysql' && $this->engine != 'sqlite') {
            throw new \RuntimeException("Only works with mysql and sqlite (for now)");
        }
        return $this->pdo;
    }

    public function disconnect()
    {
        $this->pdo = null;
    }

    public function getEngine()
    {
        return $this->engine;
    }

    public function __clone()
    {
        return $this->disconnect();
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
            if (strpos($k, "host")===0 || $k == 'server') {
                $host = $v;
            } elseif ($k=='port') {
                $port = $v;
            } elseif ($k=="database" || strpos($k, "db")===0) {
                $database = $v;
            } elseif ($k[0] == 'p') {
                $password = $v;
            } elseif ($k[0] == 'u') {
                $user = $v;
            } elseif ($k=='options') {
                $options = $v;
            }
        }
        if (!isset($params['dsn'])) {
            $dsn = (isset($params['engine']) ? $params['engine'] : 'mysql').":host={$host};";
            if ($port) {
                $dsn .= "port=".$port.';';
            }
            if (!empty($database)) {
                $dsn .= "dbname={$database};";
            }
        }
        else {
            $dsn = $params['dsn'];
        }

        if (!isset($options[\PDO::ATTR_ERRMODE])) {
            $options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
        }

        return new \PDO($dsn, $user, $password, $options);
    }
}
