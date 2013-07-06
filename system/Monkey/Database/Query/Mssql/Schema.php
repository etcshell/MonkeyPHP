<?php

/**
 * @file
 * Definition of Drupal\Core\Database\Driver\mysql\Schema
 */

namespace Monkey\Database\Query\Mssql;

use Monkey\Database\Query;

class Schema extends Query\Schema
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * Schema信息
     * @var array
     */
    protected $schema = array();

    protected
        $dbConfig
    ;

    /**
     * @param Connection $connection
     * @param string|null $databaseName 数据库名，默认使用连接中的数据库
     */
    public function __construct(Connection $connection, $databaseName=null)
    {
        $this->connection=$connection;
        $this->dbConfig=$connection->getConfig();
        if(!$databaseName){
            $databaseName=$this->dbConfig['dbname'];
        }
        //$this->explainSchema($databaseName);
    }

    /**
     * 获取Schema信息
     */
    public function getSchema() {
        return $this->schema;
    }

    /**
     * 表是否存在
     */
    public function existsTable($tableName)
    {
        $sql="select count(*) as table_count from sysobjects where [name] = '{:$tableName:}' and xtype='U'";
        $result=$this->connection->query($sql)->getResultStmt()->fetch(\PDO::FETCH_ASSOC);
        return isset($result['table_count']);
    }

    /**
     * 字段是否存在
     */
    public function existsField($tableName, $columnName)
    {
        $sql="select name as columnName from syscolumns where id=object_id('{:$tableName:}') and name=$columnName";
        $result=$this->connection->query($sql)->getResultStmt()->fetch(\PDO::FETCH_ASSOC);
        return isset($result['columnName']);
    }

    /**
     * 索引是否存在
     */
    public function existsIndex($tableName, $indexName)
    {
        $sql="select count(*) as index_count from sysindexes where id=object_id('{:$tableName:}') and name='$indexName'";
        $result = $this->connection->query($sql)->getResultStmt()->fetch(\PDO::FETCH_ASSOC);
        return isset($result['index_count']);
    }

    /**
     * 创建表
     * @param string $tableName  表名
     * @param string $tableCreateSql 创建表的sql
     * @return bool
     */
    public function createTable($tableName, $tableCreateSql)
    {
        if ($this->existsTable($tableName)) {
            return false;
        }
        return $this->connection->query($tableCreateSql)->isSuccess();
    }

    /**
     * 删除表
     */
    public function dropTable($tableName)
    {
        return $this->connection->query('DROP TABLE {:'.$tableName.':}')->isSuccess();
    }

    /**
     * 修改表名
     */
    public function renameTable($tableName, $newTableName)
    {
        return $this->connection->query('sp_rename {:'.$tableName.':}, {:'.$newTableName.':}')->isSuccess();
    }

    /**
     * 清空数据表
     * @param string $tableName 表名称
     * @return boolean
     */
    public function truncateTable($tableName)
    {
        if(empty($tableName)) {
            return false;
        }
        $sql= 'TRUNCATE TABLE {:'.$tableName.':}';
        return $this->connection->query($sql)->isSuccess();
    }

    /**
     * 添加字段
     */
    public function addField($tableName, $fieldName, $spec) {
        $sql='ALTER TABLE {:'.$tableName.':} ADD '.$fieldName.' '.$spec;
        return $this->connection->query($sql)->isSuccess();
    }

    /**
     * 删除字段
     */
    public function dropField($tableName, $fieldName) {
        return $this->connection->query('ALTER TABLE {:'.$tableName.':} DROP COLUMN `'. $fieldName .'`')->isSuccess();
    }

    /**
     * 重命名字段
     * @param $tableName
     * @param string $fieldName      老字段名称
     * @param string $newFieldName 	新字段名称
     * @return bool
     */
    public function renameField($tableName, $fieldName, $newFieldName){
        $sql='sp_rename {:'.$tableName.':}.'.$fieldName.', '.$newFieldName.', "COLUMN"';
        return $this->connection->query($sql)->isSuccess();
    }

    /**
     * 修改字段属性
     * @param $tableName
     * @param $fieldName
     * @param string $spec
     * @return bool
     */
    public function alertField($tableName, $fieldName, $spec){
        $sql='ALTER TABLE {:'.$tableName.':} ALTER COLUMN '.$fieldName.' '.$spec;
        return $this->connection->query($sql)->isSuccess();
    }
/////////////////////////////////////////////////////////////////////
    /**
     * 设置字段默认值
     */
    public function fieldSetDefault($tableName, $fieldName, $defaultValue=null, $defaultName=null)
    {
        $defaultName = !$defaultName ? $fieldName.'_defaultValue' : $defaultName;
        $defaultValue = !$defaultValue? 'NULL' : (is_string($defaultValue) ? '"'.$defaultValue.'"' : $defaultValue);
        $this->connection->query('ALTER TABLE {:'.$tableName.':} DROP CONSTRAINT '.$defaultName);
        return $this->connection->query('ALTER TABLE {:'.$tableName.':} ADD CONSTRAINT '.$defaultName.' DEFAULT '.$defaultValue.' FOR '.$fieldName)->isSuccess();
    }

    /**
     * 删除字段默认值
     */
    public function fieldSetNoDefault($tableName, $fieldName, $defaultName=null)
    {
        $defaultName = !$defaultName ? $fieldName.'_defaultValue' : $defaultName;
        return $this->connection->query('ALTER TABLE {:'.$tableName.':} DROP CONSTRAINT '.$defaultName)->isSuccess();
    }

    /**
     * 添加主键
     */
    public function addPrimaryKey($tableName, $fields)
    {
        return false;
    }

    /**
     * 删除主键
     */
    public function dropPrimaryKey($tableName)
    {
        return false;
    }

    /**
     * 添加唯一索引
     */
    public function addUniqueKey($tableName, $uniqueKeyName, $fieldName)
    {
        return false;
    }

    /**
     * 删除唯一索引
     */
    public function dropUniqueKey($tableName, $uniqueKeyName)
    {
        return false;
    }

    /**
     * 添加索引
     */
    public function addIndex($tableName, $indexName, $fieldName)
    {
        return false;
    }

    /**
     * 删除索引
     */
    public function dropIndex($tableName, $indexName)
    {
        return false;
    }

}

