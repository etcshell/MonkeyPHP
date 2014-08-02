<?php
/**
 * Project MonkeyPHP
 *
 * PHP Version 5.3.9
 *
 * @package   Monkey\Database\Sqlite
 * @author    黄易 <582836313@qq.com>
 * @version   GIT:<git_id>
 */
namespace Monkey\Database\Sqlite;

use Monkey\Database as Query;

/**
 * Class Insert
 *
 * 数据插入
 *
 * @package Monkey\Database\Insert
 */
class Insert extends Query\Insert
{

    /**
     * 构造方法
     *
     * @param Connection $connection
     * @param $table
     */
    public function __construct(Connection $connection, $table)
    {
        $this->app = $connection->app;
        $this->connection = $connection;
        $this->table = $table;
    }

}
