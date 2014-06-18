<?php
namespace Monkey\Database;

/**
 * ActiveRecorder
 * 活动记录行类
 * @package Monkey\Database
 */
class ActiveRecorder{
    public
        $table,
        $fields=array(),
        $priKey,
        $affected,
        /**
         * @var \Monkey\Database\Connection
         */
        $connection;

    /**
     * 构造方法
     * @param \Monkey\Database\Connection $connection 数据层管理器
     * @param string $table 表名
     * @param string $priKey 主键
     */
    public function  __construct($connection, $table, $priKey){
        $this->connection= $connection;
        $this->table= $table;
        $this->priKey= $priKey;
    }

    /**
     * 去除一个字段
     * @param $field
     */
    public function wipeField($field=null)
    {
        !$field and $field=$this->priKey;
        $this->fields[$field]=null;
        unset($this->fields[$field]);
    }

    /**
     * 获取字段值
     * @param $field
     * @param null $defaultValue
     * @return mixed|null
     */
    public function get($field, $defaultValue=null)
    {
        return array_key_exists($field,$this->fields) ? $this->fields[$field] : $defaultValue;
    }

    /**
     * 获取主键值
     * @return string|int
     */
    public function getPriValue()
    {
        return $this->fields[$this->priKey];
    }

    /**
     * 设置主键值
     * @param string|int $value
     */
    public function setPriValue($value)
    {
        $this->fields[$this->priKey]=$value;
    }

    /**
     * 按条件选取
     * @param string $whereField 条件字段，默认为主键
     * @param mixed $whereValue 条件值
     * @return bool
     */
    public function select($whereField=null,$whereValue=null)
    {
        if(!$whereField){
            if(!$this->priKey)return false;
            $whereField=$this->priKey;
            $whereValue=$this->fields[$this->priKey];
        }
        $select= $this->connection->select($this->table);
        if(empty($whereValue))
            $select->isNull($whereField);
        else
            $select->where($whereField,$whereValue);
        $this->fields= $select
            ->limit(0,1)
            ->execute()
            ->fetch(\PDO::FETCH_ASSOC);
        return $this->connection->lastStmt()->isSuccess();
    }

    /**
     * 选取下一条记录
     * @return bool
     */
    public function next()
    {
        if($this->fields[$this->priKey]) ++$this->fields[$this->priKey];
        else $this->fields[$this->priKey]=0;
        return $this->select();
    }

    /**
     * 选取上一条记录
     * @return bool
     */
    public function previous()
    {
        if($this->fields[$this->priKey]) --$this->fields[$this->priKey];
        else return false;
        return $this->select();
    }

    /**
     * 插入
     * @return bool
     */
    public function insert()
    {
        $this->wipeField();
        if(empty($this->fields))return false;
        $this->fields[$this->priKey]= $this->connection
            ->insert($this->table)
            ->setFields($this->fields)
            ->execute()->lastInsertId();
        return $this->connection->lastStmt()->isSuccess();
    }

    /**
     * 按条件删除
     * @param string $whereField 条件字段，默认为主键
     * @param mixed $whereValue 条件值
     * @return bool
     */
    public function delete($whereField=null,$whereValue=null)
    {
        $this->affected=null;
        if(!$whereField){
            if(!$this->priKey)return false;
            $whereField=$this->priKey;
            $whereValue=$this->fields[$this->priKey];
        }
        $delete= $this->connection->delete($this->table);
        if(empty($whereValue))
            $delete->isNull($whereField);
        else
            $delete->where($whereField,$whereValue);
        $this->affected= $delete->execute()->affected();
        $this->affected and $this->fields=array();
        return $this->connection->lastStmt()->isSuccess();
    }

    /**
     * 按条件更新
     * @param string $whereField 条件字段，默认为主键
     * @param mixed $whereValue 条件值
     * @return bool
     */
    public function update($whereField=null,$whereValue=null)
    {
        $this->affected=null;
        if(!$whereField){
            if(!$this->priKey)return false;
            $whereField=$this->priKey;
            $whereValue=$this->fields[$this->priKey];
        }
        $focus=$this->fields;
        $focus[$this->priKey]=null;
        unset($focus[$this->priKey]);
        $update=  $this->connection->update($this->table)->addFieldsValue($focus);
        if(empty($whereValue))
            $update->isNull($whereField);
        else
            $update->where($whereField,$whereValue);
        $this->affected= $update->execute()->affected();
        return $this->connection->lastStmt()->isSuccess();
    }

    /**
     * 填充数据，未指定的字段值由字段格式中的默认值填充
     * @param array $data 指定的行数据
     * @param array $formatData 字段格式
     * @return array
     */
    public function dataFill(array $data=array(),array $formatData)
    {
        foreach ($data as $field => $value) {
            array_key_exists($field,$formatData) and $formatData[$field]=$value;
        }
        return $formatData;
    }

    /**
     * 过滤数据，字段格式中的字段才会保留下来
     * @param array $data 指定的行数据
     * @param array $formatData 字段格式
     * @return array
     */
    public function dataFilter(array $data=array(),array $formatData)
    {
        $t=array();
        if(empty($data)) return $t;
        foreach ($formatData as $field => $value) {
            array_key_exists($field,$data) and $t[$field]=$data[$field];
        }
        return $t;
    }
}