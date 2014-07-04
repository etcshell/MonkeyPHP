<?php
/**
 * Project MonkeyPHP
 *
 * PHP Version 5.3.9
 *
 * @package   Monkey\ErrorReporting
 * @author    黄易 <582836313@qq.com>
 * @version   GIT:<git_id>
 */
namespace Monkey\ErrorReporting;

use Monkey;

/**
 * Class ErrorReporting
 *
 * 错误报告组件
 *
 * @package Monkey\ErrorReporting
 */
class ErrorReporting
{
    /**
     * 应用对象
     *
     * @var Monkey\App $app
     */
    public $app;

    /**
     * 配置
     *
     * @var array
     */
    private $config;

    /**
     * 构造方法
     *
     * @param Monkey\App $app
     */
    public function __construct($app)
    {
        $this->app = $app;
        $config = $app->config()->getComponentConfig('errorReporting', 'default');
        $dir = isset($config['errorTemplate']) ?
            $app->DIR . $config['errorTemplate'] :
            strtr(__DIR__, DIRECTORY_SEPARATOR, '/') . '/errorTemplate';

        $this->config = array(
            'error_tpl_403' => $dir . '/403.tpl.php',
            'error_tpl_404' => $dir . '/404.tpl.php',
            'error_tpl_debug' => $dir . '/debug.tpl.php',
            'error_tpl_public' => $dir . '/public.tpl.php',
        );
    }

    /**
     * 显示403错误
     */
    public function show403()
    {
        $errorInfo['code'] = '403';
        $errorInfo['msg'] = '你无权访问！';
        $this->_show($errorInfo, $this->config['error_tpl_403']);
    }

    /**
     * 显示404错误
     */
    public function show404()
    {
        $errorInfo['code'] = '404';
        $errorInfo['msg'] = '_error，页面没有找到！';
        $this->_show($errorInfo, $this->config['error_tpl_404']);
    }

    /**
     * 显示错误信息
     *
     * @param array $errorInfo 错误信息
     * @param bool $exit 是否在显示完毕后退出程序
     */
    public function showError(array $errorInfo, $exit = false)
    {
        if (empty($errorInfo)) {
            $errorInfo['title'] = '未知错误';
            $errorInfo['code'] = '未知';
            $errorInfo['message'] = '未知';
            $errorInfo['time'] = $this->app->TIME;
            $errorInfo['path'] = $this->app->request()->getUri();
            $errorInfo['ip'] = $this->app->request()->getIP();
            $errorInfo['backtrace'] = print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true);
            $errorInfo['goto_index'] = $this->app->INDEX_ROOT_URL;
        }

        if ($this->app->DEBUG) { //显示所有错误信息
            $this->_show($errorInfo, $this->config['error_tpl_debug']);

        } else { //显示可公开的错误信息
            $_errorInfo['title'] = $errorInfo['title'];
            $_errorInfo['code'] = $errorInfo['code'];
            $_errorInfo['time'] = $errorInfo['time'];
            $this->_show($_errorInfo, $this->config['error_tpl_public']);
        }

        if ($exit) {
            exit();
        }

    }

    private function _show($errorInfo, $tpl)
    {
        $errorInfo['goto_index'] = $this->app->FRONT_ROOT_URL . '/index.php';

        if ($this->app->request()->isAjax()) {
            ob_clean(); //清除之前输出的内容
            echo json_encode(notice(false, $errorInfo));

        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            //载入显示模板
            require $tpl;

        } else {
            echo PHP_EOL, '程序运行出错：';
            foreach ($errorInfo as $key => $value) echo PHP_EOL, $key, ': ', $value;
            echo PHP_EOL, '错误信息结束。';
        }
    }
}