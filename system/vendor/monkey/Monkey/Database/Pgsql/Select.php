<?php

namespace Monkey\Database\Pgsql;

use Monkey\Database as Query;

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
        $this->app= $connection->app;
        $this->connection=$connection;
        $this->queryIdentifier=uniqid('', TRUE);
        $conjunction  = isset($options['conjunction']) ? $options['conjunction'] : 'AND';
        $this->where  = new Condition($this->app, $conjunction);
        $this->having = new Condition($this->app, $conjunction);
        $this->addJoin(NULL, $table, $alias);
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
     * Overrides SelectQuery::orderBy().
     *
     * PostgreSQL adheres strictly to the SQL-92 standard and requires that when
     * using DISTINCT or GROUP BY conditions, fields and expressions that are
     * ordered on also need to be selected. This is a best effort implementation
     * to handle the cases that can be automated by adding the field if it is not
     * yet selected.
     *
     * @code
     *   $query = db_select('node', 'n');
     *   $query->join('node_revision', 'nr', 'n.vid = nr.vid');
     *   $query
     *     ->distinct()
     *     ->fields('n')
     *     ->orderBy('timestamp');
     * @endcode
     *
     * In this query, it is not possible (without relying on the schema) to know
     * whether timestamp belongs to node_revisions and needs to be added or
     * belongs to node and is already selected. Queries like this will need to be
     * corrected in the original query by adding an explicit call to
     * SelectQuery::addField() or SelectQuery::fields().
     *
     * Since this has a small performance impact, both by the additional
     * processing in this function and in the database that needs to return the
     * additional fields, this is done as an override instead of implementing it
     * directly in SelectQuery::orderBy().
     */
    public function orderBy($field, $direction = 'ASC') {
        // Call parent function to order on this.
        $return = parent::orderBy($field, $direction);

        // If there is a table alias specified, split it up.
        if (strpos($field, '.') !== FALSE) {
            list($table, $table_field) = explode('.', $field);
        }
        // Figure out if the field has already been added.
        foreach ($this->fields as $existing_field) {
            if (!empty($table)) {
                // If table alias is given, check if field and table exists.
                if ($existing_field['table'] == $table && $existing_field['field'] == $table_field) {
                    return $return;
                }
            }
            else {
                // If there is no table, simply check if the field exists as a field or
                // an aliased field.
                if ($existing_field['alias'] == $field) {
                    return $return;
                }
            }
        }

        // Also check expression aliases.
        foreach ($this->expressions as $expression) {
            if ($expression['alias'] == $field) {
                return $return;
            }
        }

        // If a table loads all fields, it can not be added again. It would
        // result in an ambigious alias error because that field would be loaded
        // twice: Once through table_alias.* and once directly. If the field
        // actually belongs to a different table, it must be added manually.
        foreach ($this->tables as $table) {
            if (!empty($table['all_fields'])) {
                return $return;
            }
        }

        // If $field contains an characters which are not allowed in a field name
        // it is considered an expression, these can't be handeld automatically
        // either.
        $field1=preg_replace('/[^A-Za-z0-9_.]+/', '', $field);
        if ($field1 != $field) {
            return $return;
        }

        // This is a case that can be handled automatically, add the field.
        $this->addField(NULL, $field);
        return $return;
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
        $query .= $fields ? implode(', ', $fields) : '*';
        // FROM
        $query .= "\nFROM ";
        foreach ($this->tables as $table){
            $query .= "\n";
            isset($table['join type']) and $query .= $table['join type'] . ' JOIN ';

            if ($table['table'] instanceof Select){
                $table_string = '(' . $table['table']->getString($qi) . ')';
            }
            else{
                $table_string = '{:' . $table['table'] . ':}';
            }

            $query .= $table_string . ' AS ' . $table['alias'];
            !empty($table['condition']) and $query .= ' ON ' . $table['condition'];
        }
        // WHERE
        count($this->where) and $query .= "\nWHERE " . $this->where->getString($qi);
        // GROUP BY
        $this->group and $query .= "\nGROUP BY " . implode(', ', $this->group);
        // HAVING
        count($this->having) and $query .= "\nHAVING " . $this->having->getString($qi);
        // ORDER BY
        if ($this->order) {
            $query .= "\nORDER BY ";
            $fields = array();
            foreach ($this->order as $field => $direction) {
                $fields[] = $field . ' ' . $direction;
            }
            $query .= implode(', ', $fields);
        }
        // RANGE
        !empty($this->range) and $query .= "\nLIMIT " . (int) $this->range['length'] . ' OFFSET ' . (int) $this->range['start'];
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
     * @param string $table_alias 表名或表别名
     * @param string $field 字段名
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