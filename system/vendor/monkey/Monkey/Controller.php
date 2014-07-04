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
     * 获取路由匹配中的参数
     *
     * @return array
     */
    public function getRouteParameter()
    {
        return $this->app->router()->getParameters();
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
     * 向浏览器返回ajax数据
     *
     * @param int $statusCode
     * @param string $message
     * @param array $data
     */
    public function ajaxReturn($statusCode, $message, $data = array())
    {
        $this->app->response()->setAjax()->setJsonNotice($statusCode, $data, $message);
        exit;
    }

    /**
     * 模板赋值
     *
     * @param string $name 变量名
     * @param mixed $value 变量值
     *
     * @return $this
     */
//    public function assign($name, $value)
//    {
//        $this->getView()->assign($name,$value);
//        return $this;
//    }

    /**
     * 渲染
     *
     * @param string $tplFilename 模板文件名，相对于模板根目录。
     *
     * @return string 直接输出后返回空字符串
     */
//    public function render($tplFilename)
//    {
//        $this->write($this->getView()->render($tplFilename, false));
//    }

    /**
     * 渲染
     *
     * @param string $tplFilename 模板文件名，相对于模板根目录。
     * @param int $expire 缓存时间，默认保存时间为0（永久保存），24小时为86400*1
     *
     * @return string 始终返回渲染结果
     */
//    public function renderWithCache($tplFilename,$expire=0)
//    {
//        $this->write($this->view->renderWithCache($tplFilename, $expire));
//    }

    /**
     * 添加Html正文Body文档正文
     *
     * @param string $content 内容
     */
//    public function write($content)
//    {
//        $this->response->write($content);
//    }

    /**
     * 添加Html正文Body文档正文
     *
     * @param string $content 内容
     */
//    public function writeLine($content)
//    {
//        $this->response->writeLine($content);
//    }

    /**
     * 获取Html正文Body文档正文
     *
     * @return string
     */
//    public function getBody()
//    {
//        return $this->response->getBody();
//    }

    /**
     * 清除写入响应对象中的响应体
     */
//    public function clearBody()
//    {
//        $this->response->clearBody();
//    }

    /**
     * 设置立即重定向地址
     *
     * @param string $url 重定向的地址
     * @param bool $exitNow 立即退出，默认为否
     */
//    public function redirect( $url , $exitNow=false )
//    {
//        $this->response->redirect($url, $exitNow);
//    }

} 