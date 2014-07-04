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
class Insert
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
    protected $fields = array();

    /**
     * 行值
     *
     * @var array
     */
    protected $rowValues = array();

    /**
     * 是否使用表结构
     *
     * @var bool
     */
    protected $usedTableMate = false;

    /**
     * 主键名
     *
     * @var string
     */
    protected $priName = '';

    /**
     * 是否自增字段
     *
     * @var bool
     */
    protected $isAutoPri = false;

    /**
     * 默认字段值
     *
     * @var array
     */
    protected $fieldsValuesDefault = array();

    /**
     * 默认字段类型
     *
     * @var array
     */
    protected $fieldsValuesType = array();

    /**
     * 是否设置了默认字段值
     *
     * @var bool
     */
    protected $isSetDefault = false;

    /**
     * 构造方法
     *
     * @param Connection $connection
     * @param $table
     */
    public function __construct(Connection $connection, $table)
    {
        $this->app = $connection->app;
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * 使用表结构自动填充插入字段
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function useTableMate()
    {
        if ($this->usedTableMate) {
            return $this;
        }

        $table = $this->table;
        $connection = $this->connection;
        $cache_key = md5(serialize($connection->getConfig())) . '_mate_' . $table;
        $mate = null;
        $cacher = $this->app->cache();

        if (!$cacher->fetch($cache_key, $mate)) {
            $mate = $connection->getTableMate($table);
            $cacher->store($cache_key, $mate);
        }

        if (empty($mate)) {
            throw new \Exception('表【' . $table . '】的字段信息读取失败!');
        }

        $this->fieldsValuesDefault = $mate['fields_default'];
        $this->fieldsValuesType = $mate['fields_type'];
        $this->priName = $mate['pri_name'];
        $this->isAutoPri = $mate['pri_is_auto'];

        return $this;
    }

    /**
     * 从表结构中获取字段的默认值
     *
     * @return array
     */
    public function getFieldsDefaultFromMate()
    {
        return $this->fieldsValuesDefault;
    }

    /**
     * 添加要插入的字段
     *
     * @param array $fields 数组键名，则值为字段名； 字符键名，则键名为字段名，值为默认插入值
     *
     * @return $this
     */
    public function addFields(array $fields)
    {
        $this->isSetDefault += $this->checkFields($fields);
        $this->fields += $fields;
        return $this;
    }

    /**
     * 设置要插入的字段
     *
     * @param array $fields 数组键名，则值为字段名； 字符键名，则键名为字段名，值为默认插入值
     *
     * @return $this
     */
    public function setFields(array $fields)
    {
        $this->isSetDefault = $this->checkFields($fields);
        $this->fields = $fields;
        return $this;
    }

    /**
     * 添加插入数据行
     * 注意，需要在设定插入字段之后使用
     *
     * @param array $values
     *
     * @return $this
     */
    public function addRowValues(array $values)
    {
        if (empty($this->fields) and $this->usedTableMate) {
            $this->fields = $this->fieldsValuesDefault;
        }

        $rowValue = array();
        $isSetDefault = $this->isSetDefault;
        $mateDefault = & $this->fieldsValuesDefault;
        $i = 0;

        if (is_numeric(key($values))) {
            $values = array_merge($values); //重建索引
            $total = count($values) + 1;

            foreach ($this->fields as $field => $value) {
                $rowValue[$i++] = $i < $total ? $values[$i - 1] : $isSetDefault ? $value : $mateDefault[$field];
            }

        } else {
            foreach ($this->fields as $field => $value) {
                $rowValue[$i++] = array_key_exists($field, $values) ? $values[$field] : $isSetDefault ? $value : $mateDefault[$field];
            }
        }

        $rowValue and $this->rowValues[] = $rowValue;

        return $this;
    }

    /**
     * 设置要插入的字段（使用子查询）
     *
     * @param Select $query 子查询。
     *
     * @return $this
     */
    public function setFieldsByQuery(Select $query)
    {
        $fields = array_merge(array_keys($query->getFields()), array_keys($query->getFieldOfExpressions()));
        return $this->setFields($fields);
    }

    /**
     * 使用已经准备好的查询结果
     *
     * @param Select $query 子查询。 为空时相当于放弃使用子查询的结果作为填充数据
     *
     * @return $this
     */
    public function fromSelect(Select $query = null)
    {
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
    public function execute()
    {
        $query = $this->compile();

        if (!isset($query['sql'])) {
            throw new \Exception('数据库插入语句为空，插入失败');
        }

        $return = $this->connection->query($query['sql'], $query['arguments']);
        $this->rowValues = array();

        return $return;
    }

    /**
     * 获取编译好的插入预处理语句
     *
     * @return string
     */
    protected function compile()
    {
        $query = array();
        $query['sql'] = '';
        $query['arguments'] = array();
        $fields = $this->fields;

        if ($this->fromQuery instanceof Select) {
            $query['sql'] = 'INSERT INTO {:' . $this->table . ':} (' . implode(', ', $fields) . ') ' . $this->fromQuery->getString();
            $query['arguments'] = $this->fromQuery->getArguments();
            return $query;
        }

        $rowCount = count($this->rowValues);

        if (!$rowCount) {
            return $query;
        }

        $query['sql'] = 'INSERT INTO {:' . $this->table . ':} (' . implode(', ', $fields) . ') VALUES ';
        $placeholder_total = $placeholder = 0;
        $rowsPlaceholder = array();

        foreach ($this->rowValues as $rowValue) {
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

    /**
     * 效验字段
     *
     * @param $fields
     *
     * @return bool
     */
    protected function checkFields(&$fields)
    {
        if ($this->rowValues) {
            $this->rowValues = null;
        }
        $isSetDefault = true;
        $_fields = array();
        if (is_numeric(key($fields))) {
            foreach ($fields as $field) {
                $_fields[$field] = null;
            }
            $isSetDefault = false;

        } else {
            $_fields = $fields;
        }

        if ($this->usedTableMate) {
            $fields = array();

            foreach ($_fields as $field => $value) {
                if (array_key_exists($field, $this->fieldsValuesDefault)) {
                    $fields[$field] = $isSetDefault ? $value : $this->fieldsValuesDefault[$field];
                }
            }

            if ($this->isAutoPri and array_key_exists($this->priName, $fields)) {
                unset($fields[$this->priName]);
            }
            $isSetDefault = true;

        } else {
            $fields = $_fields;
        }
        return $isSetDefault;
    }

}
