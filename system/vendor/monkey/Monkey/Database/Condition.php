<?php
namespace Monkey\Database;

use \Countable;

/**
 * 条件类
 */
class Condition implements Countable
{
    /**
     * @var \Monkey\App\App $app
     */
    public $app;
    protected static
        $placeholderTotal,
        $operatorMap
    ;
    protected
        $conditions = array(), //所有条件
        $arguments = array(), //绑定值
        $changed = TRUE,//变更标签
        $string=''
    ;

    /**
     * 构造函数
     * @param \Monkey\App\App $app
     * @param string $conjunction 联合方式 AND | OR | XOR
     */
    public function __construct($app,$conjunction='AND')
    {
        $this->app=$app;
        $format=array('AND'=>1, 'OR'=>2, 'XOR'=>3);
        $conjunction=strtoupper($conjunction);
        if(!isset($format[$conjunction])) $app->exception('数据库条件不支持:'.$conjunction, 1024, __FILE__, __LINE__);
        $this->conditions['#conjunction'] = $conjunction;
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
    public function where($fieldName, $fieldValue = NULL, $operator = NULL)
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
    public function condition($snippet, $args = array())
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
     * @param string $queryIdentifier 查询标识
     * @return array
     */
    public function getArguments($queryIdentifier)
    {
        $this->checkChang($queryIdentifier);
        return $this->arguments;
    }

    /**
     * 获取编译后的条件片段
     * @param string $queryIdentifier 查询标识
     * @return string
     */
    public function getString($queryIdentifier)
    {
        $this->checkChang($queryIdentifier);
        return $this->string;
    }

    protected function checkChang($queryIdentifier)
    {
        $this->changed and $this->compile($queryIdentifier);
    }
    /**
     * 组装条件
     * 注意：省略参数的必要条件是类属性“查询标识”正确；
     * 不能省略参数的情况比如克隆后，如果没有setQueryIdentifierAfterClone，此时就必须显示指定查询标识。
     * @param string $queryIdentifier 查询标识
     */
    protected function compile($queryIdentifier)
    {
        $qi=$queryIdentifier;
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
                            $placeholder = ':sql_placeholder_' . self::getNextPlaceholder($qi);
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
        static
            $defaultReturn = array(
                'prefix'    => '',
                'postfix'   => '',
                'delimiter' => '',
                'use_value' => TRUE,
            );

        if (isset(static::$operatorMap[$operator])){
            $return = static::$operatorMap[$operator];
        }
        else{
            $operator = strtoupper($operator);
            $return = isset(static::$operatorMap[$operator]) ? static::$operatorMap[$operator] : array();
        }
        return $return + $defaultReturn + array('operator' => $operator);
    }

    /**
     * 获取下一个占位符计数
     */
    protected static function getNextPlaceholder($identifier)
    {
        if(!static::$placeholderTotal[$identifier]){
            static::$placeholderTotal[$identifier]=0;
        }
        return static::$placeholderTotal[$identifier]++;
    }
}
