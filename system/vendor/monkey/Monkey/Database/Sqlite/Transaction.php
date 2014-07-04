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
 * Class Transaction
 *
 * 数据库事务对象
 *
 * @package Monkey\Database\Sqlite
 */
class Transaction extends Query\Transaction
{

    /**
     * 构造方法
     *
     * @param Connection $connection
     * @param $connectionName
     * @param null $transName
     *
     * @throws \Exception
     */
    public function __construct(Connection $connection, $connectionName, $transName = null)
    {
        $this->conn = $connection;
        self::$transactionsTotal += array($connectionName => array());
        $this->pdoTrans = & self::$transactionsTotal[$connectionName];

        $depth = $this->getDepth() + 1;

        if ($depth == 1) {
            $transName = 'default_transaction';
        }

        if (!$transName) {
            $transName = 'savepoint_' . $depth;
        }

        $this->name = $transName;

        if ($this->pdoTrans[$this->name]) {
            throw new \Exception($transName . ' 这个事务名称已经被占用了，不能重复开启。');
        }

        if ($this->conn->inTransaction()) {
            $this->conn->query('SAVEPOINT ' . $transName);
        } else {
            $this->conn->beginTransaction();
        }

        $this->pdoTrans[$this->name] = $transName;
    }

}