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
     * 获取查询的真实语句QueryString
     *
     * implements PDOStatement
     */
    public function getSQL()
    {
        return $this->queryString;
    }

}
