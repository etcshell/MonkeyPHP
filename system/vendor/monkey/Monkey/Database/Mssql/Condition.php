<?php
namespace Monkey\Database\Mssql;

use Monkey\Database;

/**
 * 条件类
 */
class Condition extends Database\Condition
{
    protected static $operatorMap=array
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
    );
}
