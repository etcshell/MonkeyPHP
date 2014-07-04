<?php
/**
 * Project MonkeyPHP
 *
 * PHP Version 5.3.9
 *
 * @package   Monkey\Database
 * @author    黄易 <582836313@qq.com>
 * @version   GIT:<git_id>
 */
namespace Monkey\Database;

use Monkey;

/**
 * Class Delete
 *
 * 数据删除类
 *
 * @package Monkey\Database
 */
class Delete
{
    /**
     * 应用对象
     *
     * @var Monkey\App $app
     */
    public $app;

    /**
     * 连接对象
     *
     * @var Connection
     */
    public $connection;

    /**
     * 条件对象
     *
     * @var Condition
     */
    protected $condition;

    /**
     * 查询识别码
     *
     * @var string
     */
    protected $queryIdentifier;

    /**
     * 表名
     *
     * @var string
     */
    protected $table;

    /**
     * 构造方法
     *
     * @param Connection $connection
     * @param string $table
     */
    public function __construct(Connection $connection, $table)
    {
        $this->app = $connection->app;
        $this->queryIdentifier = uniqid('', TRUE);
        $this->connection = $connection;
        $this->table = $table;
        $this->condition = new Condition($this->app, 'AND');
    }

    public function __destruct()
    {
        $this->condition = null;
    }

    /**
     * 设置更新条件
     *
     * @param $fieldName
     * @param null $value
     * @param null $operator
     *
     * @return $this
     */
    public function where($fieldName, $value = NULL, $operator = NULL)
    {
        $this->condition->where($fieldName, $value, $operator);
        return $this;
    }

    /**
     * 设置字段条件isNull
     *
     * @param $fieldName
     *
     * @return $this
     */
    public function isNull($fieldName)
    {
        $this->condition->where($fieldName, NULL, 'IS NULL');
        return $this;
    }

    /**
     * 设置字段条件isNotNull
     *
     * @param $fieldName
     *
     * @return $this
     */
    public function isNotNull($fieldName)
    {
        $this->condition->where($fieldName, NULL, 'IS NOT NULL');
        return $this;
    }

    /**
     * 设置条件片段
     *
     * @param $snippet
     * @param array $args
     *
     * @return $this
     */
    public function condition($snippet, $args = array())
    {
        $this->condition->condition($snippet, $args);
        return $this;
    }

    /**
     * exists
     *
     * @param Select $select
     *
     * @return $this
     */
    public function exists(Select $select)
    {
        $this->condition->where('', $select, 'EXISTS');
        return $this;
    }

    /**
     * notExists
     *
     * @param Select $select
     *
     * @return $this
     */
    public function notExists(Select $select)
    {
        $this->condition->where('', $select, 'NOT EXISTS');
        return $this;
    }

    /**
     * 执行删除
     *
     * @return Statement
     */
    public function execute()
    {
        $query = $this->compile();
        return $this->connection->query($query['sql'], $query['arguments']);
    }

    /**
     * 编译查询条件
     *
     * @return array
     */
    protected function compile()
    {
        $query = array();
        $query['sql'] = 'DELETE FROM {:' . $this->table . ':} ';
        $query['arguments'] = array();

        if (count($this->condition)) {
            $query['sql'] .= "\nWHERE " . $this->condition->getString($this->queryIdentifier);
            $query['arguments'] = $this->condition->getArguments($this->queryIdentifier);
        }

        return $query;
    }
}
