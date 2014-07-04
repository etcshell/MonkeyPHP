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
 * Class Delete
 *
 * 数据删除类
 *
 * @package Monkey\Database\Sqlite
 */
class Delete extends Query\Delete
{
    /**
     * 构造方法
     *
     * @param Connection $connection
     * @param string $table
     */
    public function __construct(Connection $connection, $table)
    {
        $this->app = $connection->app;
        $this->queryIdentifier = uniqid('', TRUE);
        $this->connection = $connection;
        $this->table = $table;
        $this->condition = new Condition($this->app, 'AND');
    }

}
