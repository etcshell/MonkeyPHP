<?php
namespace Monkey\Controller;

use Monkey;

/**
 * Controller
 * 控制器基类
 * @package Monkey\Controller
 */
class Controller {

    public
        /**
         * @var Monkey\App\App
         */
        $app,
        /**
         * @var Monkey\App\Config
         */
        $appConfig,
        /**
         * @var \Monkey\Request\Request
         */
        $request,
        /**
         * @var \Monkey\Response\Response
         */
        $response,
        /**
         * @var \Monkey\View\View
         */
        $view,
        $actionName
    ;
    /**
     * @param Monkey\App\App $app
     */
    public function __construct($app)
    {
        $this->app=$app;
        $this->appConfig=$app->config();
        $this->request=$app->request();
        $this->response=$app->response();
        $app->controller=$this;
    }

    /**
     * @return Monkey\View\View
     */
    protected function getView()
    {
        empty($this->view) and $this->view=$this->app->view();
        return $this->view;
    }

    /**
     * @return Monkey\Request\Request
     */
    protected function getRequest()
    {
        return $this->request;
    }

    /**
     * @return Monkey\Response\Response
     */
    protected function getResponse()
    {
        return $this->response;
    }

    /**
     * 获取请求参数，包括$_GET、$_POST信息或者命令行参数
     * @param string|int $name 参数名称（web请求），或参数序号（cli请求）
     * @param string $defaultValue 当获取变量失败的时候返回该值,默认该值为null
     * @return string
     */
    protected function getParameter($name, $defaultValue = null)
    {
        return $this->request->getParameter($name,$defaultValue);
    }

    /**
     * 获取路由匹配中的参数
     * @return array
     */
    protected function getRouteParameter()
    {
        return $this->app->router()->getParameters();
    }

    /**
     * @param null $connectionName
     * @return bool|Monkey\Database\Connection|null
     */
    protected function db($connectionName=null)
    {
        return $this->app->database()->getConnection($connectionName);
    }

    /**
     * action前置操作
     * @param $actionName
     */
    public function before($actionName)
    {

    }

    /**
     * action后置操作
     * @param $actionName
     */
    public function after($actionName)
    {

    }

    /**
     * 获取或设置用户选择的语言
     * @param string|null $name   设置用户选择的语言，为空则表示读取用户选择的语言
     * @param int|null $expire 第一参数非空时：设置持续的有效时间,默认为见配置.设置为0相当于删除它；第一参数为空：获取的默认语言
     * @return string
     *
     * $lan= language()   直接获取用户选择的语言
     * $lan= language(null, 'zh-cn')   获取用户选择的语言，如何用户没有设置，则返回'zh-cn'
     * language('zh-cn')  设置用户语言为'zh-cn'
     * language('zh-cn', 2592000)  设置用户语言为'zh-cn'，有效期30天，这个也是默认值
     * language('zh-cn', 0)  删除用户设置的语言
     * language(null, 0)  删除用户设置的语言
     */
    protected function language($name = null, $expire = null)
    {
        if($name or $expire===0){
            $this->app->cookie()->language($name, $expire);
        }
        else{
            $lan=$this->app->cookie()->language(null,$expire);
            if($lan)return $lan;
            $lan=$this->app->request()->header()->getAcceptLanguages();
            return $lan[0];
        }
        return '';
    }

    /**
     * 200=>操作成功,300=>操作失败,301=>会话超时
     * @param int $statusCode
     * @param string $message
     * @param array $data
     */
    protected function ajaxReturn($statusCode, $message, $data=array())
    {
        $jsonData = array (
            'statusCode' => $statusCode,
            'message' => $message,
            'data' => $data
        );
        $this->app->response()->setAjax()->sendHeaders();
        exit (json_encode($jsonData));
    }

    protected function tokenCheck()
    {

    }

    /**
     * 模板赋值
     * @param string $name 变量名
     * @param mixed $value 变量值
     * @return $this
     */
    protected function assign($name, $value)
    {
        $this->getView()->assign($name,$value);
        return $this;
    }

    /**
     * 渲染
     * @param string $tplFilename  模板文件名，相对于模板根目录。
     * @return string 直接输出后返回空字符串
     */
    protected function render($tplFilename)
    {
        $this->write($this->getView()->render($tplFilename, false));
    }

    /**
     * 渲染
     * @param string $tplFilename  模板文件名，相对于模板根目录。
     * @param int $expire 缓存时间，默认保存时间为0（永久保存），24小时为86400*1
     * @return string 始终返回渲染结果
     */
    public function renderWithCache($tplFilename,$expire=0)
    {
        $this->write($this->view->renderWithCache($tplFilename, $expire));
    }

    /**
     * 添加Html正文Body文档正文
     * @param string $content 内容
     */
    public function write($content)
    {
        $this->response->write($content);
    }

    /**
     * 添加Html正文Body文档正文
     * @param string $content 内容
     */
    public function writeLine($content)
    {
        $this->response->writeLine($content);
    }

    /**
     * 获取Html正文Body文档正文
     * @return string
     */
    public function getBody()
    {
        return $this->response->getBody();
    }

    /**
     * 清除写入响应对象中的响应体
     */
    public function clearBody()
    {
        $this->response->clearBody();
    }

    /**
     * 设置立即重定向地址
     * @param string $url 重定向的地址
     * @param bool $exitNow 立即退出，默认为否
     */
    public function redirect( $url , $exitNow=false )
    {
        $this->response->redirect($url, $exitNow);
    }

} 