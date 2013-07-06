<?php

namespace Monkey\Database\Query\Pgsql;

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
        $this->distinct and $query .= 'DISTINCT ';
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
        // RANGE
        !empty($this->range) and $query .= '\nLIMIT ' . (int) $this->range['length'] . ' OFFSET ' . (int) $this->range['start'];
        $this->forUpdate and $query .= ' FOR UPDATE' . ($this->forUpdate===true ? '' : $this->forUpdate) ;

        return $query;
    }

    /**
     * 准备一个统计查询
     * @return Select
     */
    protected function prepareCountQuery()
    {
        $count = clone($this);
        $group_by = $count->getGroupBy();
        $having   = $count->getHavingConditions();
        if (!$count->distinct && !isset($having[0])){
            //去掉所有select字段
            $fields = &$count->getFields();
            foreach (array_keys($fields) as $field){
                if (empty($group_by[$field])) {
                    unset($fields[$field]);
                }
            }
            //去掉所有expressions字段
            $expressions = &$count->getFieldOfExpressions();
            foreach (array_keys($expressions) as $field){
                if (empty($group_by[$field]))  {
                    unset($expressions[$field]);
                }
            }
            //去掉*字段
            foreach ($count->tables as &$table){
                unset($table['all_fields']);
            }
        }
        $count->addFieldByExpression('mk_expression_holder', '1');
        $orders = &$count->getOrderBy();
        $orders = array();
        if ($count->distinct && !empty($group_by)) {
            $count->distinct = FALSE;
        }
        return $count;
    }

    /**
     * 添加一个字段到结果集中
     * @param $table_alias 表名或表别名
     * @param $field 字段名
     * @param null $alias 设置字段的别名
     * @return $this
     */
    protected function _addField($table_alias, $field, $alias = NULL)
    {
        empty($alias) and $alias = $field;
        !empty($this->fields[$alias]) and $alias = $table_alias . '_' . $field;
        $alias_candidate = $alias;
        $count = 2;
        while (!empty($this->fields[$alias_candidate])) {
            $alias_candidate = $alias . '_' . $count++;
        }
        $alias = $alias_candidate;
        $this->fields[$alias] = array (
            'field' => $field,
            'table' => $table_alias,
            'alias' => $alias,
        );

        return $this;
    }

}