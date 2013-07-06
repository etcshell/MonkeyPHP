<?php

/**
 * @file
 *
 * Condition
 */

namespace Monkey\Database\Query\Mssql;

use Monkey\Database\Query;

/**
 * 条件类
 */
class Condition extends Query\Condition
{

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
}
