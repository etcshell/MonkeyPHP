<?php
namespace Monkey\Database;

/**
 * 数据删除工具 Delete
 * @package Monkey\Database
 */

class Delete
{
    /**
     * @var \Monkey\App\App $app
     */
    public $app;
    /**
     * @var Connection
     */
    public $connection;

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
        $this->app=$connection->app;
        $this->queryIdentifier=uniqid('', TRUE);
        $this->connection=$connection;
        $this->table = $table;
        $this->condition = new Condition($this->app,'AND');
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
    public function where($fieldName, $value = NULL, $operator = NULL)
    {
        $this->condition->where($fieldName, $value, $operator);
        return $this;
    }

    /**
     * 设置字段条件isNull
     * @param $fieldName
     * @return $this
     */
    public function isNull($fieldName)
    {
        $this->condition->where($fieldName, NULL, 'IS NULL');
        return $this;
    }

    /**
     * 设置字段条件isNotNull
     * @param $fieldName
     * @return $this
     */
    public function isNotNull($fieldName)
    {
        $this->condition->where($fieldName, NULL, 'IS NOT NULL');
        return $this;
    }

    /**
     * 设置条件片段
     * @param $snippet
     * @param array $args
     * @return $this
     */
    public function condition($snippet, $args = array())
    {
        $this->condition->condition($snippet, $args);
        return $this;
    }

    /**
     * @param Select $select
     * @return $this
     */
    public function exists(Select $select) {
        $this->condition->where( '', $select, 'EXISTS');
        return $this;
    }

    /**
     * @param Select $select
     * @return $this
     */
    public function notExists(Select $select) {
        $this->condition->where( '', $select, 'NOT EXISTS');
        return $this;
    }

    /**
     * 执行删除
     * @param int $returnType
     * @return Connection|\PDOStatement|int
     */
    public function execute($returnType=Database::RETURN_AFFECTED)
    {
        $query=$this->compile();
        return $this->connection->query( $query['sql'], $returnType, $query['arguments'] );
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
