<?php

namespace Monkey\Database\Sqlite;

use Monkey\Database as Query;

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
        /*if(!$databaseName){
            $databaseName=$this->dbConfig['dbname'];
        }
        $this->explainSchema($databaseName);*/
    }

    /**
     * 表是否存在
     */
    public function existsTable($tableName)
    {
        $sql="SELECT COUNT(*)  as table_count FROM sqlite_master where type='table' and name='{:$tableName:}'";
        $result=$this->connection->query($sql, 1)->fetch(\PDO::FETCH_ASSOC);
        return isset($result['table_count']);
    }

    /**
     * 字段是否存在
     */
    public function existsField($tableName, $columnName)
    {
        $sql='PRAGMA table_info({:'.$tableName.':})';
        if(!$this->connection->query($sql)->isSuccess())return FALSE;
        $tableMate=$this->connection->getResultStmt()->fetchAll(\PDO::FETCH_ASSOC);
        foreach($tableMate as $field){
            if($field['name']==$columnName) return true;
        }
        return false;
    }

    /**
     * 索引是否存在
     */
    public function existsIndex($tableName, $indexName)
    {
        $sql="SELECT count(*) AS index_count FROM sqlite_master WHERE type='index' AND tbl_name='{:$tableName:}' AND name='$indexName'";
        if(!$this->connection->query($sql)->isSuccess())return FALSE;
        $result=$this->connection->query($sql, 1)->fetch(\PDO::FETCH_ASSOC);
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
        return $this->connection->query($tableCreateSql)->isSuccess();
    }

    /**
     * 删除表
     */
    public function dropTable($tableName)
    {
        return $this->connection->query('DROP TABLE IF EXISTS {:'.$tableName.':}')->isSuccess();
    }

    /**
     * 修改表名
     */
    public function renameTable($tableName, $newTableName)
    {
        return $this->connection->query('ALTER TABLE {:'.$tableName.':} RENAME TO {:'.$newTableName.':}')->isSuccess();
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
        $sql= 'DELETE FROM {:'.$tableName.':}';
        if($this->connection->query($sql)->isSuccess()){
            $sql="UPDATE sqlite_sequence SET seq = 0 WHERE name = '{:$tableName:}'";
            $this->connection->query($sql);
            return true;
        }
        return false;
    }

    /**
     * 添加字段
     */
    public function addField($tableName, $fieldName, $spec)
    {
        $sql='ALTER TABLE {:'.$tableName.':} ADD COLUMN '.$fieldName.' '.$spec;
        return $this->connection->query($sql)->isSuccess();
    }

    /**
     * 删除字段
     */
    public function dropField($tableName, $fieldName)
    {
        if(empty($fieldName))return false;
        $sql='PRAGMA table_info({:'.$tableName.':})';
        if(!$this->connection->query($sql)->isSuccess())return FALSE;
        $tableMate=$this->connection->getResultStmt()->fetchAll(\PDO::FETCH_ASSOC);
        $fieldExists=false;
        $fields=array();
        $tempTable="sqlite_mk_temp_{:$tableName:}";
        $createTempTable="CREATE TABLE '$tempTable' (\n";
        foreach($tableMate as $field){
            if($field['name']!=$fieldName){
                $fields[]=$field['name'];
                $createTempTable.='"'.$field['name'].'" '.$field['type'];
                $field['pk'] and $createTempTable.=' PRIMARY KEY AUTOINCREMENT';
                $field['notnull'] and $createTempTable.=' NOT NULL';
                $field['dflt_value'] and $createTempTable.=' DEFAULT '.$field['dflt_value'];
                $createTempTable.="\n";
            }
            else{
                $fieldExists=true;
            }
        }
        if(!$fieldExists)return false;
        $createTempTable.=')';
        $fields=implode(', ',$fields);
        if(!$this->connection->query($createTempTable)->isSuccess())return FALSE;
        $insertToTemp="INSERT INTO $tempTable SELECT {$fields} FROM {:$tableName:}";
        if(!$this->connection->query($insertToTemp)->isSuccess())return FALSE;
        $sql="DROP TABLE IF EXISTS {:$tableName:}";
        if(!$this->connection->query($sql)->isSuccess())return FALSE;
        $sql="ALTER TABLE $tempTable RENAME TO {:$tableName:}";
        if(!$this->connection->query($sql)->isSuccess())return FALSE;
        return true;
    }

    /**
     * 重命名字段
     * @param $tableName
     * @param string $fieldName      老字段名称
     * @param string $newFieldName  新字段名称
     * @return bool
     */
    public function renameField($tableName, $fieldName, $newFieldName)
    {
        $sql="SELECT sql FROM sqlite_master WHERE type='table' AND name='{:$tableName:}'";
        if(!$this->connection->query($sql)->isSuccess())return FALSE;
        $sql=$this->connection->getResultStmt()->fetch(\PDO::FETCH_ASSOC);
        $sql=$sql['sql'];
        if(empty($sql)) return false;
        $tempTable="sqlite_mk_temp_{:$tableName:}";
        $sql="CREATE TABLE '$tempTable' ".strstr($sql,'(');
        $createTempTable=str_replace($fieldName, $newFieldName,$sql);

        $sql='PRAGMA table_info({:'.$tableName.':})';
        if(!$this->connection->query($sql)->isSuccess())return FALSE;
        $tableMate=$this->connection->getResultStmt()->fetchAll(\PDO::FETCH_ASSOC);
        $fields=array();
        foreach($tableMate as $field){
            $fields[]= $field['name']==$fieldName ? "$fieldName AS $newFieldName" : $field['name'];
        }
        $fields=implode(', ',$fields);

        if(!$this->connection->query($createTempTable)->isSuccess())return FALSE;
        $insertToTemp="INSERT INTO $tempTable SELECT {$fields} FROM {:$tableName:}";
        if(!$this->connection->query($insertToTemp)->isSuccess())return FALSE;
        $sql="DROP TABLE IF EXISTS {:$tableName:}";
        if(!$this->connection->query($sql)->isSuccess())return FALSE;
        $sql="ALTER TABLE $tempTable RENAME TO {:$tableName:}";
        if(!$this->connection->query($sql)->isSuccess())return FALSE;
        return true;
    }

    /**
     * 修改字段属性
     * @param $tableName
     * @param $fieldName
     * @param string $spec
     * @return bool
     */
    public function alertField($tableName, $fieldName, $spec)
    {
        return false;//SQLite数据库系统不允许修改字段属性
    }

    /**
     * 设置字段默认值
     */
    public function fieldSetDefault($tableName, $fieldName, $defaultValue=null)
    {
        return false;//SQLite数据库系统不允许修改字段属性
    }

    /**
     * 设置字段无默认值
     */
    public function fieldSetNoDefault($tableName, $fieldName)
    {
        return false;//SQLite数据库系统不允许修改字段属性
    }

    /**
     * 添加主键
     */
    public function addPrimaryKey($tableName, $fields)
    {
        return false;
        //sqlite中所有表都有一个伪字段rowid
        //这个是自增的可以被Select语句选中，所以没有必要再人为设置主键了
        //另外这个rowid的查询效率还极高！写入效率快8%左右。
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
        is_array($fieldName) and $fieldName=implode(', ',$fieldName);
        $sql="CREATE UNIQUE INDEX $uniqueKeyName ON {:$tableName:}($fieldName)";
        return $this->connection->query($sql)->isSuccess();
    }

    /**
     * 删除唯一索引
     */
    public function dropUniqueKey($tableName, $uniqueKeyName)
    {
        $sql="DROP INDEX IF EXISTS $uniqueKeyName";
        return $this->connection->query($sql)->isSuccess();
    }

    /**
     * 添加索引
     */
    public function addIndex($tableName, $indexName, $fieldName)
    {
        is_array($fieldName) and $fieldName=implode(', ',$fieldName);
        $sql="CREATE INDEX $indexName ON {:$tableName:}($fieldName)";
        return $this->connection->query($sql)->isSuccess();
    }

    /**
     * 删除索引
     */
    public function dropIndex($tableName, $indexName)
    {
        $sql="DROP INDEX IF EXISTS $indexName";
        return $this->connection->query($sql)->isSuccess();
    }

    //拼缀索引SQL
    protected function createKeySql($fields)
    {
        return '';
    }

    /**
     * 执行获取Schema
     */
    protected function explainSchema($databaseName)
    {
        return $this;
    }
}

