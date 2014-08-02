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

use Composer\Autoload;
use Monkey\Exceptions;
use Monkey\Request\Request;
use Monkey\Response\Response;

/**
 * Class App
 *
 * 应用基类
 * 所有的应用类都继承此类
 *
 * @package Monkey
 */
class App {
    /**
     * 框架版本号
     */
    const MONKEY_VERSION = '2014.07.01';

    /**
     * 应用名字
     *
     * @var string
     */
    public $NAME;

    /**
     * 请求时间
     *
     * @var int
     */
    public $TIME;

    /**
     * 调试模式
     *
     * @var int
     */
    public $DEBUG = 0;

    /**
     * Monkey框架所在目录
     *
     * @var string
     */
    public $MONKEY_DIR;

    /**
     * 应用所在目录
     *
     * @var string
     */
    public $DIR;

    /**
     * 临时缓存目录
     *
     * @var string
     */
    public $TEMP;

    /**
     * 前端资源文件所在目录
     *
     * @var string
     */
    public $FRONT_ROOT_DIR;

    /**
     * 前端入口文件所在目录
     *
     * @var string
     */
    public $INDEX_ROOT_DIR;

    /**
     * 前端资源文件所在网址
     *
     * @var string
     */
    public $FRONT_ROOT_URL;

    /**
     * 前端入口文件所在网址
     *
     * @var string
     */
    public $INDEX_ROOT_URL;

    /**
     * 控制器对象
     *
     * @var Controller
     */
    public $controller;

    /**
     * 依赖容器类实例
     *
     * @var Container
     */
    protected $container;

    /**
     * 配置类实例
     *
     * @var Config
     */
    protected $config;

    /**
     * 请求对象
     *
     * @var Request
     */
    protected $request;

    /**
     * 响应对象
     *
     * @var Response
     */
    protected $response;

    /**
     * 配置文件位置
     *
     * @var string
     */
    protected $configFile = null;

    /**
     * 构造方法
     *
     * @param string $staticDir 静态资源目录
     */
    public function __construct($staticDir) {
        //设置框架目录
        $this->MONKEY_DIR = strtr(__DIR__, DIRECTORY_SEPARATOR, '/');

        //格式化静态资源目录
        $staticDir = strtr($staticDir, DIRECTORY_SEPARATOR, '/');

        //获取执行类名，并定义应用名
        $appClassName = get_class($this);
        $this->NAME = strstr($appClassName, '\\', true);;

        //获取应用文件名 及 应用目录
        $appFile = Autoload\Initializer::getLoader()->findFile($appClassName);
        $appDir = strtr(dirname($appFile), DIRECTORY_SEPARATOR, '/');
        $this->DIR = $appDir;

        //设置临时目录
        !$this->TEMP and $this->TEMP = $appDir . '/temp';

        //加载配置，并设置时区
        !$this->configFile and $this->configFile = $appDir . '/data/config.php';
        $this->config = new Config($this->configFile, $this->TEMP);
        date_default_timezone_set($this->config->get('timezone', 'PRC'));

        //加载请求对象
        $this->request = $request = new Request();

        //获取请求时间
        $this->TIME = $request->getTime();

        //设置前端资源目录 及其网址
        $documentRoot = $request->getDocumentRoot();
        $this->FRONT_ROOT_DIR = $staticDir;
        $this->FRONT_ROOT_URL = $documentRoot == $staticDir ? '' : substr($staticDir, strlen($documentRoot));

        //设置index目录 及其网址
        $this->INDEX_ROOT_DIR = $documentRoot . dirname($_SERVER['PHP_SELF']);
        $this->INDEX_ROOT_URL = dirname($_SERVER['PHP_SELF']);

        //装载注入容器，设置错误管理器，启动Session
        $this->container = new Container($this);
        $this->setError();
        $this->sessionStart();

        //装载响应类
        $this->response = new Response($this);
    }

    /**
     * 设置错误管理器
     */
    protected function setError() {
        Exceptions\Exception::$app = $this;
        Exceptions\Exception::$errorReporting = $this->errorReporting();

        //接管错误和异常处理
        error_reporting($this->DEBUG);

        //注册系统默认异常处理函数
        //set_exception_handler(array($this, 'exceptionHandler'));
        set_exception_handler(function (\Exception $e) {
            throw new Exceptions\Exception($e->getMessage(), $e->getCode(), $e->getPrevious(), $e->getFile(), $e->getLine());
        });

        //注册系统默认错误处理函数
        //set_error_handler(array($this, 'errorHandler'), $this->DEBUG);
        set_error_handler(function ($code = 0, $message = '', $file = null, $line = null) {
            throw new Exceptions\Exception($message, $code, null, $file, $line);
        }, $this->DEBUG);
    }

    /**
     * 启动Session
     */
    protected function sessionStart() {
        $this->container->getter('session');
    }

    /**
     * 获取请求对象
     *
     * @return Request
     */
    public function request() {
        return $this->request;
    }

    /**
     * 获取响应对象
     *
     * @return Response
     */
    public function response() {
        return $this->response;
    }

    /**
     * 清除临时目录
     */
    public function clearTempDir() {
        dir_delete($this->TEMP);
    }

    /**
     * 运行应用
     */
    public function run() {
        $this->dispatching();
    }

    /**
     * 获取配置类
     *
     * @return Config
     */
    public function config() {
        return $this->config;
    }

    /**
     * 获取默认缓存组件
     *
     * @param string $provider 缓存提供者
     *
     * @return \Monkey\Cache\CacheInterface
     */
    public function cache($provider = null) {
        return $this->container->getter('cache', $provider);
    }

    /**
     * 获取数据库组件
     *
     * @return \Monkey\Database\Database
     */
    public function database() {
        return $this->container->getter('database');
    }

    /**
     * 获取错误报告组件
     *
     * @return \Monkey\ErrorReporting\ErrorReporting
     */
    public function errorReporting() {
        return $this->container->getter('errorReporting');
    }

    /**
     * 获取日志组件
     *
     * @return \Monkey\Logger\Logger
     */
    public function logger() {
        return $this->container->getter('logger');
    }

    /**
     * 获取权限组件
     *
     * @return \Monkey\Permission\Permission
     */
    public function permission() {
        return $this->container->getter('permission');
    }

    /**
     * 获取路由组件
     *
     * @return \Monkey\Router\Router
     */
    public function router() {
        return $this->container->getter('router');
    }

    /**
     * 获取Shutdown组件
     *
     * @return \Monkey\Shutdown\Shutdown
     */
    public function shutdown() {
        return $this->container->getter('shutdown');
    }

    /**
     * 获取视图组件
     *
     * @return \Monkey\View\View
     */
    public function view() {
        return $this->container->getter('view');
    }

    /**
     * 停止并跳出整个调度器
     * 在浏览器中正常显示已经生成了的网页
     *
     * @param string $message
     * @param int $code
     * @param null|string $file
     * @param null|int $line
     *
     * @throws Exceptions\BreakException
     */
    public function stop($message, $code = 0, $file = null, $line = null) {
        throw new Exceptions\BreakException($message, $code, $file, $line);
    }


    /**
     * 分发路由
     *
     * @throws \Exception
     */
    public function dispatching() {
        static $isDispatching;

        if ($isDispatching) {
            return;
        }
        else {
            $isDispatching = true;
        }

        try {
            //获取路由组件
            $router = $this->router();

            //启动路由钩子
            $router->startHook();
            //            增加hook执行后的status判断和处理
            //            if(isset($router->hook->status['denied'])){
            //                //todo
            //            }

            //获取路由
            $route = $router->getRoute();

            if (!$route or !isset($route['controller']) or !isset($route['action'])) {
                //路由不存在
                throw new Exceptions\Http\NotFound('无法找到你访问的网页！');
            }

            //实例化控制器
            if (Autoload\Initializer::getLoader()->findFile($route['controller']) == false) {
                throw new Exceptions\Http\NotFound('访问的控制器[' . $route['controller'] . ']的类文件丢失！');
            }

            $controller = $route['controller'];
            $controller = new $controller($this);
            $action = 'action' . ucfirst($route['action']);

            if (!method_exists($controller, $action)) {
                //请求方法不存在
                throw new Exceptions\Http\NotFound('访问的方法[' . $route['action'] . ']不存在！');
            }

            //执行请求方法
            $controller->actionName = $route['action'];
            $controller->before($route['action']);
            $controller->$action();
            $controller->after($route['action']);

        }
        catch (Exceptions\BreakException $e) {
            //正常中断

        }
    }
}
