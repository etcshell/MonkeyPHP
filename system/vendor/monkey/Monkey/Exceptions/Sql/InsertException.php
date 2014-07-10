<?php
/**
 * Project MonkeyPHP
 *
 * PHP Version 5.3.9
 *
 * @package   Monkey\Exceptions\Sql
 * @author    黄易 <582836313@qq.com>
 * @version   GIT:<git_id>
 */
namespace Monkey\Exceptions\Sql;


use Monkey\Exceptions\Exception;

/**
 * Class InsertException
 *
 * sql空语句异常
 *
 * @package Monkey\Exceptions\Sql
 */
class InsertException extends Exception
{
    protected $code = 3010;
}