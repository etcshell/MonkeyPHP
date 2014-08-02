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
 * Class Select
 *
 * 数据选择查询类
 *
 * @package Monkey\Database
 */
class Select {
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
     * 查询识别码
     *
     * @var string
     */
    protected $queryIdentifier;

    /**
     * 查询主表别名
     *
     * @var string
     */
    protected $mainTableAlias;

    /**
     * 选择表名
     *
     * @var array
     */
    protected $tables = array();

    /**
     * 选择字段
     *
     * @var array
     */
    protected $fields = array();

    /**
     * 条件表达式
     *
     * @var array
     */
    protected $expressions = array();

    /**
     * 排序条件
     *
     * @var array
     */
    protected $order = array();

    /**
     * 分组条件
     *
     * @var array
     */
    protected $group = array();

    /**
     * 查询条件
     *
     * @var Condition
     */
    protected $where;

    /**
     * having条件
     *
     * @var Condition
     */
    protected $having;

    /**
     * 是否去除重复条件
     *
     * @var array
     */
    protected $distinct = FALSE;

    /**
     * 查询范围
     *
     * @var array
     */
    protected $range;

    /**
     * 是否用于更新语句
     *
     * @var bool
     */
    protected $forUpdate = FALSE;

    /**
     * 构造方法
     *
     * @param Connection $connection
     * @param string $table
     * @param null $alias
     * @param array $options
     */
    public function __construct(Connection $connection, $table, $alias = NULL, $options = array()) {
        $this->app = $connection->app;
        $this->queryIdentifier = uniqid('', TRUE);
        if (empty($alias)) {
            $alias = $table instanceof Select ? 'subquery' : '{:' . $table . ':}';
        }
        $this->mainTableAlias = $alias;
        $conjunction = isset($options['conjunction']) ? $options['conjunction'] : 'AND';
        $this->where = new Condition($this->app, $conjunction);
        $this->having = new Condition($this->app, $conjunction);
        $this->addJoin(NULL, $table, $alias);
        $this->connection = $connection;
    }

    /**
     * 去掉重复结果
     *
     * @param bool $distinct TRUE，去掉；FALSE，不去。
     *
     * @return $this
     */
    public function distinct($distinct = TRUE) {
        $this->distinct = (bool)$distinct;
        return $this;
    }

    /**
     * 添加 主表 出现在结果集中的 字段
     * 不使用相当于添加了主表的所有字段
     *
     * @param string|array $fields 字符串参数可以多个
     *
     * @return $this
     *
     * 例子：
     * 添加 主表中的所有字段：
     * field()
     *
     * 添加 主表中的 'aF1' 字段：
     * field('f1')
     *
     * 添加 主表中的 3 个字段：
     * field('f1', 'f2', 'f3')
     *
     * 添加 主表中的 'f1' 字段，并且设置为别名 'aliasF1'：
     * field(array('f1'=>'aliasF1'))
     *
     * 添加 主表中的 3 个字段，并且将字段 'f2' 的别名设置为 'aliasF2'
     * field(array('f1', 'aF2'=>'aliasF2', 'f3')) //即，第二个参数中，可以用字符串键名来设置别名
     *
     */
    public function fields($fields = array()) {
        if (func_num_args() > 1) {
            $fields = func_get_args();
        }

        return $this->_addFields($this->mainTableAlias, $fields);
    }

    /**
     * 添加一个字段到结果集中
     *
     * @param string $tableAlias 表名或表别名
     * @param string $field 字段名
     * @param null $alias 设置字段的别名
     *
     * @return $this
     */
    public function addField($tableAlias, $field, $alias = NULL) {
        //修复表别名
        $tableAlias = $this->checkTableAlias($tableAlias);
        $this->_addField($tableAlias, $field, $alias);

        return $this;
    }

    /**
     * 添加结果集中的字段
     *
     * @param string $tableAlias 表名或表别名
     * @param string|array $fields 默认为该表的所有字段 array('f1','f2','f3')  或 'f3'
     *
     * @return $this
     *
     * 例子：
     * 添加 a 表中的所有字段：
     * field('a')
     *
     * 添加 a 表中的 'aF1' 字段：
     * field('a', 'aF1' )
     *
     * 添加 a 表中的 'aF1' 字段，并且设置为别名 'aliasF1'：
     * field('a', array('f1'=>'aliasF1'))
     *
     * 添加 a 表中的 3 个字段：
     * field('a', array('aF1', 'aF2', 'aF3'))
     *
     * 添加 a 表中的 3 个字段，并且将字段 'aF2' 的别名设置为 'aliasF2'
     * field('a', array('aF1', 'aF2'=>'aliasF2', 'aF3')) //即，第二个参数中，可以用字符串键名来设置别名
     */
    public function addFields($tableAlias, $fields = array()) {
        //修复表别名
        $tableAlias = $this->checkTableAlias($tableAlias);
        $this->_addFields($tableAlias, $fields);

        return $this;
    }

    /**
     * 验证表别名
     *
     * @param string $test
     *
     * @return string
     */
    protected function checkTableAlias($test) {
        //检查存在的已定义的别名
        foreach ($this->tables as $alias => $item) {
            if ($test == $alias or $test == $item['table'] or $test == $item['alias']) {
                return $item['alias'];
            }
        }

        //没有定义过别名，$test就是表名或子查询了
        return strpos($test, ':') ? $test : '{:' . $test . ':}';
    }

    protected function _addField($tableAlias, $field, $alias = NULL) {
        empty($alias) and $alias = $field;
        !empty($this->fields[$alias]) and $alias = $tableAlias . '_' . $field;
        $aliasCandidate = $alias;
        $count = 2;

        while (!empty($this->fields[$aliasCandidate])) {
            $aliasCandidate = $alias . '_' . $count++;
        }

        $alias = $aliasCandidate;

        $this->fields[$alias] = array('field' => $field, 'table' => $tableAlias, 'alias' => $alias,);
    }

    protected function _addFields($tableAlias, $fields = array()) {
        if (empty($fields)) {
            $this->tables[$tableAlias]['all_fields'] = TRUE;
            return $this;
        }
        is_string($fields) and $fields = array($fields);

        foreach ($fields as $key => $field) {
            if (is_numeric($key)) {
                $this->_addField($tableAlias, $field);
            }
            else {
                $this->_addField($tableAlias, $key, $field);
            }
        }

        return $this;
    }

    /**
     * 添加一个表达式到结果集中
     *
     * @param string $alias 设置表达式的别名
     * @param string $expression 表达式
     * @param array $arguments 用到的参数
     *
     * @return $this
     *
     * 如：$select->addFieldByExpression( 'numb', 'count(*)' );
     * 如果表达式中出现了表名，请这样表示：'{:tablename:}'
     */
    public function addFieldByExpression($alias, $expression, $arguments = array()) {
        if (empty($alias)) {
            $alias = 'expression';
        }

        $aliasCandidate = $alias;
        $count = 2;

        while (!empty($this->expressions[$aliasCandidate])) {
            $aliasCandidate = $alias . '_' . $count++;
        }

        $alias = $aliasCandidate;
        $this->expressions[$alias] = array('expression' => $expression, 'alias' => $alias, 'arguments' => $arguments,);

        return $this;
    }

    /**
     * 添加一个指定 Type 的 JOIN 连接条件
     *
     * @param $type
     * @param $table
     * @param null $alias
     * @param null $condition
     * @param array $arguments
     *
     * @return $this
     */
    public function addJoin($type, $table, $alias = NULL, $condition = NULL, $arguments = array()) {
        if (empty($alias)) {
            $alias = $table instanceof Select ? 'subquery' : '{:' . $table . ':}';
        }

        $aliasCandidate = $alias;
        $count = 2;

        while (!empty($this->tables[$aliasCandidate])) {
            $aliasCandidate = $alias . '_' . $count++;
        }

        $alias = $aliasCandidate;

        if (is_string($condition)) {
            $condition = str_replace('%alias', $alias, $condition);
        }

        $this->tables[$alias] = array('join type' => $type, 'table' => $table, 'alias' => $alias, 'condition' => $condition, 'arguments' => $arguments,);

        return $this;
    }

    /**
     * 添加一个 INNER JOIN 连接条件
     *
     * @param $table
     * @param null $alias
     * @param null $condition
     * @param array $arguments
     *
     * @return $this
     */
    public function join($table, $alias = NULL, $condition = NULL, $arguments = array()) {
        return $this->addJoin('INNER', $table, $alias, $condition, $arguments);
    }

    /**
     * 添加一个 INNER JOIN 连接条件
     *
     * @param $table
     * @param null $alias
     * @param null $condition
     * @param array $arguments
     *
     * @return $this
     */
    public function innerJoin($table, $alias = NULL, $condition = NULL, $arguments = array()) {
        return $this->addJoin('INNER', $table, $alias, $condition, $arguments);
    }

    /**
     * 添加一个 LEFT OUTER JOIN 连接条件
     *
     * @param $table
     * @param null $alias
     * @param null $condition
     * @param array $arguments
     *
     * @return $this
     */
    public function leftJoin($table, $alias = NULL, $condition = NULL, $arguments = array()) {
        return $this->addJoin('LEFT OUTER', $table, $alias, $condition, $arguments);
    }

    /**
     * 添加一个 RIGHT OUTER JOIN 连接条件
     *
     * @param $table
     * @param null $alias
     * @param null $condition
     * @param array $arguments
     *
     * @return $this
     */
    public function rightJoin($table, $alias = NULL, $condition = NULL, $arguments = array()) {
        return $this->addJoin('RIGHT OUTER', $table, $alias, $condition, $arguments);
    }

    /**
     * 为字段设置一个where条件
     *
     * @param string $fieldName 字段名
     * @param null|mixed $fieldValue 字段值
     * @param null|string $operator
     *
     * @return $this
     */
    public function where($fieldName, $fieldValue = NULL, $operator = NULL) {
        $this->where->where($fieldName, $fieldValue, $operator);
        return $this;
    }

    /**
     * 为字段设置一个判空的条件
     *
     * @param string $fieldName 字段名
     *
     * @return $this
     */
    public function isNull($fieldName) {
        $this->where->where($fieldName, NULL, 'IS NULL');
        return $this;
    }

    /**
     * 为字段设置一个判非空的条件
     *
     * @param string $fieldName 字段名
     *
     * @return $this
     */
    public function isNotNull($fieldName) {
        $this->where->where($fieldName, NULL, 'IS NOT NULL');
        return $this;
    }

    /**
     * 添加一个where片段
     *
     * @param $snippet
     * @param array $args
     *
     * @return $this
     */
    public function condition($snippet, $args = array()) {
        $this->where->condition($snippet, $args);
        return $this;
    }

    /**
     * exists
     *
     * @param Select $select
     *
     * @return $this
     */
    public function exists(Select $select) {
        $this->where->where('', $select, 'EXISTS');
        return $this;
    }

    /**
     * notExists
     *
     * @param Select $select
     *
     * @return $this
     */
    public function notExists(Select $select) {
        $this->where->where('', $select, 'NOT EXISTS');
        return $this;
    }

    /**
     * 添加排序条件
     *
     * @param $field
     * @param string $direction
     *
     * @return $this
     */
    public function orderBy($field, $direction = 'ASC') {
        $this->order[$field] = strtoupper($direction) == 'ASC' ? 'ASC' : 'DESC';
        return $this;
    }

    /**
     * 添加分组条件
     *
     * @param $field
     *
     * @return $this
     */
    public function groupBy($field) {
        $this->group[$field] = $field;
        return $this;
    }

    /**
     * 设置字段筛选条件
     *
     * @param $fieldName
     * @param null $fieldValue
     * @param null $operator
     *
     * @return $this
     */
    public function having($fieldName, $fieldValue = NULL, $operator = NULL) {
        $this->having->where($fieldName, $fieldValue, $operator);
        return $this;
    }

    /**
     * 设置筛选条件IsNull
     *
     * @param $fieldName
     *
     * @return $this
     */
    public function havingIsNull($fieldName) {
        $this->having->where($fieldName, NULL, 'IS NULL');
        return $this;
    }

    /**
     * 设置筛选条件IsNotNull
     *
     * @param $fieldName
     *
     * @return $this
     */
    public function havingIsNotNull($fieldName) {
        $this->having->where($fieldName, NULL, 'IS NOT NULL');
        return $this;
    }

    /**
     * 设置复杂筛选片段
     *
     * @param $snippet
     * @param array $args
     *
     * @return $this
     */
    public function havingCondition($snippet, $args = array()) {
        $this->having->condition($snippet, $args);
        return $this;
    }

    /**
     * 设置查询范围
     *
     * @param int $length
     * @param int $start
     *
     * @return $this
     */
    public function range($length = NULL, $start = NULL) {
        $this->range = func_num_args() ? array('start' => (int)$start, 'length' => (int)$length) : array();
        return $this;
    }

    /**
     * 设置查询范围
     *
     * @param int $start
     * @param int $length
     *
     * @return $this
     */
    public function limit($start = 0, $length = 1) {
        $this->range = array('start' => (int)$start, 'length' => (int)$length);
        return $this;
    }

    /**
     * 锁定当前select结果对应的源行
     *
     * @param bool|string $set true表示直接锁定， "of {:tableName:}.fieldName"表示仅锁定指定表中的源行
     *
     * @return $this
     */
    public function forUpdate($set = TRUE) {
        isset($set) and $this->forUpdate = $set;
        return $this;
    }

    /**
     * 获取结果集字段列表
     *
     * @return array
     */
    public function &getFields() {
        return $this->fields;
    }

    /**
     * 获取结果集表达式列的列表
     *
     * @return array
     */
    public function &getFieldOfExpressions() {
        return $this->expressions;
    }

    /**
     * 获取查询目标表
     *
     * @return array
     */
    public function &getTables() {
        return $this->tables;
    }

    /**
     * 获取排序列表
     *
     * @return array
     */
    public function &getOrderBy() {
        return $this->order;
    }

    /**
     * 获取分组列表
     *
     * @return array
     */
    public function &getGroupBy() {
        return $this->group;
    }

    /**
     * 获取聚合条件列表
     *
     * @return array
     */
    public function &getHavingConditions() {
        return $this->having->getConditions();
    }

    /**
     * 获取查询参数值
     *
     * @param string $queryIdentifier
     *
     * @return array|null
     */
    public function getArguments($queryIdentifier = NULL) {
        $queryIdentifier and $this->queryIdentifier = $queryIdentifier;
        $qi = $this->queryIdentifier;
        $args = $this->where->getArguments($qi) + $this->having->getArguments($qi);

        foreach ($this->tables as $table) {
            $table['arguments'] and $args += $table['arguments'];
            $table['table'] instanceof Select and $args += $table['table']->getArguments($qi);
        }

        foreach ($this->expressions as $expression) {
            $expression['arguments'] and $args += $expression['arguments'];
        }

        return $args;
    }

    /**
     * 获取组装好的查询语句
     *
     * @param null $queryIdentifier
     *
     * @return string
     */
    public function getString($queryIdentifier = NULL) {
        $qi = $queryIdentifier ? $this->queryIdentifier = $queryIdentifier : $this->queryIdentifier;
        //!$this->compiled() and $this->compile($this);
        // SELECT
        $query = 'SELECT ';
        $this->distinct and $query .= 'DISTINCT ';
        // FIELDS and EXPRESSIONS
        $fields = array();

        foreach ($this->tables as $alias => $table) {
            !empty($table['all_fields']) and $fields[] = $alias . '.*';
        }

        foreach ($this->fields as $field) {
            $fields[] = (isset($field['table']) ? $field['table'] . '.' : '') . $field['field'] . ' AS ' . $field['alias'];
        }

        foreach ($this->expressions as $expression) {
            $fields[] = $expression['expression'] . ' AS ' . $expression['alias'];
        }

        $query .= $fields ? implode(', ', $fields) : '*';
        // FROM
        $query .= "\nFROM ";

        foreach ($this->tables as $table) {
            $query .= "\n";
            !empty($table['join type']) and $query .= $table['join type'] . ' JOIN ';

            if (isset($table['table']) and $table['table'] instanceof Select) {
                $tableString = '(' . $table['table']->getString($qi) . ')';

            }
            else {
                $tableString = '{:' . $table['table'] . ':}';
            }

            $query .= $tableString . ' as ' . $table['alias'];
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
        !empty($this->range) and $query .= "\nLIMIT " . (int)$this->range['length'] . ' OFFSET ' . (int)$this->range['start'];
        $this->forUpdate and $query .= ' FOR UPDATE' . ($this->forUpdate === true ? '' : $this->forUpdate);

        return $query;
    }

    /**
     * 获取一个统计查询
     *
     * @return Select
     */
    public function getCountQuery() {
        $count = $this->prepareCountQuery();
        $query = $this->connection->select($count);
        $query->addFieldByExpression('mk_count_value', 'COUNT(*)');
        return $query;
    }

    /**
     * 执行查询
     *
     * @return Statement
     */
    public function execute() {
        return $this->connection->query($this->getString(), $this->getArguments());
    }

    /**
     * 准备一个统计查询
     *
     * @return Select
     */
    protected function prepareCountQuery() {
        $count = clone($this);
        $groupBy = $count->getGroupBy();
        $having = $count->getHavingConditions();

        if (!$count->distinct && !isset($having[0])) {
            //去掉所有select字段
            $fields = & $count->getFields();

            foreach (array_keys($fields) as $field) {
                if (empty($groupBy[$field])) {
                    unset($fields[$field]);
                }
            }

            //去掉所有expressions字段
            $expressions = & $count->getFieldOfExpressions();
            foreach (array_keys($expressions) as $field) {
                if (empty($groupBy[$field])) {
                    unset($expressions[$field]);
                }
            }

            //去掉*字段
            foreach ($count->tables as &$table) {
                unset($table['all_fields']);
            }
        }

        $count->addFieldByExpression('mk_expression_holder', '1');
        $orders = & $count->getOrderBy();
        $orders = array();

        if ($count->distinct && !empty($groupBy)) {
            $count->distinct = FALSE;
        }

        return $count;
    }
}