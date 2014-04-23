<?php

namespace Monkey\Database\Pgsql;

use Monkey\Database as Query;
use \PDO;

class Connection extends Query\Connection
{
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
     * 获取所操作的数据库类型
     * @return string
     */
    public function getType()
    {
        return 'pgsql';
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
        else{
            !$config['port'] and $config['port']='5432';
            $dsn = 'pgsql:host=' . $config['host'] . ';port=' .$config['port'];
            $config['dbname']  and $dsn .= ';dbname=' . $config['dbname'];
        }
        $options= $config['options'] + array(
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY  => TRUE,
            PDO::ATTR_EMULATE_PREPARES          => TRUE,
            PDO::ATTR_ERRMODE                   => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_STRINGIFY_FETCHES         => TRUE,
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
            $this->app->logger()->sql($error);
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
        empty($this->oSchema) and $this->oSchema = new Schema($this);
        return $this->oSchema;
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
     * 创建一个嵌套事务对象
     * 注意保存这个对象，提交 和 回滚 事务需要它
     * @param null $transName 事务名称，可以不指定
     * @return Transaction
     */
    public function transactionNested($transName=null)
    {
        return new Transaction($this->oPDO, $this->name, $transName);
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