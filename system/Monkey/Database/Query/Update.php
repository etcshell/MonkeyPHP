<?php

namespace Monkey\Database\Query;

class Update
{
    /**
     * @var Connection
     */
    protected $connection;
    /**
     * @var Condition
     */
    protected $condition;

    protected
        $queryIdentifier,
        $table,
        $fields = array(),
        $expressionFields = array()
    ;

    /**
     * @param Connection $connection
     * @param $table
     */
    public function __construct(Connection $connection, $table)
    {
        $this->connection=$connection;
        $this->queryIdentifier=uniqid('', TRUE);
        $this->table = $table;
        $this->condition = new Condition($this->queryIdentifier,'AND');
    }

    public function __clone()
    {
        $this->queryIdentifier=uniqid('', TRUE);
        $this->condition  = clone($this->condition);
        $this->condition->setQueryIdentifierAfterClone($this->queryIdentifier);
    }

    public function __destruct()
    {
        $this->condition=null;
        $this->fields=null;
        $this->expressionFields=null;
    }

    /**
     * 设置更新条件
     * @param $fieldName
     * @param null $value
     * @param null $operator
     * @return $this
     */
    public function whereField($fieldName, $value = NULL, $operator = NULL)
    {
        $this->condition->whereField($fieldName, $value, $operator);
        return $this;
    }

    /**
     * 设置字段条件isNull
     * @param $fieldName
     * @return $this
     */
    public function whereFieldIsNull($fieldName)
    {
        $this->condition->whereField($fieldName, NULL, 'IS NULL');
        return $this;
    }

    /**
     * 设置字段条件isNotNull
     * @param $fieldName
     * @return $this
     */
    public function whereFieldIsNotNull($fieldName)
    {
        $this->condition->whereField($fieldName, NULL, 'IS NOT NULL');
        return $this;
    }

    /**
     * 设置条件片段
     * @param $snippet
     * @param array $args
     * @return $this
     */
    public function whereCondition($snippet, $args = array())
    {
        $this->condition->whereCondition($snippet, $args);
        return $this;
    }

    /**
     * @param Select $select
     * @return $this
     */
    public function whereExists(Select $select) {
        $this->condition->whereField( '', $select, 'EXISTS');
        return $this;
    }

    /**
     * @param Select $select
     * @return $this
     */
    public function whereNotExists(Select $select) {
        $this->condition->whereField( '', $select, 'NOT EXISTS');
        return $this;
    }

    /**
     * 添加更新字段值列表
     * @param array $fieldsValue 如 array(field1=>value1,...)
     * @return $this
     */
    public function addFieldsValue(array $fieldsValue)
    {
        $this->fields = array_merge($this->fields, $fieldsValue);
        return $this;
    }

    /**
     * 设置更新字段值列表
     * @param array $fieldsValue 如 array(field1=>value1,...)
     * @return $this
     */
    public function setFieldsValue(array $fieldsValue)
    {
        $this->fields = $fieldsValue;
        return $this;
    }

    /**
     * 添加更新字段值表达式
     * @param $field
     * @param $expression
     * @param array $arguments
     * @return $this
     */
    public function addFieldsValueByExpression($field, $expression, array $arguments = NULL)
    {
        $this->expressionFields[$field] = array(
            'expression' => $expression,
            'arguments'  => $arguments,
        );
        return $this;
    }

    /**
     * 执行更新
     * @return Connection
     */
    public function execute()
    {
        $query=$this->compile();
        $this->connection->query( $query['sql'], $query['arguments'] );
        return $this->connection;
    }

    protected function compile()
    {
        $fields = $this->fields;
        $update_fields = array();
        $update_values = array();
        foreach ($this->expressionFields as $field => $data) {
            !empty($data['arguments']) and $update_values += $data['arguments'];
            if ($data['expression'] instanceof Select) {
                $update_values += $data['expression']->getArguments($this->queryIdentifier);
                $data['expression'] = ' (' . $data['expression']->getString($this->queryIdentifier) . ')';
            }
            $update_fields[] = $field . '=' . $data['expression'];
            unset($fields[$field]);
        }
        $max_placeholder = $placeholder=0;
        foreach ($fields as $field => $value) {
            $placeholder=':mk_update_placeholder_' . ($max_placeholder++);
            $update_fields[] = $field . '=' . $placeholder;
            $update_values[$placeholder] = $value;
        }
        $query = 'UPDATE {:' . $this->table . ':} SET ' . implode(', ', $update_fields);
        if (count($this->condition)){
            $query .= "\nWHERE " . $this->condition->getString($this->queryIdentifier);
            $update_values = array_merge($update_values, $this->condition->getArguments($this->queryIdentifier));
        }
        return array('sql'=>$query,'arguments'=>$update_values);
    }

}
