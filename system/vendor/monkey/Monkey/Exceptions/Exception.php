<?php
/**
 * Project MonkeyPHP
 *
 * PHP Version 5.3.9
 *
 * @package   Monkey
 * @author    黄易 <582836313@qq.com>
 * @version   GIT:<git_id>
 */
namespace Monkey\Exceptions;

use Monkey;

/**
 * Class Exception
 *
 * 异常接管类
 *
 * @package Monkey\Exceptions
 */
class Exception extends \Exception {

    /**
     * 应用对象
     *
     * @var Monkey\App
     */
    public static $app;

    /**
     * @var Monkey\ErrorReporting\ErrorReporting
     */
    public static $errorReporting;

    /**
     * 错误标题
     *
     * @var array
     */
    private static $errorTitle = array(
        -1 => '其它错误(E_OTHER)',
        1 => '致命错误(E_ERROR)',
        2 => '警告(E_WARNING)',
        4 => '语法解析错误(E_PARSE)',
        8 => '提示(E_NOTICE)',
        16 => '启动时的致命错误(E_CORE_ERROR)',
        32 => '启动时的非致命错误(E_CORE_WARNING)',
        64 => '编译错误(E_COMPILE_ERROR)',
        128 => '编译警告(E_COMPILE_WARNING)',
        256 => '致命错误(E_USER_ERROR)',
        512 => '警告(E_USER_WARNING)',
        1024 => '提示(E_USER_NOTICE)',
        2047 => 'E_ALL',
        2048 => '建议您改变代码，以提高代码的互用性和兼容性(E_STRICT)'
    );

    public function __construct($message = '', $code = 0, $previous = null, $file = null, $line = null) {
        if (!is_numeric($code)) {
            $message = 'Error code: ' . $code . '; ' . $message;
            $code = -1;
        }
        parent::__construct($message, $code, $previous);

        !is_null($file) and $this->file = $file;
        !is_null($line) and $this->line = $line;

        $info = array();
        $app = & self::$app;

        //设置错误信息
        $info['time'] = date('Y-m-d H:i:s', $app->TIME);
        $info['title'] = isset(self::$errorTitle[$this->getCode()]) ? self::$errorTitle[$this->getCode()] : '应用程序错误';
        $info['code'] = $this->getCode();
        $info['file'] = $this->file;
        $info['line'] = $this->line;
        $info['message'] = $this->getMessage();
        $info['path'] = $app->request()->getUri();
        $info['ip'] = $app->request()->getIP();
        $info['backtrace'] = $this->getTraceAsString();

        //记录日志
        !$app->DEBUG and $app->logger()->error($info);

        //显示错误
        $this->showError($info);

    }

    /**
     * 显示错误
     *
     * @param array $info
     */
    public function showError($info) {
        self::$errorReporting->showError($info, true);
    }

}