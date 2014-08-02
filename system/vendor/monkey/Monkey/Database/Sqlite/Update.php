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
 * Class Update
 *
 * 数据更新查询类
 *
 * @package Monkey\Database\Sqlite
 */
class Update extends Query\Update {
    /**
     * 构造方法
     *
     * @param Connection $connection
     * @param $table
     */
    public function __construct(Connection $connection, $table) {
        $this->app = $connection->app;
        $this->connection = $connection;
        $this->queryIdentifier = uniqid('', TRUE);
        $this->table = $table;
        $this->condition = new Condition($this->app, 'AND');
    }

}
