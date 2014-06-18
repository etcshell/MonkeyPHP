<?php
namespace Monkey\Database;

use \PDO;
use \PDOStatement;
use Monkey\App\App;
 
class Statement extends PDOStatement
{
    public
        /**
         * @var \Monkey\App\App $app
         */
        $app,
        /**
         * @var Connection
         */
        $conn
    ;

    private $isExecuteTrue=false;

    /**
     * @param string App $app
     * @param Connection $pdo
     */
    protected function __construct($app, Connection $pdo)
    {
        $this->app = $app;
        $this->conn = $pdo;
    }
    
    /**
     * 执行Statement
     * @param $args
     *
     * @return true|false
     */
    public function execute($args = array())
    {
        $this->isExecuteTrue=parent::execute($args);
        return $this->isExecuteTrue;
    }

    /**
     * 获取连接对象
     * @return Connection
     */
    public function connection()
    {
        return $this->conn;
    }

    /**
     * 返回最后插入的ID
     * @return string
     */
    public function lastInsertId()
    {
        return $this->conn->lastInsertId();
    }

    /**
     * 查询执行是否成功
     * @return bool
     */
    public function isSuccess()
    {
        return $this->isExecuteTrue;
    }

    /**
     * 查询影响的行数
     * @return int
     */
    public function affected()
    {
        return $this->rowCount();
    }

    /**
     * 获取QueryString
     *
     * implements PDOStatement
     */
    public function getQueryString()
    {
        return $this->queryString;
    }

    /**
     * 从结果集中获取下一行
     * 默认返回关联数组
     */
    public function fetch( $fetch_style=PDO::FETCH_ASSOC , $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0)
    {
        return parent::fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 返回一个包含结果集中所有行的数组
     * 默认返回关联数组
     */
    public function fetchAll( $fetch_style=PDO::FETCH_ASSOC , $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0)
    {
        return parent::fetchAll(PDO::FETCH_ASSOC);
    }
}
