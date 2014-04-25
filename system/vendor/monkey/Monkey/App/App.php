<?php
namespace Monkey\App;

use Monkey;

/**
 * App
 * 应用基类
 * 所有的应用类都继承此类
 * @package Monkey\App
 */
class App
{
    public
        $NAME,//应用名字
        $TIME,//请求时间
        $DEBUG=0,//默认关闭调试模式
        $DIR,//应用所在目录
        $MONKEY_DIR,//Monkey框架所在目录
        $FRONT_ROOT_DIR,//前端根文件所在目录
        $FRONT_ROOT_URL,//前端根文件网址
        $isCli=false,//是否为命令行应用
        $type='web' //应用类型
    ;

    public
        /**
         * 依赖容器类
         * @var \Monkey\Container
         */
        $container,
        /**
         * 配置类
         * @var \Monkey\App\Config
         */
        $config,
        /**
         * 控制器类
         * @var \Monkey\Controller\Controller
         */
        $controller
    ;

    public function __construct( )
    {

    }

    /**
     * 运行应用
     */
    public function run()
    {
        $this->dispatching();
    }

    /**
     * 获取配置类
     * @return \Monkey\App\Config
     */
    public function config()
    {
        return $this->config;
    }

    /**
     * 获取一个配置项
     * @param string $key 配置名称
     * @param mixed  $defaultValue 返回失败时的默认值
     * @return mixed 配置值
     */
    public function getConfig($key, $defaultValue=null)
    {
        return Config::get($key,$defaultValue);
    }

    /**
     * 获取默认缓存组件
     * @param string $provider
     * @return \Monkey\Cache
     */
    public function cache($provider=null)
    {
        return $this->container->getter('cache',$provider);
    }

    /**
     * 获取Cookie对象
     * @return \Monkey\Cookie\Cookie
     */
    public function cookie()
    {
        return $this->container->getter('cookie');
    }

    /**
     * @return \Monkey\Database\Database
     */
    public function database()
    {
        return $this->container->getter('database');
    }

    /**
     * @return \Monkey\ErrorReporting\ErrorReporting
     */
    public function errorReporting()
    {
        return $this->container->getter('errorReporting');
    }

    /**
     * @return \Monkey\Logger\Logger
     */
    public function logger()
    {
        return $this->container->getter('logger');
    }

    /**
     * @param string $provider
     * @return \Monkey\Request\Request
     */
    public function request($provider=null)
    {
        return $this->container->getter('request',$provider ? $provider : $this->type);
    }

    /**
     * @param string $provider
     * @return \Monkey\Response\Response
     */
    public function response($provider=null)
    {
        return $this->container->getter('response',$provider ? $provider : $this->type);
    }

    /**
     * @return \Monkey\Router\Router
     */
    public function router()
    {
        return $this->container->getter('router');
    }

    /**
     * @param string $provider
     * @return \Monkey\Session

    public function session($provider=null)
    {
        return $this->container->getter('session',$provider);
    }
     */

    /**
     * @param string $provider
     */
    public function sessionStart($provider=null)
    {
        $this->container->getter('session',$provider);
    }

    /**
     * @return \Monkey\Shutdown\Shutdown
     */
    public function shutdown()
    {
        return $this->container->getter('shutdown');
    }

    /**
     * @return \Monkey\View\View
     */
    public function view()
    {
        return $this->container->getter('view');
    }

    /**
     * 报错并结束程序
     * 会在浏览器显示错误页
     * @param string $message
     * @param int $code
     * @param null|string $file
     * @param null|int $line
     * @throws \Monkey\Exception
     */
    public function exception($message,$code=0,$file=null,$line=null)
    {
        throw new Monkey\Exception($message,$code,$file,$line);
    }

    /**
     * 停止并跳出整个调度器
     * 在浏览器中正常显示已经生成了的网页
     * @param string $message
     * @param int $code
     * @param null|string $file
     * @param null|int $line
     * @throws \Monkey\BreakException
     */
    public function stop($message,$code=0,$file=null,$line=null)
    {
        throw new Monkey\BreakException($message,$code,$file,$line);
    }

    /**
     * 分发路由
     */
    protected function dispatching()
    {
        static $isDispatching;
        if($isDispatching)
            return ;
        else
            $isDispatching=true;
        try{
            $router=$this->router()->getRouter();
            /**
             * 路由结构为数组：
             *      array(
             *         'controller'=>'Example',//要运行的控制器名。
             *         'action'=>'hello',//要激活的方法
             *      ),
             */
            if(!$router){
                $this->exception('无法找到你访问的网页！', 404);
            }
            $controller=$router['controller'];
            $action=$this->getConfig('action_prefix','');
            $action.=$router['action'];
            $controllerFile =strtr($controller,'\\','/').'.php';//这句注意与自动加载规则保持一致！
            if(!file_exists(dirname($this->DIR).$controllerFile)){
                $this->exception('访问的控制器['.$router['controller'].']的类文件['.$controllerFile.']丢失！', 404);
            }
            $controller = new \ReflectionClass($controller);//建立反射类
            if(!$controller->hasMethod($action)){
                $this->exception('访问的方法['.$router['action'].']不存在！', 404);
            }
            $action=$controller->getMethod($action);
            if(!$action->isPublic() or $action->isAbstract() or $action->isStatic()){
                $this->exception('访问的方法['.$router['action'].']不存在（不公有 或 未实现——为抽象的 或为静态方法）！', 404);
            }
            /*
             * 验证控制器是否规范，严格遵守控制器的命名空间写法，可以注释掉这里的代码。
            $class=$controller;
            while ($parent = $class->getParentClass()) {
                if($parent->getName()=='Monkey\Controller\Controller'){
                    $find=true;
                    break;
                };
                $class = $parent;
            }
            if(!$find){
                $this->exception('访问的控制器['.$router['controller'].']不规范！', 404);
            }
            */
            $controller=$controller->newInstance($this);
            $controller->actionName=$router['action'];
            $controller->before($router['action']);
            $action->invoke($controller);
            $controller->after($router['action']);
        }
        catch(Monkey\BreakException $e){
        }
        catch(Monkey\Exception $e){
            $report=$this->errorReporting();
            if(!$this->DEBUG and $e->getCode()==403){
                $report->show403();
            }
            elseif(!$this->DEBUG and  $e->getCode()==404 ){
                $report->show404();
            }
            else{
                $report->showError($e::getErrorInfo());
            }
        }
    }
}
