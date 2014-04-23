<?php
namespace Monkey\Database;

/**
 * 数据库组件 Database
 * @package Monkey\Database
 */

class Database
{
    //返回:Connection对象
    const RETURN_CONNECTION = 0;
    //返回:statement对象
    const RETURN_STATEMENT  = 1;
    //返回:affected数目
    const RETURN_AFFECTED   = 2;
    //返回:last insert id
    const RETURN_INSERT_ID  = 3;

    private
        /**
         * @var \Monkey\App\App
         */
        $app,
        $config,
        $pool,
        $default,
        $active,
        $oConnections
    ;

    /**
     * @param \Monkey\App\App $app
     */
    public function __construct($app)
    {
        if( !extension_loaded('pdo') ) $app->exception('没有安装pdo驱动扩展,请先在php.ini中配置安装pdo！',1024,__FILE__,__LINE__);
        $this->app= $app;
        $config= $app->config->getComponentConfig('database','default');
        $this->config=$config;
        $this->pool= $config['pool'];
        $this->default= $config['default_connection'];
        if(!$this->default || !isset($this->pool[$this->default])) {
            reset($this->pool);
            $this->default= key($this->pool);
        }
        $this->active=$this->default;
    }

    /**
     * 直接SQL
     * @param string $sql
     * @param int $returnType
    0 = \Monkey\Database::RETURN_CONNECTION //返回:Connection对象本身 （默认值）
    1 = \Monkey\Database::RETURN_STATEMENT  //返回:statement对象
    2 = \Monkey\Database::RETURN_AFFECTED   //返回:affected数目
    3 = \Monkey\Database::RETURN_INSERT_ID  //返回:last insert id
     * @param array $args
     *
     * @return Connection|\PDOStatement|int
     *
     *   ->query('SELECT id FROM table WHERE id = :id' , 1, array(':id'=>1))
     *   ->fetchAll();
     */
    public function query($sql, $returnType=Database::RETURN_CONNECTION, array $args = array())
    {
        return $this->getActiveConnection()->query($sql, $returnType, $args);
    }

    /**
     * @param string $table 表名
     * @param string $priKey 主键
     * @return ActiveRecorder
     */
    public function activeRecorder($table, $priKey)
    {
        return $this->getActiveConnection()->activeRecorder($table, $priKey);
    }

    /**
     * @param $table
     * @param null $alias
     * @param array $options
     * @return Select
     *   ->select('table', 'alias')
     *   ->fields('alias')
     *   ->condition('id', 1)
     *   ->execute(1) //return statement对象
     *   ->fetchAll();
     */
    public function select($table, $alias = NULL, array $options = array())
    {
        return $this->getActiveConnection()->select($table, $alias, $options);
    }

    /**
     * @param $table
     * @return Insert
     *   ->insert('table')
     *   ->fields(array(
     *      'name' => 'value',
     *   ))
     *   ->execute(3);//return lastInsertId;
     */
    public function insert($table) {
        return $this->getActiveConnection()->insert($table);
    }

    /**
     * @param $table
     * @return Update
     *   ->update('table')
     *   ->fields(array(
     *      'name' => 'value',
     *   ))
     *   ->condition('id', 1)
     *   ->execute(2);//return affected数目
     */
    public function update($table) {
        return $this->getActiveConnection()->update($table);
    }

    /**
     * @param $table
     * @return Delete
     *   ->delete('table')
     *   ->condition('id', 1)
     *   ->execute(2);//return affected数目
     */
    public function delete($table) {
        return $this->getActiveConnection()->delete($table);
    }

    /**
     * @param $table
     * @param array $options
     * @return
     */
    /*public function merge($table, array $options = array()) {
        return new Merge($this, $table, $options);
    }*/

    /**
     * @return Schema
     */
    public function schema()
    {
        return $this->getActiveConnection()->schema();
    }

    /**
     * @param string $tableName 表名
     * @param string $comment 表注释
     * @param string $engine 存储引擎， 默认使用'InnoDB'
     * @param string $characterSet 字符集， 默认使用'utf8'
     * @param string $collation 本地语言
     * @return CreateTable
     */
    public function createTable($tableName, $comment='', $engine=null, $characterSet=null, $collation=null)
    {
        return $this->getActiveConnection()->createTable($tableName, $comment, $engine, $characterSet, $collation);
    }

    /**
     * 读取表字段信息
     * @param string $tableName 表名称
     * @return boolean|array
     * 返回结果结构如下：
     * array(
     *      'pri_name'      =>string,
     *      'pri_is_auto'   =>boolean,
     *      'fields_default'=>array,
     *      'fields_type'   =>array,
     * );
     */
    public function getTableMate($tableName)
    {
        return $this->getActiveConnection()->getTableMate($tableName);
    }

    /**
     * 激活某个连接
     * @param string $name 留空时激活默认连接
     * @return bool
     */
    public function activeConnection($name=null)
    {
        if($name===null) $name=$this->default;
        $name=strtolower($name);
        if(!isset($this->pool[$name])) return false;
        $this->active=$name;
        return true;
    }

    /**
     * 获取指定名称的查询连接
     *
     * @param string|null $name 连接名称。留空时，使用当前活动连接
     * @return \Monkey\Database\Connection|bool|null
     *
     * 1.正确返回\Monkey\Database\Connection；
     * 2.不存在指定的连接（包括默认连接）返回null；
     * 3.连接失败返回false。
     */
    public function getConnection($name=null)
    {
        if(empty($name)) {
            $name= $this->active;
        }
        else {
            $name=strtolower($name);
            $name= isset($this->pool[$name]) ? $name : null;
        }
        if(!$name) {
            return null;
        }
        if(!isset($this->oConnections[$name])) {
            $this->oConnections[$name]= $this->tryConnecting($this->config[$name],$name);
        }
        return $this->oConnections[$name];
    }

    /**
     * @param $name
     * @return \Monkey\Database\Connection|false
     */
    /**
     * 尝试连接
     * @param array $config 连接配置
     * @param string $name 连接名称，留空表示测试连接
     * @return \Monkey\Database\Connection|false
     */
    public function tryConnecting($config, $name='test')
    {
        $class=ucfirst(strtolower($config['protocol']));
        $class= $class=='Mysql'? '' : '\\'.$class;//如果是Mysql驱动，直接使用父类，目的是获得更高的效率
        $class= __NAMESPACE__.$class.'\\Connection';
        try {
            $connect = new $class($this->app,$name,$config);
        } catch (\PDOException $e) {
            $error=array(
                'error_title'       =>'连接到PDO时出错。',
                'message'           =>$e->getMessage(),
                'code'              =>$e->getCode(),
            );
            $this->app->logger()->sql($error);
            return false;
        }
        if(!$connect->getPDO()) return false;
        return $connect;
    }

    /**
     * @return \Monkey\Database\Connection
     */
    private function getActiveConnection()
    {
        $name=$this->active;
        if(!isset($this->oConnections[$name])) {
            $this->oConnections[$name]= $this->tryConnecting($this->config[$name],$name);
        }
        if(!isset($this->oConnections[$name])){
            $this->app->exception('连接数据库出错');
        }
        return $this->oConnections[$name];
    }

    public function __destruct()
    {
        $this->config=null;
        $this->pool=null;
        $this->default=null;

        if($this->oConnections) {
            foreach($this->oConnections as $key=>$connection) {
                $connection=null;
                $this->oConnections[$key]=null;
            }
            $this->oConnections=null;
        }
    }

}