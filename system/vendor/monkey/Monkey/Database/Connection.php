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

use \PDO;
use \PDOException;
use Monkey\App;

/**
 * Class Connection
 *
 * 连接类
 *
 * @package Monkey\Database
 */
class Connection extends PDO
{
    /**
     * 应用对象
     *
     * @var App $app
     */
    public $app;

    /**
     * 预处理对象
     *
     * @var Statement
     */
    protected $stmt;

    /**
     * 数据表结构修改对象
     *
     * @var Schema
     */
    protected $oSchema;

    /**
     * 预处理类名
     *
     * @var string
     */
    protected $statementClass = '\\Monkey\\Database\\Statement';

    /**
     * 连接名
     *
     * @var string
     */
    protected $name;

    /**
     * 连接配置
     *
     * @var array
     */
    protected $config;

    /**
     * 是否支持事务
     *
     * @var bool
     */
    protected $transactionSupport = false;

    /**
     * 事务层级
     *
     * @var array
     */
    protected $transactionLayers = array();

    /**
     * 预处理语句
     *
     * @var string
     */
    protected $prepareSQL;

    /**
     * 构造方法
     *
     * @param App $app
     * @param string $name
     * @param array $config
     *
     * @throws PDOException
     */
    public function __construct($app, $name, array $config = array())
    {
        //设置配置
        $this->app = $app;
        $this->name = $name;
        !isset($config['prefix']) and $config['prefix'] = '';
        isset($config['transactions']) and $this->transactionSupport = (bool)$config['transactions'];

        if (isset($config['dsn'])) {
            $dsn = $config['dsn'];

        } elseif (isset($config['unix_socket'])) {
            $dsn = 'mysql:unix_socket=' . $config['unix_socket'];

        } else {
            !isset($config['port']) and $config['port'] = '3306';
            $dsn = 'mysql:host=' . $config['host'] . ';port=' . $config['port'];
            isset($config['dbname']) and $dsn .= ';dbname=' . $config['dbname'];
        }

        isset($config['charset']) and $dsn = rtrim($dsn, ';') . ';charset=' . $config['charset'];

        $options = $config['options'] + array(
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => TRUE,
                PDO::ATTR_EMULATE_PREPARES => TRUE,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            );

        $this->config = $config;

        //连接数据库
        try {
            parent::__construct($dsn, $config['username'], $config['password'], $options);

        } catch (\PDOException $e) {
            //处理连接错误，并记录日志
            $error = array(
                'error_title' => '连接到PDO时出错。',
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'dsn_true' => $dsn,
            );
            $this->app->logger()->sql($error + $config);
            throw $e;
        }

        //设置连接属性
        if (isset($config['charset'])) {
            $sql = 'SET NAMES ' . $config['charset'];
            isset($config['collation']) and $sql .= ' COLLATE ' . $config['collation'];
            $this->exec($sql);
        }

        $init_commands = isset($config['init_commands']) ? $config['init_commands'] : array();

        $init_commands = $init_commands + array('sql_mode' =>
                "SET sql_mode = 'ANSI,STRICT_TRANS_TABLES,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER'"
            );

        $this->exec(implode('; ', $init_commands));

        if (!empty($this->statementClass)) {
            $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array($this->statementClass, array($this)));
        }

    }

    /**
     * 销毁这个连接对象
     */
    public function __destruct()
    {
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('PDOStatement', array()));
        $this->stmt = null;
        $this->oSchema = NULL;
    }

    /**
     * 新建条件生成器
     *
     * @param string $conjunction 联合方式 AND | OR | XOR
     *
     * @return Condition
     */
    public function newCondition($conjunction = 'AND')
    {
        return new Condition($this->app, $conjunction);
    }

    /**
     * 获取所操作的数据库类型
     *
     * @return string
     */
    public function getType()
    {
        return 'mysql';
    }

    /**
     * 获取当前连接的名称
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 获取连接配置
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * 直接SQL
     *
     * @param string $sql
     * @param array $args
     *
     * @throws \PDOException|SqlEmptyException
     *
     * @return Statement
     *
     * 例子
     *   ->query('SELECT id FROM table WHERE id = :id' ,array(':id'=>1))
     *   ->fetchAll();
     */
    public function query($sql, array $args = array())
    {
        //清理上一个预处理对象
        $this->stmt = null;
        $this->expandArguments($sql, $args);

        try {
            //创建预处理对象
            $this->stmt = $this->prepareQuery($sql);

            //执行预处理语句
            $this->stmt->execute($args);

        } catch (\PDOException $e) {
            //处理错误，并记录日志
            $error = array(
                'code' => $e->getCode(),
                'prepareSQL' => $this->prepareSQL,
                'sql' => $this->stmt->queryString,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'connectionName' => $this->name
            );
            $args = $args ? $args : array();
            $this->app->logger()->sql($error + $args);

            throw $e;
        }
        return $this->stmt;
    }

    /**
     * 独立预处理
     *
     * @param string $sql
     * @param array $driver_options
     *
     * @return Statement
     *
     * @throws SqlEmptyException
     */
    public function prepareQuery($sql, array $driver_options = array())
    {
        //效验sql语句
        if (!$sql) {
            $error = array(
                'code' => 1024,
                'sql' => '',
                'message' => 'sql语句为空，无法执行query操作！',
                'connectionName' => $this->name
            );
            $this->app->logger()->sql($error);

            throw new SqlEmptyException('数据库查询错误。', 1024, __FILE__, __LINE__);
        }

        //处理表前缀
        $sql = preg_replace('/\{:(\S+?):\}/', $this->config['prefix'] . '$1', $sql);

        //保存预处理sql
        $this->prepareSQL = $sql;

        return  parent::prepare($sql, $driver_options);
    }

    /**
     * 获取选择查询对象
     *
     * @param $table
     * @param null $alias
     * @param array $options
     *
     * @return Select
     *
     *   ->select('table', 'alias')
     *   ->fields('alias')
     *   ->condition('id', 1)
     *   ->execute()
     *   ->fetchAll();
     */
    public function select($table, $alias = NULL, array $options = array())
    {
        return new Select($this, $table, $alias, $options);
    }

    /**
     * 获取插入查询对象
     *
     * @param $table
     *
     * @return Insert
     *
     *   ->insert('table')
     *   ->fields(array(
     *      'name' => 'value',
     *   ))
     *   ->execute()
     *   ->lastInsertId();
     */
    public function insert($table)
    {
        return new Insert($this, $table);
    }

    /**
     * 获取更新查询对象
     *
     * @param $table
     *
     * @return Update
     *
     *   ->update('table')
     *   ->fields(array(
     *      'name' => 'value',
     *   ))
     *   ->condition('id', 1)
     *   ->execute()
     *   ->affected();
     */
    public function update($table)
    {
        return new Update($this, $table);
    }

    /**
     * 获取删除查询对象
     *
     * @param $table
     *
     * @return Delete
     *
     *   ->delete('table')
     *   ->condition('id', 1)
     *   ->execute()
     *   ->affected();
     */
    public function delete($table)
    {
        return new Delete($this, $table);
    }

    /**
     * 获取表结构修改查询对象
     *
     * @return Schema
     */
    public function schema()
    {
        empty($this->oSchema) and $this->oSchema = new Schema($this);
        return $this->oSchema;
    }

    /**
     * 获取表创建查询对象
     *
     * @param string $tableName 表名
     * @param string $comment 表注释
     * @param string $engine 存储引擎， 默认使用'InnoDB'
     * @param string $characterSet 字符集， 默认使用'utf8'
     * @param string $collation 本地语言
     *
     * @return CreateTable
     */
    public function createTable($tableName, $comment = '', $engine = null, $characterSet = null, $collation = null)
    {
        return new CreateTable($this, $tableName, $comment, $engine, $characterSet, $collation);
    }

    /**
     * 读取表字段信息
     *
     * @param string $tableName 表名称
     *
     * @return boolean|array
     *
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
        if (empty($tableName)) {
            return FALSE;
        }

        $sql = 'SHOW COLUMNS FROM {:' . $tableName . ':}';

        if (!$this->query($sql)->isSuccess()) {
            return FALSE;
        }

        $tableMate = $this->stmt->fetchAll();
        $mate['pri_name'] = null;
        $mate['pri_is_auto'] = false;
        foreach ($tableMate as $field) {
            $mate['fields_default'][$field['Field']] = $field['Default'];
            $mate['fields_type'][$field['Field']] = $field['Type'];

            if ($field['Key'] == 'PRI') {
                $mate['pri_name'] = $field['Field'];
                $mate['pri_is_auto'] = ($field['Extra'] == 'auto_increment');
            }
        }
        return $mate;
    }

    /**
     * 获取查询结果生成的Statement对象
     *
     * @return Statement
     */
    public function stmt()
    {
        return $this->stmt;
    }

    /**
     * 获取上次查询的预处理后的sql语句
     *
     * @return string
     */
    public function getPrepareSQL()
    {
        return $this->prepareSQL;
    }

    /**
     * 对sql参数中的特殊字符进行转义
     *
     * @param string|array $data 待转义的数据
     *
     * @return string|array
     */
    public function quoteParameters($data)
    {
        if (is_array($data)) return array_map(__METHOD__, $data); //array($this, 'quoteParameters')
        if (is_null($data)) return 'NULL';
        if (is_bool($data)) return $data ? '1' : '0';
        if (is_int($data)) return (int)$data;
        if (is_float($data)) return (float)$data;
        return $this->quote($data);
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
     *
     * @param integer|string $type 0|'begin', 1|'commit', -1|'rollback'
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function transactionLite($type)
    {
        if (!$this->transactionSupport) {
            throw new \Exception('当前数据库不支持事务处理！');
        }

        if ($type === 0 or $type == 'begin') {
            $this->beginTransaction();

        } elseif ($type === 1 or $type == 'commit') {
            $this->commit();

        } elseif ($type === -1 or $type == 'rollback') {
            $this->rollBack();
        }

        return $this;
    }

    /**
     * 创建一个嵌套事务对象
     * 注意保存这个对象，提交 和 回滚 事务需要它
     *
     * @param null $transName 事务名称，可以不指定
     *
     * @return Transaction
     *
     * @throws \Exception
     */
    public function transactionNested($transName = null)
    {
        if (!$this->transactionSupport) {
            throw new \Exception('当前数据库不支持事务处理！');
        }
        return new Transaction($this, $this->name, $transName);
    }

    /**
     * 扩展参数占位符
     *
     * @param $sql
     * @param $args
     *
     * @return bool
     */
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