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

/**
 * Class Schema
 *
 * 数据表结构修改类
 *
 * @package Monkey\Database
 */
class Schema {

    /**
     * 数据库连接对象
     *
     * @var Connection
     */
    protected $connection;

    /**
     * 数据表结构信息
     *
     * @var array
     */
    protected $schema = array();

    /**
     * 数据库连接配置
     *
     * @var array
     */
    protected $dbConfig;

    /**
     * 构造方法
     *
     * @param Connection $connection
     * @param string|null $databaseName 数据库名，默认使用连接中的数据库
     */
    public function __construct(Connection $connection, $databaseName = null) {
        $this->connection = $connection;
        $this->dbConfig = $connection->getConfig();

        if (!$databaseName) {
            $databaseName = $this->dbConfig['dbname'];
        }

        $this->explainSchema($databaseName);
    }

    /**
     * 获取数据表结构信息
     */
    public function getSchema() {
        return $this->schema;
    }

    /**
     * 表是否存在
     */
    public function existsTable($tableName) {
        return isset($this->schema[$this->prefix($tableName)]);
    }

    /**
     * 字段是否存在
     */
    public function existsField($tableName, $columnName) {
        return isset($this->schema[$this->prefix($tableName)][$columnName]);
    }

    /**
     * 索引是否存在
     */
    public function existsIndex($tableName, $indexName) {
        $sql = 'SHOW INDEX FROM {:' . $tableName . ':} WHERE key_name = ' . $indexName;
        $row = $this->connection->query($sql)->fetch();
        return isset($row['Key_name']);
    }

    /**
     * 创建表
     *
     * @param string $tableName 表名
     * @param string $tableCreateSql 创建表的sql
     *
     * @return bool
     */
    public function createTable($tableName, $tableCreateSql) {
        if ($this->existsTable($tableName)) {
            return false;
        }
        return $this->connection->query($tableCreateSql)->isSuccess();
    }

    /**
     * 删除表
     */
    public function dropTable($tableName) {
        if (!$this->existsTable($tableName)) {
            return true;
        }
        return $this->connection->query('DROP TABLE IF EXISTS {:' . $tableName . ':}')->isSuccess();
    }

    /**
     * 修改表名
     */
    public function renameTable($tableName, $newTableName) {
        if (!$this->existsTable($tableName) || $this->existsTable($newTableName)) {
            return false;
        }
        return $this->connection->query(
            'ALTER TABLE {:' . $tableName . ':} RENAME TO `{:' . $newTableName . ':}`'
        )->isSuccess();
    }

    /**
     * 清空数据表
     * @param string $tableName 表名称
     * @return boolean
     */
    public function truncateTable($tableName) {
        if (empty($tableName)) {
            return false;
        }
        $sql = 'TRUNCATE TABLE {:' . $tableName . ':}';
        return $this->connection->query($sql)->isSuccess();
    }

    /**
     * 添加字段
     */
    public function addField($tableName, $fieldName, $spec) {
        if (!$this->existsTable($tableName) || $this->existsField($tableName, $fieldName)) {
            return false;
        }
        $sql = 'ALTER TABLE {:' . $tableName . ':} ADD ' . $fieldName . ' ' . $spec;
        return $this->connection->query($sql)->isSuccess();
    }

    /**
     * 删除字段
     */
    public function dropField($tableName, $fieldName) {
        if (!$this->existsField($tableName, $fieldName)) {
            return false;
        }
        return $this->connection->query(
            'ALTER TABLE {:' . $tableName . ':} DROP COLUMN `' . $fieldName . '`'
        )->isSuccess();
    }

    /**
     * 重命名字段
     * @param $tableName
     * @param string $fieldName 老字段名称
     * @param string $newFieldName 新字段名称
     * @return bool
     */
    public function renameField($tableName, $fieldName, $newFieldName) {
        if (!$this->existsField($tableName, $fieldName) ||
            (($fieldName != $newFieldName) && $this->existsField($tableName, $newFieldName))
        ) {
            return false;
        }
        $sql = 'ALTER TABLE {:' . $tableName . ':} CHANGE ' . $fieldName . ' ' . $newFieldName;
        return $this->connection->query($sql)->isSuccess();
    }

    /**
     * 修改字段属性
     *
     * @param $tableName
     * @param $fieldName
     * @param string $spec
     *
     * @return bool
     */
    public function alertField($tableName, $fieldName, $spec) {
        if (!$this->existsField($tableName, $fieldName)) {
            return false;
        }
        $sql = 'ALTER TABLE {:' . $tableName . ':} CHANGE ' . $fieldName . ' ' . $fieldName . ' ' . $spec;
        return $this->connection->query($sql)->isSuccess();
    }

    /**
     * 设置字段默认值
     */
    public function fieldSetDefault($tableName, $fieldName, $defaultValue = null) {
        if (!$this->existsField($tableName, $fieldName)) {
            return false;
        }
        $defaultValue =
            !$defaultValue ? 'NULL' : (is_string($defaultValue) ? '"' . $defaultValue . '"' : $defaultValue);
        return $this->connection->query(
            'ALTER TABLE {:' . $tableName . ':} ALTER COLUMN `' . $fieldName . '` SET DEFAULT ' . $defaultValue
        )->isSuccess();
    }

    /**
     * 删除字段默认值
     */
    public function fieldSetNoDefault($tableName, $fieldName) {
        if (!$this->existsField($tableName, $fieldName)) {
            return false;
        }
        return $this->connection->query(
            'ALTER TABLE {:' . $tableName . ':} ALTER COLUMN `' . $fieldName . '` DROP DEFAULT'
        )->isSuccess();
    }

    /**
     * 添加主键
     */
    public function addPrimaryKey($tableName, $fields) {
        if (!$this->existsTable($tableName) || $this->existsIndex($tableName, 'PRIMARY')) {
            return false;
        }
        return $this->connection->query(
            'ALTER TABLE {:' . $tableName . ':} ADD PRIMARY KEY (' . $this->createKeySql($fields) . ')'
        )->isSuccess();
    }

    /**
     * 删除主键
     */
    public function dropPrimaryKey($tableName) {
        if (!$this->existsIndex($tableName, 'PRIMARY')) {
            return false;
        }
        return $this->connection->query('ALTER TABLE {:' . $tableName . ':} DROP PRIMARY KEY')->isSuccess();
    }

    /**
     * 添加唯一索引
     */
    public function addUniqueKey($tableName, $uniqueKeyName, $fieldName) {
        if (!$this->existsTable($tableName) || $this->existsIndex($tableName, $uniqueKeyName)) {
            return false;
        }
        return $this->connection->query(
            'ALTER TABLE {:' .
            $tableName .
            ':} ADD UNIQUE KEY `' .
            $uniqueKeyName .
            '` (' .
            $this->createKeySql($fieldName) .
            ')'
        )->isSuccess();
    }

    /**
     * 删除唯一索引
     */
    public function dropUniqueKey($tableName, $uniqueKeyName) {
        if (!$this->existsIndex($tableName, $uniqueKeyName)) {
            return false;
        }
        return $this->connection->query(
            'ALTER TABLE {:' . $tableName . ':} DROP KEY `' . $uniqueKeyName . '`'
        )->isSuccess();
    }

    /**
     * 添加索引
     */
    public function addIndex($tableName, $indexName, $fieldName) {
        if (!$this->existsTable($tableName) || $this->existsIndex($tableName, $indexName)) {
            return false;
        }
        return $this->connection->query(
            'ALTER TABLE {:' .
            $tableName .
            ':} ADD INDEX `' .
            $indexName .
            '` (' .
            $this->createKeySql($fieldName) .
            ')'
        )->isSuccess();
    }

    /**
     * 删除索引
     */
    public function dropIndex($tableName, $indexName) {
        if (!$this->existsIndex($tableName, $indexName)) {
            return false;
        }
        return $this->connection->query(
            'ALTER TABLE {:' . $tableName . ':} DROP INDEX `' . $indexName . '`'
        )->isSuccess();
    }

    //拼缀索引SQL
    protected function createKeySql($fields) {
        $return = array();
        foreach ((array)$fields as $field) {
            if (is_array($field)) {
                $return[] = '`' . $field[0] . '`(' . $field[1] . ')';
            }
            else {
                $return[] = '`' . $field . '`';
            }
        }
        return implode(', ', $return);
    }

    protected function prefix($table) {
        return $this->dbConfig['prefix'] . $table;
    }

    /**
     * 执行获取Schema
     */
    protected function explainSchema($databaseName) {
        $sqlSchema = 'SELECT table_name, column_name, column_default, is_nullable, data_type, column_comment ';
        $sqlSchema .= "\nFROM information_schema.columns ";
        $sqlSchema .= "\nWHERE table_schema = :database ";

        $info = $this->connection->query($sqlSchema, array(':database' => $databaseName))->fetchAll(\PDO::FETCH_OBJ);

        foreach ($info as $v) {
            $this->schema[$v->tableName][$v->columnName] = array(
                'description' => $v->columnComment,
                'type' => $v->dataType,
                'default' => $v->columnDefault,
                'not null' => $v->isNullable == 'NO',
            );
        }
        return $this;
    }
}

