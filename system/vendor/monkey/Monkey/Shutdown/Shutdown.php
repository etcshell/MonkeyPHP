<?php
/**
 * Project MonkeyPHP
 *
 * PHP Version 5.3.9
 *
 * @package   Monkey\Shutdown
 * @author    黄易 <582836313@qq.com>
 * @version   GIT:<git_id>
 */
namespace Monkey\Shutdown;

use Monkey;

/**
 * Class Shutdown
 *
 * Shutdown组件
 *
 * @package Monkey\Shutdown
 */
class Shutdown {
    /**
     * 应用对象
     *
     * @var Monkey\App
     */
    public $app;

    /**
     * 回调列表
     *
     * @var array
     */
    private $callbacks = array();

    /**
     * 标记自己执行
     *
     * @var string
     */
    private $calledByMe = 'This is called by the framework';

    /**
     * 构造方法
     *
     * @param Monkey\App $app
     */
    public function __construct($app) {
        $this->app = $app;
        register_shutdown_function(array($this, 'execute'), $this->calledByMe);
        $config = $app->config()->getComponentConfig('shutdown', 'default');

        foreach ($config as $call) {
            $this->register($call);
        }
    }

    /**
     * 注册结束后的回调函数
     *
     * @param string|array $callback
     *
     * 用法：
     * 1. $shutdown->register( 'myfunction' );
     * 2. $shutdown->register( 'myclass::staticmethod' );
     * 3. $shutdown->register( array('myclass', 'method') );
     * 4. $shutdown->register( array( $myObject, 'method') );
     */
    public function register($callback) {
        $this->callbacks[] = $callback;
    }

    /**
     * 不建议使用，框架内部方法
     *
     * @return array
     */
    public function getCallbacks() {
        return $this->callbacks;
    }

    /**
     * 不建议使用，框架内部方法
     */
    public function execute() {
        if (func_get_arg(0) != $this->calledByMe) {
            return;
        }

        foreach ($this->callbacks as $Callback) {
            call_user_func($Callback);
        }
    }
}