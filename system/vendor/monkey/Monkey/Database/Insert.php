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
 * Class Insert
 *
 * 数据插入
 *
 * @package Monkey\Database
 */
class Insert {

    /**
     * 应用对象
     *
     * @var Monkey\App
     */
    public $app;

    /**
     * 连接对象
     *
     * @var Connection
     */
    public $connection;

    /**
     * 表名
     *
     * @var string
     */
    protected $table;

    /**
     * 子查询
     *
     * @var Select
     */
    protected $fromQuery;

    /**
     * 插入字段
     *
     * @var array
     */
    protected $insertFields = array();

    /**
     * 行值
     *
     * @var array
     */
    protected $insertRow = array();

    /**
     * 默认字段值
     *
     * @var array
     */
    protected $fieldsDefaults = array();

    /**
     * 构造方法
     *
     * @param Connection $connection
     * @param $table
     */
    public function __construct(Connection $connection, $table) {
        $this->app = $connection->app;
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * 设置要插入的字段
     *
     * @param array $fields 数组键名，则值为字段名； 字符键名，则键名为字段名，值为默认插入值
     *
     * @return $this
     */
    public function setFields(array $fields) {
        if (is_numeric(key($fields))) {
            $this->insertFields = $fields;
        }
        else {
            $this->insertFields = array_keys($fields);
            $this->insertRow = array();
            $this->insertRow[] = array_values($fields);
        }

        return $this;
    }

    /**
     * 设置要插入的字段（使用子查询）
     *
     * @param Select $query 子查询。
     *
     * @return $this
     */
    public function setFieldsByQuery(Select $query) {
        $fields = array_merge(array_keys($query->getFields()), array_keys($query->getFieldOfExpressions()));
        return $this->setFields($fields);
    }

    /**
     * 设置默认字段值
     *
     * @param array $fields 字段名 => 字段值 ，可以仅仅指定部分字段的默认值
     *
     * @return $this
     */
    public function useDefaults(array $fields) {
        $this->fieldsDefaults = $fields;
        return $this;
    }

    /**
     * 添加插入数据行
     * 注意，需要在设定插入字段之后使用
     *
     * @param array $values
     *
     * @throws Monkey\Exceptions\Sql\InsertException
     *
     * @return $this
     */
    public function addRow(array $values) {
        if (is_numeric(key($values))) {
            $this->insertRow[] = $values;
        }
        elseif (empty($this->insertFields)) {
            $this->insertFields = array_keys($values);
            $this->insertRow[] = $values;
        }
        else {
            $i = 0;
            $rowValue = array();
            foreach ($this->insertFields as $field) {
                if (isset($values[$field])) {
                    $rowValue[$i++] = $values[$field];
                }
                elseif (isset($this->fieldsDefaults[$field])) {
                    $rowValue[$i++] = $this->fieldsDefaults[$field];
                }
                else {
                    throw new Monkey\Exceptions\Sql\InsertException('插入行值缺少与字段名对应的值。');
                }
            }
            $this->insertRow[] = $rowValue;
        }

        return $this;
    }

    /**
     * 使用已经准备好的查询结果
     *
     * @param Select $query 子查询。 为空时相当于放弃使用子查询的结果作为填充数据
     *
     * @return $this
     */
    public function fromSelect(Select $query = null) {
        $this->fromQuery = $query;
        return $this;
    }

    /**
     * 执行插入
     *
     * @return Statement
     *
     * @throws \Exception
     */
    public function execute() {
        $query = $this->compile();
        $return = $this->connection->query($query['sql'], $query['arguments']);
        $this->insertRow = array();

        return $return;
    }

    /**
     * 获取编译好的插入预处理语句
     *
     * @return string
     */
    protected function compile() {
        $query = array();
        $query['sql'] = '';
        $query['arguments'] = array();
        $fields = $this->insertFields;

        if ($this->fromQuery instanceof Select) {
            $query['sql'] = 'INSERT INTO {:' . $this->table . ':} (' . implode(', ', $fields) . ') ' . $this->fromQuery->getString();
            $query['arguments'] = $this->fromQuery->getArguments();
            return $query;
        }

        $rowCount = count($this->insertRow);

        if (!$rowCount) {
            return $query;
        }

        $query['sql'] = 'INSERT INTO {:' . $this->table . ':} (' . implode(', ', $fields) . ') VALUES ';
        $placeholder_total = $placeholder = 0;
        $rowsPlaceholder = array();

        foreach ($this->insertRow as $rowValue) {
            $placeholders = array();
            $rowArgument = array();

            foreach ($rowValue as $value) {
                $placeholder = ':mk_insert_placeholder_' . $placeholder_total++;
                $placeholders[] = $placeholder;
                $rowArgument[$placeholder] = $value;
            }

            $rowsPlaceholder[] = '(' . implode(', ', $placeholders) . ')';
            $query['arguments'] += $rowArgument;
        }

        $query['sql'] .= implode(', ', $rowsPlaceholder);

        return $query;
    }

}
