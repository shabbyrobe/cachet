<?php
namespace Cachet\Test\Backend;

use Cachet\Backend;
use Cachet\Cache;

class PDOSqliteTest extends \BackendTestCase
{
    public function getBackend()
    {
        return new Backend\PDO(array('dsn'=>'sqlite::memory:'));
    }
}
