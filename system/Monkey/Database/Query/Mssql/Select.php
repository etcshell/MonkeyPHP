<?php

namespace Monkey\Database\Query\Mssql;

use Monkey\Database\Query;

class Select extends Query\Select
{
    /**
     * @param Connection $connection
     * @param string $table
     * @param null $alias
     * @param array $options
     */
    public function __construct(Connection $connection, $table, $alias = NULL, $options = array())
    {
        $this->queryIdentifier=uniqid('', TRUE);
        $conjunction  = isset($options['conjunction']) ? $options['conjunction'] : 'AND';
        $this->where  = new Condition($this->queryIdentifier, $conjunction);
        $this->having = new Condition($this->queryIdentifier, $conjunction);
        $this->addJoin(NULL, $table, $alias);
        $this->connection=$connection;
    }

    /**
     * 锁定当前select结果对应的源行
     * @param bool|string $set true表示直接锁定， "of {:tableName:}.fieldName"表示仅锁定指定表中的源行
     * @return $this
     */
    public function forUpdate($set = TRUE)
    {
        return $this;
    }

    /**
     * 获取组装好的查询语句
     * @param null $queryIdentifier
     * @return string
     */
    public function getString( $queryIdentifier = NULL)
    {
        $qi= $queryIdentifier? $this->queryIdentifier=$queryIdentifier:$this->queryIdentifier;
        //!$this->compiled() and $this->compile($this);
        // SELECT
        $query =  'SELECT ';
        // RANGE
        !empty($this->range) and $query .= 'TOP ' .$this->range['length'] ;
        // DISTINCT
        $this->distinct and $query .= ' DISTINCT ';
        // FIELDS and EXPRESSIONS
        $fields = array();
        foreach ($this->tables as $alias => $table){
            !empty($table['all_fields']) and  $fields[] = $alias . '.*';
        }
        foreach ($this->fields as $field){
            $fields[] = (isset($field['table']) ? $field['table'] . '.' : '') . $field['field'] . ' AS ' . $field['alias'];
        }
        foreach ($this->expressions as $expression){
            $fields[] = $expression['expression'] . ' AS ' . $expression['alias'];
        }
        $query .= implode(', ', $fields);
        // FROM
        $query .= '\nFROM ';
        foreach ($this->tables as $table){
            $query .= "\n";
            isset($table['join type']) and $query .= $table['join type'] . ' JOIN ';

            if ($table['table'] instanceof Select){
                $table_string = '(' . $table['table']->getString($qi) . ')';
            }
            else{
                $table_string = '{:' . $table['table'] . ':}';
            }

            $query .= $table_string . ' ' . $table['alias'];
            !empty($table['condition']) and $query .= ' ON ' . $table['condition'];
        }
        // WHERE
        count($this->where) and $query .= '\nWHERE ' . $this->where->getString($qi);
        // GROUP BY
        $this->group and $query .= '\nGROUP BY ' . implode(', ', $this->group);
        // HAVING
        count($this->having) and $query .= '\nHAVING ' . $this->having->getString($qi);
        // ORDER BY
        if ($this->order) {
            $query .= '\nORDER BY ';
            $fields = array();
            foreach ($this->order as $field => $direction) {
                $fields[] = $field . ' ' . $direction;
            }
            $query .= implode(', ', $fields);
        }
        //$this->forUpdate and $query .= ' FOR UPDATE' . ($this->forUpdate===true ? '' : $this->forUpdate) ;
        return $query;
    }

}