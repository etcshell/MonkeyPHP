<?php
/**
 * monkeyphp - Conflict.php
 *
 * PHP Version 5.4
 *
 * @author zhangming <zhangming@ec3s.com>
 * @copyright 2014 ec3s.com
 * @license Copyright (c) 2013 ec3s (http://www.ec3s.com)
 * @version GIT:<git_id>
 * @link http://www.ec3s.com
 */
namespace Monkey\Exceptions\Http;
/**
 * Class Conflict
 *
 * @package Monkey\Exceptions\Http
 */
class Conflict extends Base {
    /**
     * @var int
     */
    protected $statusCode = 409;
} 