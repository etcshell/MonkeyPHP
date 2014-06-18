<?php
namespace Monkey\Database\Sqlite;

use Monkey\Database as Query;
use Monkey\Database\Statement;
use \PDO;
use \PDOException;
use Monkey\App\App;
//sqlite中所有表都有一个伪字段rowid，这个是自增的可以被Select语句选中，所以没有必要再人为设置主键了
/**
 * Connection
 * 连接类
 * @package Monkey\Database
 */
class Connection extends PDO
{
    /**
     * @var \Monkey\App\App $app
     */
    public $app;

    /**
     * @var \PDOStatement
     */
    protected $stmt;

    /**
     * @var Schema
     */
    protected $oSchema;

    /**
     * Statement
     *
     * @var string
     */
    protected $statementClass = '\\Monkey\\Database\\Statement';

    protected
        $config,
        $name,
        $transactionSupport = TRUE,//是否支持事务
        $transactionLayers = array(),//事务层级数组
        $prepareSQL
    ;

    /**
     * @param App $app
     * @param string $name
     * @param array $config
     * @throws PDOException
     */
    public function __construct($app,$name, array $config = array() )
    {
        $this->app=$app;
        $this->name=$name;
        !isset($config['prefix']) and $config['prefix']='';
        $this->config=$config;
        $this->transactionSupport = isset($config['transactions']) ? (bool)$config['transactions'] : FALSE;


        if(isset($config['dsn'])){
            $dsn = $config['dsn'];
        }
        else{
            $dsn = 'sqlite:' . $config['file'] ;
        }
        $options= $config['options'] + array(
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY  => TRUE,
                PDO::ATTR_EMULATE_PREPARES          => TRUE,
                PDO::ATTR_ERRMODE                   => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_PERSISTENT                => false
            );

        try {
            parent::__construct($dsn, $config['username'], $config['password'], $options);
        }
        catch (\PDOException $e) {
            $error=array(
                'error_title'       =>'连接到PDO时出错。',
                'message'           =>$e->getMessage(),
                'code'              =>$e->getCode(),
                'dsn_true'          =>$dsn,
            );
            $this->app->logger()->sql($error+$config);
            throw new PDOException($e->getMessage(),$e->getCode(),$e->getPrevious());
        }

        if(isset($config['charset'])){
            $sql='SET NAMES '.$config['charset'];
            $config['collation'] and $sql.=' COLLATE '.$config['collation'];
            $this->exec($sql);
        }

        $init_commands=$config['init_commands']?$config['init_commands']:array();
        $init_commands=$init_commands+ array(
                'sql_mode' => "SET sql_mode = 'ANSI,STRICT_TRANS_TABLES,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER'",
            );
        $this->exec(implode('; ', $init_commands));

        if (!empty($this->statementClass)) {
            $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array($this->statementClass, array($app,$this)));
        }
    }

    /**
     * 销毁连接
     */
    public function destroy() {
        $this->__destruct();
    }

    /**
     * 销毁这个连接对象
     */
    public function __destruct() {
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('PDOStatement', array()));
        $this->stmt=null;
        $this->oSchema = NULL;
    }

    /**
     * 新建条件生成器
     * @param string $conjunction 联合方式 AND | OR | XOR
     * @return Condition
     */
    public function newCondition($conjunction='AND')
    {
        return new Condition($this->app,$conjunction);
    }

    /**
     * 获取所操作的数据库类型
     * @return string
     */
    public function getType()
    {
        return 'sqlite';
    }

    /**
     * 获取当前连接的名称
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 获取连接配置
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * 直接SQL
     * @param string $sql
     * @param array $args
     * @throws \Exception
     * @return Statement
     *
     *   ->query('SELECT id FROM table WHERE id = :id' ,array(':id'=>1))
     *   ->fetchAll();
     */
    public function query($sql, array $args = array())
    {
        $this->stmt=null;
        if(!$sql){
            $error=array('code'=>1024, 'sql'=>'', 'message'=>'sql语句为空，无法执行query操作！','connectionName'=>$this->name);
            $this->app->logger()->sql($error);
            throw new \Exception('数据库查询错误。',1024,__FILE__,__LINE__);
        }
        try {
            $sql = preg_replace('/\{:(\S+?):\}/',$this->config['prefix'].'$1',$sql);
            $this->expandArguments($sql, $args);
            $this->prepareSQL=$sql;
            $this->stmt= parent::prepare($sql);
            $this->stmt->execute($args);
        }
        catch (\PDOException $e) {
            $error=array(
                'code'=>$e->getCode(),
                'prepareSQL'=>$this->prepareSQL ,'sql'=>$this->stmt->queryString,
                'message'=>$e->getMessage(),
                'file'=>$e->getFile(),'line'=>$e->getLine(),
                'connectionName'=>$this->name
            );
            $args=$args?$args:array();
            $this->app->logger()->sql($error+$args);
            throw new \Exception('数据库查询错误。',1024,__FILE__,__LINE__);
        }
        return $this->stmt;
    }

    public function prepare($sql)
    {
        throw new \Exception('数据库预处理查询prepare不可用，请改用直接查询query方法。',1024,__FILE__,__LINE__);
    }

    /**
     * 获取活动记录行对象
     * @param string $table 表名
     * @param string $priKey 主键
     * @return ActiveRecorder
     */
    public function activeRecorder($table, $priKey)
    {
        return new ActiveRecorder($this, $table, $priKey);
    }

    /**
     * 获取选择查询对象
     * @param $table
     * @param null $alias
     * @param array $options
     * @return Select
     *   ->select('table', 'alias')
     *   ->fields('alias')
     *   ->condition('id', 1)
     *   ->execute()
     *   ->fetchAll();
     */
    public function select($table, $alias = NULL, array $options = array()) {
        return new Select($this, $table, $alias, $options);
    }

    /**
     * 获取插入查询对象
     * @param $table
     * @return Insert
     *   ->insert('table')
     *   ->fields(array(
     *      'name' => 'value',
     *   ))
     *   ->execute()
     *   ->lastInsertId();
     */
    public function insert($table) {
        return new Insert($this, $table);
    }

    /**
     * 获取更新查询对象
     * @param $table
     * @return Update
     *   ->update('table')
     *   ->fields(array(
     *      'name' => 'value',
     *   ))
     *   ->condition('id', 1)
     *   ->execute()
     *   ->affected();
     */
    public function update($table) {
        return new Update($this, $table);
    }

    /**
     * 获取删除查询对象
     * @param $table
     * @return Delete
     *   ->delete('table')
     *   ->condition('id', 1)
     *   ->execute()
     *   ->affected();
     */
    public function delete($table) {
        return new Delete($this, $table);
    }

    /**
     * 获取表结构修改查询对象
     * @return Schema
     */
    public function schema()
    {
        empty($this->oSchema) and $this->oSchema = new Schema($this);
        return $this->oSchema;
    }

    /**
     * 获取表创建查询对象
     * @param string $tableName 表名
     * @param string $comment 表注释
     * @param string $engine 存储引擎， 默认使用'InnoDB'
     * @param string $characterSet 字符集， 默认使用'utf8'
     * @param string $collation 本地语言
     * @return CreateTable
     */
    public function createTable($tableName, $comment='', $engine=null, $characterSet=null, $collation=null)
    {
        return new CreateTable($this, $tableName, $comment, $engine, $characterSet, $collation);
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
        if(empty($tableName))
        {
            return FALSE;
        }
        $sql='PRAGMA table_info({:'.$tableName.':})';
        if(!$this->query($sql)->isSuccess())return FALSE;
        $tableMate=$this->stmt->fetchAll();
        $mate['pri_name']=null;
        $mate['pri_is_auto']=false;
        foreach($tableMate as $field){
            $mate['fields_default'][$field['name']]=$field['dflt_value'];
            $mate['fields_type'][$field['name']]=$field['type'];
            if($field['pk']){
                $mate['pri_name']=$field['name'];
                $mate['pri_is_auto']=true;
            }
        }
        return $mate;
    }

    /**
     * 获取查询结果生成的Statement对象
     * @return Statement
     */
    public function stmt()
    {
        return $this->stmt;
    }

    /**
     * 获取上次查询的预处理后的sql语句
     * @return string
     */
    public function getPrepareSQL()
    {
        return $this->prepareSQL;
    }

    /**
     * 对sql参数中的特殊字符进行转义
     * @param string|array $data 待转义的数据
     * @return string|array
     */
    public function quote($data)
    {
        if (is_array($data)) return array_map(array($this,'quote'), $data);
        if (is_null($data)) return 'NULL';
        if (is_bool($data)) return $data ? '1' : '0';
        if (is_int($data)) return (int) $data;
        if (is_float($data)) return (float) $data;
        return parent::quote($data);
    }

    /**
     * 返回数据库版本信息
     *
     * implements PDO
     */
    public function version()
    {
        return $this->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    /**
     * 转义LIKE时的一些特殊字符
     */
    public function escapeLike($string)
    {
        return addcslashes($string, '\%_');
    }

    /**
     * 简单事务处理
     * @param integer|string $type 0|'begin', 1|'commit', -1|'rollback'
     * @return $this
     * @throws \Exception
     */
    public function transactionLite($type)
    {
        if(!$this->transactionSupport){
            throw new \Exception('当前数据库不支持事务处理！');
        }
        if($type===0 or $type=='begin'){
            $this->beginTransaction();
        }elseif($type===1 or $type=='commit'){
            $this->commit();
        }elseif($type===-1 or $type=='rollback'){
            $this->rollBack();
        }
        return $this;
    }

    /**
     * 创建一个嵌套事务对象
     * 注意保存这个对象，提交 和 回滚 事务需要它
     * @param null $transName 事务名称，可以不指定
     * @return Transaction
     * @throws \Exception
     */
    public function transactionNested($transName=null)
    {
        if(!$this->transactionSupport){
            throw new \Exception('当前数据库不支持事务处理！');
        }
        return new Transaction($this, $this->name, $transName);
    }

    //扩展参数占位符
    protected function expandArguments(&$sql, &$args)
    {
        $modified = FALSE;
        //为子层生成占位符
        foreach (array_filter($args, 'is_array') as $key => $data) {
            $new_keys = array();
            foreach ($data as $i => $value) {
                $new_keys[$key . '_' . $i] = $value;
            }
            $sql = preg_replace('/' . $key . '\b/', implode(', ', array_keys($new_keys)), $sql);
            unset($args[$key]);
            $args += $new_keys;
            $modified = TRUE;
        }
        return $modified;
    }

}