<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Administrator
 * Date: 13-6-16
 * Time: 上午11:15
 * To change this template use File | Settings | File Templates.
 */

namespace Monkey\Database\Query;

use \PDO;

class Connection
{
    /**
     * @var \Monkey\Monkey
     */
    protected $oMonkey;

    /**
     * @var \Monkey\Logger\Logger|null
     */
    protected $oLogger = NULL;

    /**
     * @var bool|\PDO
     */
    protected $oPDO;

    /**
     * @var \PDOStatement
     */
    protected $oStmt;

    /**
     * @var Schema
     */
    protected $oSchema;

    protected
        $config,
        $name,
        $transactionSupport = TRUE,//是否支持事务
        $transactionLayers = array(),//事务层级数组
        $isExecuteTrue,
        $lastSQL,
        $error
    ;


    /**
     * @param \Monkey\Monkey $monkey
     * @param string $name
     * @param array $config
     */
    public function __construct($monkey, $name, array $config = array() )
    {
        $this->oMonkey=$monkey;
        $this->oLogger= $monkey->getLogger();
        $this->name=$name;
        !isset($config['prefix']) and $config['prefix']='';
        $this->config=$config;
        $this->transactionSupport = !isset($config['transactions']) || ($config['transactions'] !== FALSE);
        $this->oPDO=$this->connecting($config);
    }

    /**
     * 获取日志记录器
     * @return \Monkey\Logger\Logger|null
     */
    public function getLogger()
    {
        return $this->oLogger;
    }

    /**
     * 获取容器
     * @return \Monkey\Monkey
     */
    public function getMonkey()
    {
        return $this->oMonkey;
    }

    /**
     * 获取所操作的数据库类型
     * @return string
     */
    public function getType()
    {
        return 'mysql';
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
     * 连接数据库
     * @param $config
     * @return bool|PDO
     */
    public function connecting($config)
    {
        if(isset($config['dsn'])){
            $dsn = $config['dsn'];
        }
        else if (isset($config['unix_socket'])){
            $dsn = 'mysql:unix_socket=' . $config['unix_socket'];
        }
        else{
            !$config['port'] and $config['port']='3306';
            $dsn = 'mysql:host=' . $config['host'] . ';port=' .$config['port'];
            $config['dbname']  and $dsn .= ';dbname=' . $config['dbname'];
        }
        $config['charset'] and $dsn .= ';charset='.$config['charset'];
        $options= $config['options'] + array(
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY  => TRUE,
            PDO::ATTR_EMULATE_PREPARES          => TRUE,
            PDO::ATTR_ERRMODE                   => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT                => false
        );

        try {
            $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        }
        catch (\PDOException $e) {
            $error=array(
                'error_title'       =>'连接到PDO时出错。',
                'message'           =>$e->getMessage(),
                'code'              =>$e->getCode(),
            );
            $this->oLogger->put('sql',$error);
            return false;
        }

        if(isset($config['charset'])){
            $sql='SET NAMES '.$config['charset'];
            $config['collation'] and $sql.=' COLLATE '.$config['collation'];
            $pdo->exec($sql);
        }

        $init_commands= $config['init_commands'] + array(
            'sql_mode' => "SET sql_mode = 'ANSI,STRICT_TRANS_TABLES,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER'",
        );
        $pdo->exec(implode('; ', $init_commands));

        return $pdo;
    }

    /**
     * 直接SQL
     * @param string $sql
     * @param array $args
     * @return Connection
     */
    public function query($sql, array $args = array())
    {
        $this->isExecuteTrue=false;
        $this->oStmt=null;
        $this->error=array();
        if(!$sql){
            $this->error=array('code'=>1024, 'message'=>'sql语句为空，无法执行query操作！','connectionName'=>$this->name);
            $this->oLogger->put('sql',$this->error);
            return $this;
        }
        try {
            $sql = preg_replace('/\{:(\S+?):\}/',$this->config['prefix'].'$1',$sql);
            $this->expandArguments($sql, $args);
            $this->oStmt=$this->oPDO->prepare($sql);
            $this->lastSQL=$sql;
            !is_null($args) and $args=$this->quote($args);
            $this->isExecuteTrue = $this->oStmt->execute($args);
        }
        catch (\PDOException $e) {
            $this->error=array('code'=>$e->getCode(),'message'=>$e->getMessage(),
                'file'=>$e->getFile(),'line'=>$e->getLine(),
                'connectionName'=>$this->name);
            $this->oLogger->put('sql',$this->error+$args);
        }
        return $this;
    }

    /**
     * @param $table
     * @param null $alias
     * @param array $options
     * @return Select
     */
    public function select($table, $alias = NULL, array $options = array()) {
        return new Select($this, $table, $alias, $options);
    }

    /**
     * @param $table
     * @return Insert
     */
    public function insert($table) {
        return new Insert($this, $table);
    }

    /**
     * @param $table
     * @return Update
     */
    public function update($table) {
        return new Update($this, $table);
    }

    /**
     * @param $table
     * @return Delete
     */
    public function delete($table) {
        return new Delete($this, $table);
    }

    /**
     * @param $table
     * @param array $options
     * @return Merge
     */
    /*public function merge($table, array $options = array()) {
        return new Merge($this, $table, $options);
    }*/

    /**
     * @return Schema
     */
    public function schema()
    {
        empty($this->oSchema) and $this->oSchema = new Schema($this);
        return $this->oSchema;
    }

    /**
     * @param string $tableName 表名
     * @param string $comment 表注释
     * @param string $engine 存储引擎， 默认使用'InnoDB'
     * @param string $characterSet 字符集， 默认使用'utf8'
     * @param string $collation 本地语言
     * @return SqlBuilderForCreateTable
     */
    public function newSqlBuilderForCreateTable($tableName, $comment='', $engine=null, $characterSet=null, $collation=null)
    {
        return new SqlBuilderForCreateTable($tableName, $comment, $engine, $characterSet, $collation);
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
        $sql='SHOW COLUMNS FROM {:'.$tableName.':}' ;
        $this->query($sql);
        if(!$this->isExecuteTrue){
            return FALSE;
        }
        $tableMate=$this->oStmt->fetchAll(PDO::FETCH_ASSOC);
        $mate['pri_name']=null;
        $mate['pri_is_auto']=false;
        foreach($tableMate as $field){
            $mate['fields_default'][$field['Field']]=$field['Default'];
            $mate['fields_type'][$field['Field']]=$field['Type'];
            if($field['Key']=='PRI'){
                $mate['pri_name']= $field['Field'];
                $mate['pri_is_auto']= $field['Extra']=='auto_increment';
            }
        }
        return $mate;
    }

    /**
     * 查询方法判断查询是否成功
     * @return bool
     */
    public function isSuccess()
    {
        return $this->isExecuteTrue;
    }

    /**
     * 获取pdo驱动
     * @return bool|PDO
     */
    public function getPDO()
    {
        return $this->oPDO;
    }

    /**
     * 获取查询结果生成的\PDOStatement对象
     * @return \PDOStatement
     */
    public function getResultStmt()
    {
        return $this->oStmt;
    }

    /**
     * 获取上次查询的真实sql语句
     * @return string
     */
    public function getLastSql()
    {
        return $this->lastSQL;
    }

    /**
     * 对sql参数中的特殊字符进行转义
     * @param string|array $data 待转义的数据
     * @return string|array
     */
    public function quote($data)
    {
        if (is_array($data)) return array_map(array($this,'escape'), $data);
        /*
        if (is_null($data)) return 'NULL';
        if (is_bool($data)) return $data ? '1' : '0';
        if (is_int($data)) return (int) $data;
        if (is_float($data)) return (float) $data;
        */
        return $this->oPDO->quote($data);
    }

    /**
     * 读取错误代号
     * @return integer
     */
    public function errorCode()
    {
        if(is_null($this->error['code']))
            return $this->oPDO->errorCode();
        else
            return $this->error['code'];
    }

    /**
     * 读取错误信息
     * @return string
     */
    public function errorMessage()
    {
        if(is_null($this->error['message']))
            return $this->oPDO->errorCode(). '【sql：】' . $this->lastSQL;
        else
            return $this->error['message'];
    }

    /**
     * 返回数据库版本信息
     *
     * implements PDO
     */
    public function version()
    {
        return $this->oPDO->getAttribute(PDO::ATTR_SERVER_VERSION);
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
            $this->oPDO->beginTransaction();
        }elseif($type===1 or $type=='commit'){
            $this->oPDO->commit();
        }elseif($type===-1 or $type=='rollback'){
            $this->oPDO->rollBack();
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
        return new Transaction($this->oPDO, $this->name, $transName);
    }

    /**
     * 销毁这个连接对象
     */
    public function __destruct() {
        $this->oStmt=null;
        $this->oSchema = NULL;
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