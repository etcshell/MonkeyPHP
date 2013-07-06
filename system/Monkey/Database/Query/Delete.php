<?php

namespace Monkey\Database\Query;

class Delete
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
        $table
    ;

    /**
     * @param Connection $connection
     * @param string $table
     */
    public function __construct(Connection $connection, $table)
    {
        $this->queryIdentifier=uniqid('', TRUE);
        $this->connection=$connection;
        $this->table = $table;
        $this->condition = new Condition($this->queryIdentifier,'AND');
    }

    public function __destruct()
    {
        $this->condition=null;
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
     * 执行删除
     * @return Connection
     */
    public function execute()
    {
        $query=$this->compile();
        return $this->connection->query( $query['sql'] , $query['arguments'] );
    }

    protected function compile()
    {
        $query=array();
        $query['sql'] = 'DELETE FROM {:' . $this->table . ':} ';
        if (count($this->condition)) {
            $query['sql'] .= "\nWHERE " . $this->condition->getString($this->queryIdentifier);
            $query['arguments']= $this->condition->getArguments($this->queryIdentifier);
        }
        return $query;
    }
}
