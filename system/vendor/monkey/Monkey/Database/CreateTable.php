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

use Monkey;

/**
 * Class CreateTable
 *
 * 数据表创建语句生成类
 *
 * @package Monkey\Database
 */
class CreateTable {
    /**
     * 应用对象
     *
     * @var Monkey\App $app
     */
    public $app;
    /**
     * 连接对象
     *
     * @var Connection
     */
    protected $connection;

    /**
     * 字段类型映射
     *
     * @var array
     */
    protected static $map = array('char' => array('normal' => 'CHAR', 'tiny' => 'VARCHAR', 'small' => 'VARCHAR', 'medium' => 'CHAR', 'big' => 'CHAR'),

        'text' => array('normal' => 'TEXT', 'tiny' => 'TINYTEXT', 'small' => 'TINYTEXT', 'medium' => 'MEDIUMTEXT', 'big' => 'LONGTEXT'),

        'serial' => array('normal' => 'INT', 'tiny' => 'TINYINT', 'small' => 'SMALLINT', 'medium' => 'MEDIUMINT', 'big' => 'BIGINT'),

        'int' => array('normal' => 'INT', 'tiny' => 'TINYINT', 'small' => 'SMALLINT', 'medium' => 'MEDIUMINT', 'big' => 'BIGINT'),

        'float' => array('normal' => 'FLOAT', 'tiny' => 'FLOAT', 'small' => 'FLOAT', 'medium' => 'FLOAT', 'big' => 'DOUBLE'),

        'numeric' => array('normal' => 'DECIMAL', 'tiny' => 'DECIMAL', 'small' => 'DECIMAL', 'medium' => 'DECIMAL', 'big' => 'DECIMAL'),

        'blob' => array('normal' => 'BLOB', 'tiny' => 'TINYBLOB', 'small' => 'MEDIUMBLOB', 'medium' => 'BLOB', 'big' => 'LONGBLOB'),);

    /**
     * 表名
     *
     * @var string
     */
    protected $tableName;

    /**
     * 存储引擎
     *
     * @var null|string
     */
    protected $engine;

    /**
     * 字符设置
     *
     * @var null|string
     */
    protected $characterSet;

    /**
     * 本地语言
     *
     * @var null|string
     */
    protected $collation;

    /**
     * 注释
     *
     * @var string
     */
    protected $comment;

    /**
     * 主键是否自增
     *
     * @var boolean
     */
    protected $autoIncrement;

    /**
     * 字段
     *
     * @var array
     */
    protected $fields;

    /**
     * 索引
     *
     * @var array
     */
    protected $keys;

    /**
     * 构造方法
     *
     * @param Connection $connection 数据库连接
     * @param string $tableName 表名
     * @param string $comment 表注释
     * @param string $engine 存储引擎， 默认使用'InnoDB'
     * @param string $characterSet 字符集， 默认使用'utf8'
     * @param string $collation 本地语言
     */
    public function __construct($connection, $tableName, $comment = '', $engine = null, $characterSet = null, $collation = null) {
        $this->app = $connection->app;
        $this->connection = $connection;
        $this->tableName = $tableName;
        $this->engine = $engine ? $engine : 'InnoDB';
        $this->characterSet = $characterSet ? $characterSet : 'utf8';
        $this->collation = $collation;
        $this->comment = $comment;
    }

    /**
     * 添加字段
     *
     * @param string $fieldName 字段名
     * @param string $comment 字段注释
     * @param string $type 字段类型 char、text、serial、int、float、numeric、blob
     * @param string $scale 字段规模 normal、tiny、small、medium、big
     * @param int $size 字段具体尺寸
     * @param string $default 默认值
     * @param bool $autoIncrement 是否自动增加
     * @param bool $primaryKey 是否为主键
     *
     * @return $this
     */
    public function addField($fieldName, $comment, $type, $scale, $size = null, $default = 'notNull', $autoIncrement = false, $primaryKey = false) {
        $field = '`' . $fieldName . '` ';

        if ($type != 'char' and $type != 'serial' and $type != 'int' and $type != 'binary') {
            $size = null;
        }
        $type = isset(self::$map[$type]) ? self::$map[$type] : self::$map['char'];
        $type = isset($type[$scale]) ? $type[$scale] : $type['normal'];
        $field .= $type;

        $size and $field .= '(' . $size . ') ';

        $field .= (!$default ? 'DEFAULT NULL ' : strtolower($default) == 'notnull' ? 'NOT NULL ' : 'NOT NULL DEFAULT ' . $default);

        $autoIncrement and $field .= 'AUTO_INCREMENT ' and $this->autoIncrement = true;

        $comment and $field .= 'COMMENT \'' . $comment . '\'';

        $this->fields[] = $field;
        $primaryKey and $this->setPrimaryKey($fieldName);
        return $this;
    }

    /**
     * 添加字段（整体方式）
     *
     * @param $field 字段设置
     * @param bool $autoIncrement 是否为自增字段
     *
     * @return $this
     */
    public function addFieldByString($field, $autoIncrement = false) {
        $this->fields[] = $field;
        $autoIncrement and $this->autoIncrement = true;
        return $this;
    }

    /**
     * 设置主键
     *
     * @param $fieldName
     *
     * @return $this
     */
    public function setPrimaryKey($fieldName) {
        !$this->keys['primary'] and $this->keys['primary'] = 'PRIMARY KEY (`' . $fieldName . '`)';
        return $this;
    }

    /**
     * 设置唯一索引
     * @param $fieldName
     * @param null $alias
     */
    public function setUniqueKey($fieldName, $alias = null) {
        !$alias and $alias = $fieldName;
        $this->keys['unique'][$fieldName] = 'UNIQUE KEY `' . $alias . '` (`' . $fieldName . '`)';
    }

    /**
     * 设置普通索引
     * @param $fieldName
     * @param null $alias
     */
    public function setKey($fieldName, $alias = null) {
        !$alias and $alias = $fieldName;
        $this->keys['key'][$fieldName] = 'KEY `' . $alias . '` (`' . $fieldName . '`)';
    }

    /**
     * 获取完整的数据表创建语句
     * @return string
     */
    public function getSql() {
        $sql = 'CREATE TABLE IF NOT EXISTS {:' . $this->tableName . ":} \n(\n";
        $fields = $this->fields;
        !empty($this->keys['primary']) and $fields[] = $this->keys['primary'];
        !empty($this->keys['unique']) and $fields[] = implode(", \n", $this->keys['unique']);
        !empty($this->keys['key']) and $fields[] = implode(", \n", $this->keys['key']);
        $sql .= implode(", \n", $fields) . "\n)\n";
        $sql .= 'ENGINE = ' . $this->engine . "\n DEFAULT CHARSET = " . $this->characterSet;
        $this->collation and $sql .= "\n COLLATE " . $this->collation;
        $this->comment and $sql .= "\n COMMENT = " . $this->comment;
        $this->autoIncrement and $sql .= "\n AUTO_INCREMENT = 0;";
        return $sql;
    }

    /**
     * 执行创建操作
     *
     * @return Statement
     *
     * @throws Monkey\Exceptions\Sql\SqlEmptyException
     */
    public function execute() {
        $sql = $this->getSql();
        if (!$sql) {
            new Monkey\Exceptions\Sql\SqlEmptyException('sql语句为空，创建数据库表失败', 1024);
        }
        return $this->connection->query($sql);
    }
}