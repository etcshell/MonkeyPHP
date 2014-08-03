<?php
/**
 * monkeyphp - Base.php
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

use Monkey\Exceptions\Exception;

class Base extends Exception {

    /**
     * http 错误代码
     *
     * @var int
     */
    protected $statusCode = 500;

    /**
     * construct method
     *
     * @param string $message error message
     * @param int $code error code
     *
     * @return \Monkey\Exceptions\Http\Base
     */
    public function __construct($message = '', $code = -1) {
        $code == -1 and $code = $this->statusCode;
        parent::__construct($message, $code);
    }

    /**
     * 获得出现异常的状态码
     *
     * @return int
     */
    public function getStatusCode() {
        return $this->statusCode;
    }
} 