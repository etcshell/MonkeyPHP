<?php

namespace Monkey\Database\Pgsql;

use Monkey\Database as Query;

class Insert extends Query\Insert
{

    /**
     * @param Connection $connection
     * @param $table
     */
    public function __construct(Connection $connection, $table)
    {
        $this->app=$connection->app;
        $this->connection=$connection;
        $this->table = $table;
    }

    /**
     * 使用表结构填充插入字段
     * @return $this
     * @throws \Exception
     */
    public function useTableMate()
    {
        if($this->usedTableMate){
            return $this;
        }
        $table=$this->table;
        $connection=$this->connection;
        $cache_key=serialize($connection->getConfig()).'_mate_'.$table;
        $mate=null;
        $cacher=$this->app->cache();
        if(!$cacher->fetch($cache_key,$mate)){
            $mate=$connection->getTableMate($table);
            $cacher->store($cache_key,$mate);
        }
        if(empty($mate)){
            throw new \Exception('表【'.$table. '】的字段信息读取失败!');
        }
        $this->fieldsValuesDefault=$mate['fields_default'];
        $this->fieldsValuesType=$mate['fields_type'];
        $this->priName=$mate['pri_name'];
        $this->isAutoPri=$mate['pri_is_auto'];
        return $this;
    }

    /**
     * 获取编译好的插入预处理语句
     * @return string
     */
    protected function compile()
    {
        $query=array();
        $fields = $this->fields;
        if ($this->fromQuery instanceof Select) {
            $query['sql']='INSERT INTO {:' . $this->table . ':} (' . implode(', ', $fields) . ') ' . $this->fromQuery->getString();
            $query['arguments']=$this->fromQuery->getArguments();
            return $query;
        }
        $rowCount=count($this->rowValues);
        if(!$rowCount){
            return $query;
        }
        $query['sql']= 'INSERT INTO {:' . $this->table . ':} (' . implode(', ', $fields) . ') VALUES ';
        $placeholder_total = $placeholder = 0;
        $rowsPlaceholder = array();
        foreach ($this->rowValues as $rowValue){
            $placeholders = array();
            $rowArgument=array();
            foreach ($rowValue as $value){
                $placeholder=':mk_insert_placeholder_' . $placeholder_total++;
                $placeholders[] = $placeholder;
                $rowArgument[$placeholder]=$value;
            }
            $rowsPlaceholder[] = '(' . implode(', ', $placeholders) . ')';
            $query['arguments'] += $rowArgument;
        }
        $query['sql'] .= implode(', ', $rowsPlaceholder);
        return $query;
    }

    protected function checkFields(&$fields)
    {
        if($this->rowValues){
            $this->rowValues=null;
        }
        $isSetDefault=true;
        $_fields=array();
        if(is_numeric(key($fields))){
            foreach($fields as $field){
                $_fields[$field]=null;
            }
            $isSetDefault=false;
        }
        else{
            $_fields=$fields;
        }

        if($this->usedTableMate){
            $fields=array();
            foreach($_fields as $field=>$value){
                if(array_key_exists($field, $this->fieldsValuesDefault) ){
                    $fields[$field]= $isSetDefault ? $value : $this->fieldsValuesDefault[$field];
                }
            }
            if($this->isAutoPri and array_key_exists($this->priName,$fields)){
                unset($fields[$this->priName]);
            }
            $isSetDefault=true;
        }
        else{
            $fields=$_fields;
        }
        return $isSetDefault;
    }

}
