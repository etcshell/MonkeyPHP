<?php
/**
 * Project MonkeyPHP
 *
 * PHP Version 5.3.9
 *
 * @package   Monkey\Database\Sqlite
 * @author    黄易 <582836313@qq.com>
 * @version   GIT:<git_id>
 */
namespace Monkey\Database\Sqlite;

use Monkey\Database;

/**
 * Class Condition
 *
 * 条件类
 *
 * @package Monkey\Database\Sqlite
 */
class Condition extends Database\Condition {
    /**
     * 条件连接操作选项
     *
     * @var array
     */
    protected static $operatorMap = array('BETWEEN' => array('delimiter' => ' AND '), 'IN' => array('delimiter' => ', ', 'prefix' => ' (', 'postfix' => ')'), 'NOT IN' => array('delimiter' => ', ', 'prefix' => ' (', 'postfix' => ')'), 'EXISTS' => array('prefix' => ' (', 'postfix' => ')'), 'NOT EXISTS' => array('prefix' => ' (', 'postfix' => ')'), 'IS NULL' => array('use_value' => FALSE), 'IS NOT NULL' => array('use_value' => FALSE), 'LIKE' => array('postfix' => " ESCAPE '\\'"), 'NOT LIKE' => array('postfix' => " ESCAPE '\\'"), '=' => array(), '<' => array(), '>' => array(), '>=' => array(), '<=' => array(),);

}
