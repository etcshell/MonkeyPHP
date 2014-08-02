<?php
/**
 * monkeyphp - BadRequest.php
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

//use DefaultApp\LabelApi\Base;

/**
 * Class BadRequest
 *
 * @package Monkey\Exceptions\Http
 */
class BadRequest extends Base
{
    /**
     * @var int
     */
    protected $statusCode = 400;
} 