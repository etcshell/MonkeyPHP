<?php

/**
 * @file
 *
 * Condition
 */

namespace Monkey\Database\Query;

use \Countable;

/**
 * 条件类
 */
class Condition implements Countable
{
    protected static $placeholderTotal;
    protected
        $conditions = array(), //所有条件
        $arguments = array(), //绑定值
        $changed = TRUE,//变更标签
        $queryIdentifier,//查询标识
        $string=''
    ;

    /**
     * 构造函数
     * @param string $queryIdentifier 查询标识
     * @param string $conjunction 联合方式 AND | OR | XOR
     */
    public function __construct($queryIdentifier,$conjunction)
    {
        $this->conditions['#conjunction'] = $conjunction;
        $this->queryIdentifier=$queryIdentifier;
    }

    /**
     * 实现深拷贝
     */
    public function __clone()
    {
        $this->changed = TRUE;
        foreach ($this->conditions as $key => $condition){
            if ($key === '#conjunction') continue;
            is_object($condition['field']) and $this->conditions[$key]['field'] = clone($condition['field']);
            is_object($condition['value']) and $this->conditions[$key]['value'] = clone($condition['value']);
        }
    }

    /**
     * 设置查询标识
     * 用于克隆后实现深拷贝，否则在克隆后编译时就必须显示指定查询标识
     * @param string $queryIdentifier
     */
    public function setQueryIdentifierAfterClone($queryIdentifier)
    {
        $this->queryIdentifier=$queryIdentifier;
    }

    /**
     * 实现统计接口
     */
    public function count()
    {
        return count($this->conditions) - 1;
    }

    /**
     * 为字段设置一个条件
     * @param string $fieldName 字段名
     * @param null|mixed $fieldValue 字段值
     * @param null|string $operator
     * @return $this
     */
    public function whereField($fieldName, $fieldValue = NULL, $operator = NULL)
    {
        if (!isset($operator)) {
            if (is_array($fieldValue)) {
                $operator = 'IN';
            }
            else {
                $operator = '=';
            }
        }
        $this->conditions[] = array(
            'field' => $fieldName,
            'value' => $fieldValue,
            'operator' => $operator,
        );
        $this->changed = TRUE;
        return $this;
    }

    /**
     * 设置一个条件片段
     * @param string $snippet 条件片段，其中可以使用占位符':placeholderName'
     * @param array $args 参数值 array(':placeholderName'=>value, ...)
     * @return $this
     */
    public function whereCondition($snippet, $args = array())
    {
        $this->conditions[] = array
        (
            'field' => $snippet,
            'value' => $args,
            'operator' => NULL,
        );
        $this->changed = TRUE;
        return $this;
    }

    /**
     * 引用返回条件清单
     */
    public function &getConditions()
    {
        return $this->conditions;
    }
    
    /**
     * 获取所有的值
     * @param string|null $queryIdentifier 查询标识
     * @return array
     */
    public function getArguments($queryIdentifier=null)
    {
        $this->checkChang($queryIdentifier);
        return $this->arguments;
    }

    /**
     * 获取编译后的条件片段
     * @param string|null $queryIdentifier 查询标识
     * @return string
     */
    public function getString($queryIdentifier=null)
    {
        $this->checkChang($queryIdentifier);
        return $this->string;
    }

    protected function checkChang($queryIdentifier=null)
    {
        $this->changed and $this->compile($queryIdentifier);
    }
    /**
     * 组装条件
     * 注意：省略参数的必要条件是类属性“查询标识”正确；
     * 不能省略参数的情况比如克隆后，如果没有setQueryIdentifierAfterClone，此时就必须显示指定查询标识。
     * @param string|null $queryIdentifier 查询标识
     */
    protected function compile($queryIdentifier=null)
    {
        $queryIdentifier and $this->queryIdentifier=$queryIdentifier;
        $qi=$this->queryIdentifier;
        $condition_fragments = array();
        $arguments = array();
        $conditions = $this->conditions;
        $conjunction = $conditions['#conjunction'];
        unset($conditions['#conjunction']);

        foreach ($conditions as $condition){
            if (empty($condition['operator'])){
                $condition_fragments[] = ' (' . $condition['field'] . ') ';
                $arguments += $condition['value'];
            }
            else{
                if ($condition['field'] instanceof Condition){
                    $condition_fragments[] = '(' . $condition['field']->getString($qi) . ')';
                    $arguments += $condition['field']->getArguments($qi);
                }
                else{
                    $operator = $this->mapConditionOperator($condition['operator']);
                    $placeholders = array();

                    if ($condition['value'] instanceof Select){
                        $placeholders[] = $condition['value']->getString($qi);
                        $arguments += $condition['value']->getArguments($qi);
                        $operator['use_value'] = FALSE;
                    }

                    if (!$operator['delimiter']){//单值运算
                        $condition['value'] = array($condition['value']);
                    }

                    if ($operator['use_value']) {
                        foreach ($condition['value'] as $value){
                            $placeholder = ':mk_sql_placeholder_' . self::getNextPlaceholder($qi);
                            $arguments[$placeholder] = $value;
                            $placeholders[] = $placeholder;
                        }
                    }

                    $condition_fragments[] = ' (' .$condition['field'] . ' ' . $operator['operator'] . ' ' .
                        $operator['prefix'] . implode($operator['delimiter'], $placeholders) . $operator['postfix'] . ') ';
                }
            }
        }
        $this->changed = FALSE;
        $this->string = implode($conjunction, $condition_fragments);
        $this->arguments = $arguments;
    }

    /**
     * 解析条件操作符
     */
    protected function mapConditionOperator($operator)
    {
        static $specials = array
            (
                'BETWEEN'       => array('delimiter' => ' AND '),
                'IN'            => array('delimiter' => ', ', 'prefix' => ' (', 'postfix' => ')'),
                'NOT IN'        => array('delimiter' => ', ', 'prefix' => ' (', 'postfix' => ')'),
                'EXISTS'        => array('prefix' => ' (', 'postfix' => ')'),
                'NOT EXISTS'    => array('prefix' => ' (', 'postfix' => ')'),
                'IS NULL'       => array('use_value' => FALSE),
                'IS NOT NULL'   => array('use_value' => FALSE),
                'LIKE'          => array('postfix' => " ESCAPE '\\\\'"),
                'NOT LIKE'      => array('postfix' => " ESCAPE '\\\\'"),
                '='             => array(),
                '<'             => array(),
                '>'             => array(),
                '>='            => array(),
                '<='            => array(),
            ),
            $return_default = array
            (
                'prefix'    => '',
                'postfix'   => '',
                'delimiter' => '',
                'use_value' => TRUE,
            );

        if (isset($specials[$operator])){
            $return = $specials[$operator];
        }
        else{
            $operator = strtoupper($operator);
            $return = isset($specials[$operator]) ? $specials[$operator] : array();
        }

        return $return + $return_default + array('operator' => $operator);
    }

    /**
     * 获取下一个占位符计数
     */
    protected static function getNextPlaceholder($identifier)
    {
        if(!self::$placeholderTotal[$identifier]){
            self::$placeholderTotal[$identifier]=0;
        }
        return self::$placeholderTotal[$identifier]++;
    }
}
