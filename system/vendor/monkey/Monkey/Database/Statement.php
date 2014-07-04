<?php
/**
 * Project MonkeyPHP
 *
 * PHP Version 5.3.9
 *
 * @package   Monkey\Database
 * @author    黄易 <582836313@qq.com>
 * @version   GIT:<git_id>
 */
namespace Monkey\Database;

use \PDOStatement;

/**
 * Class Statement
 *
 * 预处理类
 *
 * @package Monkey\Database
 */
class Statement extends PDOStatement
{

    /**
     * 连接对象
     *
     * @var Connection
     */
    public $conn;

    /**
     * 是否执行成功
     *
     * @var bool
     */
    private $isExecuteTrue = false;

    /**
     * 构造方法
     *
     * @param Connection $pdo
     */
    protected function __construct(Connection $pdo)
    {
        $this->conn = $pdo;
    }

    /**
     * 执行Statement
     *
     * @param $args
     *
     * @return true|false
     */
    public function execute($args = array())
    {
        $this->isExecuteTrue = parent::execute($args);
        return $this->isExecuteTrue;
    }

    /**
     * 获取连接对象
     *
     * @return Connection
     */
    public function connection()
    {
        return $this->conn;
    }

    /**
     * 返回最后插入的ID
     *
     * @return string
     */
    public function lastInsertId()
    {
        return $this->conn->lastInsertId();
    }

    /**
     * 查询执行是否成功
     *
     * @return bool
     */
    public function isSuccess()
    {
        return $this->isExecuteTrue;
    }

    /**
     * 查询影响的行数
     *
     * @return int
     */
    public function affected()
    {
        return $this->rowCount();
    }

    /**
     * 获取查询的真实语句QueryString
     *
     * implements PDOStatement
     */
    public function getSQL()
    {
        return $this->queryString;
    }

}
