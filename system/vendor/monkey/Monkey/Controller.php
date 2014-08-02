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
namespace Monkey;

/**
 * Class Controller
 *
 * 控制器基类
 *
 * @package Monkey
 */
class Controller
{

    /**
     * 应用对象
     *
     * @var App
     */
    public $app;

    /**
     * 配置组件对象
     *
     * @var Config
     */
    public $appConfig;

    /**
     * 请求组件对象
     *
     * @var Request\Request
     */
    public $request;

    /**
     * 响应组件对象
     *
     * @var Response\Response
     */
    public $response;

    /**
     * 视图组件对象
     *
     * @var View\View
     */
    protected $view;

    /**
     * 请求方法名
     * 不含前缀'action'
     *
     * @var string
     */
    public $actionName;

    /**
     * 构造方法
     *
     * @param App $app
     */
    public function __construct($app)
    {
        $this->app = $app;
        $this->appConfig = $app->config();
        $this->request = $app->request();
        $this->response = $app->response();
        $app->controller = $this;
    }

    /**
     * 获取视图组件对象
     *
     * @return View\View
     */
    public function getView()
    {
        empty($this->view) and $this->view = $this->app->view();
        return $this->view;
    }

    /**
     * 获取请求组件对象
     *
     * @return Request\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * 获取响应组件对象
     *
     * @return Response\Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * 获取请求参数，包括$_GET、$_POST信息 或者命令行参数：$_SERVER["argv"]
     *
     * @param string|int $name 参数名称（web请求），或参数序号（cli请求）
     * @param string $defaultValue 当获取变量失败的时候返回该值,默认该值为null
     *
     * @return string
     */
    public function getParameter($name, $defaultValue = null)
    {
        return $this->request->getParameter($name, $defaultValue);
    }

    /**
     * 获取请求路径中的参数
     * @param string $name
     * @param string $default
     *
     * @return array
     */
    public function getRouteParameter($name, $default = null)
    {
        return $this->app->router()->getParameter($name, $default);
    }

    /**
     * 获取数据组件对象
     *
     * @param null $connectionName
     *
     * @return bool|Database\Connection|null
     */
    public function db($connectionName = null)
    {
        return $this->app->database()->getConnection($connectionName);
    }

    /**
     * action前置操作
     *
     * @param string $actionName 不含前缀 'action'
     */
    public function before($actionName)
    {

    }

    /**
     * action后置操作
     *
     * @param string $actionName 不含前缀 'action'
     */
    public function after($actionName)
    {

    }

    /**
     * 向浏览器返回json数据
     *
     * @param array $notice
     */
    public function returnJson($notice)
    {
        $this->app->response()->setJson($notice);
        exit;
    }

    /**
     * 设置立即重定向地址
     *
     * @param string $url 重定向的地址
     * @param bool $exitNow 立即退出，默认为否
     */
    public function redirect( $url , $exitNow=false )
    {
        $this->response->redirect($url, $exitNow);
    }

} 