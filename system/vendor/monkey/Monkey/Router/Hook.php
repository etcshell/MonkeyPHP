<?php
/**
 * Project MonkeyPHP
 *
 * PHP Version 5.3.9
 *
 * @package   Monkey\Router
 * @author    黄易 <582836313@qq.com>
 * @version   GIT:<git_id>
 */
namespace Monkey\Router;

use Monkey;

/**
 * Class Hook
 *
 * 路由hook
 *
 * @package Monkey\Router
 */
class Hook {

    /**
     * 应用对象
     *
     * @var Monkey\App
     */
    public $app;

    /**
     * 钩子执行状态
     *
     * @var array
     */
    public $status = array();

    /**
     * 钩子列表
     *
     * @var array
     */
    private $hooks;

    /**
     * 匹配中的钩子
     *
     * @var array
     */
    private $select = array();

    /**
     * 已执行了的钩子
     *
     * @var array
     */
    private $called;

    /**
     * 是否已启动钩子
     *
     * @var bool
     */
    private $started = false;

    /**
     * 构造方法
     *
     * @param Monkey\App $app
     */
    public function __construct($app) {
        $this->app = $app;
    }

    /**
     * 添加路由hook
     *
     * @param string $subPath 路径片段（匹配路由的头部）
     * @param \Closure $handle hook函数，通常用匿名函数代替
     * @param string $requestMethod 请求方法
     *
     * 例如：
     * $hook->add('/user', function($app,$hook){ ...; $hook->next(); }, 'get');
     */
    public function add($subPath, \Closure $handle, $requestMethod = 'all') {
        $subPath = '/' . trim(strtolower($subPath), '/');
        $requestMethod = strtolower($requestMethod);
        $this->hooks[$requestMethod][$subPath] = $handle;
    }

    /**
     * 启动路由hook
     *
     * @param string $requestPath 请求路径
     * @param string $requestMethod 请求方法
     */
    public function start($requestPath, $requestMethod = 'get') {
        if ($this->started) {
            return;
        }

        if (empty($this->hooks)) {
            return;
        }

        $this->started = true;
        $requestPath = '/' . ltrim(strtolower($requestPath), '/');
        $requestMethod = strtolower($requestMethod);
        $extension = array('.php' => 1, '.txt' => 1, '.html' => 1, '.json' => 1, '.xml' => 1);
        $this->matchHook('all', $requestPath, $extension); //匹配通用请求方法的路由hook
        $this->matchHook($requestMethod, $requestPath, $extension); //匹配指定请求方法的路由hook
        reset($this->select);
        $this->next();
    }

    private function matchHook($requestMethod, $requestPath, $extension) {
        $_ = '';
        foreach ($this->hooks[$requestMethod] as $subPath => $handle) {

            if (strpos($requestPath, $subPath) !== 0) {
                continue;
            }

            $_ = substr($requestPath, strlen($subPath));

            if (empty($_) or $_[0] == '/' or ($_[0] == '.' and $extension[$_])) {
                $this->select[$subPath] = $handle;
            }
        }
    }

    /**
     * 迭代运行路由hook
     * 本来可以用start方法中的循环语句直接运行hook的，但是为了更灵活的控制hook流程，在此改为迭代方式
     * 从call_user_func调用hook的方式可以看出，hook处理函数handle有1个参数：hook管理器$this
     * 另外handle中要想迭代继续，必须显示调用hook管理器中的next方法，也就是这个方法。
     * handle例：
     * function myHandle($hook){
     *     ...//Todo，处理你的逻辑
     *     $hook->next();
     * }
     */
    public function next() {
        if (empty($this->select)) {
            return;
        }

        $this->called[] = key($this->select);
        $handle = array_shift($this->select);
        call_user_func($handle, $this); //$handle($this->app,$this);
    }

    /**
     * 获取已经执行了的路由hook
     *
     * @return mixed
     */
    public function getCalled() {
        return $this->called;
    }
}

/*
增加一个route hook功能，即给指定的路由增加hook接口，同一个“route”可重复添加hook。
添加hook的route不一定是完整的路由，
比如'/'就相当于给所有路由增加了一个hook，‘/user’则相当于给所有以‘/user’开头的路由增加了一个hook
这个hook接口目前不能直接输出到浏览器，只能做验证、日志等后端操作，它仅仅是router的辅助功能，
要输出到浏览器，请使用route指定控制器，并把业务逻辑放到控制器或模块中
但是有个变通的用法，可以在hook中把所有要输出到浏览器的内容（比如页面广告）存储到一个公共的堆栈中，然后在渲染时统一检测堆栈并渲染输出
hook流程中的状态信息是隔离的，而且先于业务逻辑执行。
hook流程要与业务流程通信，唯一的办法是建立一个共享堆栈，hook流程只能写入，业务逻辑只能读取。
至于这个共享堆栈如何怎么写，根据个人喜好，比如当作app容器类的一个属性，或者单独写一个类等等。
*/