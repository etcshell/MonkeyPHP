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
 * Class Router
 *
 * 路由组件
 *
 * @package Monkey\Router
 */
class Router
{
    /**
     * 应用对象
     *
     * @var Monkey\App
     */
    public $app;

    /**
     * 匹配对象
     *
     * @var Pattern
     */
    public $pattern;

    /**
     * 路由钩子
     *
     * @var Hook
     */
    public $hook;

    /**
     * 路由映射
     *
     * @var Map
     */
    private $map;

    /**
     * 配置
     *
     * @var array
     */
    private $config;

    /**
     * 请求路径
     *
     * @var string
     */
    private $path;

    /**
     * 请求路由
     *
     * @var array
     */
    private $route;

    /**
     * 请求参数
     *
     * @var array
     */
    private $params;

    /**
     * 请求方法
     *
     * @var string
     */
    private $requestMethod;

    /**
     * index文件目录
     *
     * @var string
     */
    private $indexRoot;

    /**
     * 构造方法
     *
     * @param Monkey\App $app
     */
    public function __construct($app)
    {
        $this->app = $app;
        $config = $app->config()->getComponentConfig('router', 'default');
        $this->config = $config;
        $this->indexRoot = $app->INDEX_ROOT_URL;
        $this->pattern = new Pattern($app, $config);
        $this->loadPath();
        $this->requestMethod = $app->request()->getMethod();
        $match = $this->pattern->matchPath($this->requestMethod, $this->path);
        $this->params = isset($match['params']) ? $match['params'] : null;
        $route = array();

        if (isset($match['router_name'])) {
            $config['router_class_auto_prefix'] and $match['router_name'] = '\\' . $this->app->NAME . '\\Controller\\' . $match['router_name'];
            list($route['controller'], $route['action']) = explode(':', $match['router_name'], 2);
        }

        $this->route = $route;
        $this->hook = new Hook($app);
    }

    /**
     * 获取路由映射对象
     *
     * @return Map
     */
    public function map()
    {
        if (!$this->map) $this->map = new Map($this->app, $this->config);
        return $this->map;
    }

    /**
     * 获取路由hook管理器
     *
     * @return Hook
     */
    public function getHook()
    {
        return $this->hook;
    }

    /**
     * 启动路由hook
     */
    public function startHook()
    {
        $this->hook->start($this->path, $this->requestMethod);
    }

    /**
     * 获取请求路径
     *
     * @return string。
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * 获取请求路由
     *
     * @return array|null 存在时候为array；不存在则为空值。
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * 获取请求路径中的参数
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->params;
    }

    /**
     * 还原路径
     *
     * @param string $pattern 路径模式  get/test/abc-{zh|en}/blog/{year}-([1-9]\d*):language:year:id
     * 其中请求方法get是可选的 如 /test/abc-{zh|en}/blog/{year}-([1-9]\d*):language:year:id
     * @param string $parameters 参数  array('language'=>'en','year'=>'2014','id'=4025
     *
     * @return string  /test/abc-en/blog/2014-4025
     */
    public function toPath($pattern, $parameters = null)
    {
        return $this->pattern->packagePath($pattern, $parameters);
    }

    /**
     * 组装相对网址链接
     *
     * @param string $pattern 路径模式  get/test/abc-{zh|en}/blog/{year}-([1-9]\d*):language:year:id
     * 其中请求方法get是可选的 如 /test/abc-{zh|en}/blog/{year}-([1-9]\d*):language:year:id
     * @param string $parameters 参数  array('language'=>'en','year'=>'2014','id'=4025
     * @param bool $fixExtend
     *
     * @return string  /.../test/abc-en/blog/2014-4025
     */
    public function toURL($pattern, $parameters = null, $fixExtend = true)
    {
        if ($parameters or strpos($pattern, ':') !== false) {
            $pattern = $this->pattern->packagePath($pattern, $parameters);
        }

        $mode = $this->config['search_mode'];
        $pattern[0] != '/' and $pattern = '/' . $pattern;
        //rewrite（需服务器支持）、pathinfo（需服务器支持）、get（传统方式）
        if ($mode == 'rewrite') {
            $fixExtend and $pattern = $this->fixToUrl($pattern);
            return $this->indexRoot . $pattern;
        }

        if ($mode == 'pathinfo') {
            return $this->indexRoot . '/' . basename($_SERVER['SCRIPT_NAME']) . $pattern;
        }

        return $this->indexRoot . '/' . basename($_SERVER['SCRIPT_NAME']) . '?' .
            $this->config['search_get'] . '=' . $pattern;
    }

    /**
     * 组装相对网址链接
     *
     * @param string $pattern 路径模式  get/test/abc-{zh|en}/blog/{year}-([1-9]\d*):language:year:id
     * 其中请求方法get是可选的 如 /test/abc-{zh|en}/blog/{year}-([1-9]\d*):language:year:id
     * @param string $parameters 参数  array('language'=>'en','year'=>'2014','id'=4025
     * @param bool $forceHttps 是否强制使用https协议， false时将使用请求时的协议。
     *
     * @return string  http://www.host.com/.../test/abc-en/blog/2014-4025
     */
    public function toAbsURL($pattern, $parameters = null, $forceHttps = false)
    {
        $uri = $this->toURL($pattern, $parameters);
        $uri = $this->app->request()->getUriPrefix() . $uri;
        $forceHttps and $uri = 'https' . strstr($uri, ':');
        return $uri;
    }

    /**
     * 清除编译好的路由匹配表
     */
    public function clearPatternCompiled()
    {
        $this->pattern->clearPatternCompiled();
    }

    private function loadPath()
    {
        $config = $this->config;

        if (php_sapi_name() == 'cli') {
            $this->path = $_SERVER["argv"][1];

        } else if ($config['search_mode'] === 'rewrite' or $config['search_mode'] === 'pathinfo') {
            $this->path = $_SERVER['REQUEST_URI'] ? $this->_getParams($_SERVER['REQUEST_URI']) : '';

        } else if (isset($_GET[$config['search_get']])) {
            $this->path = $_GET[$config['search_get']];

        } else {
            $this->path = '';
        }
    }

    private function _getParams($url)
    {
        $temp = strstr($url, '?', true);
        $temp !== false and $url = $temp;
        //默认认为REQUEST_URI包含了子目录，那么就要去除网址偏移量
        $this->indexRoot and $url = substr($url, strlen($this->indexRoot));
        $url = '/' . trim($url, '/');
        $frontFile = basename($_SERVER['SCRIPT_NAME']);
        strrchr($url, '/') == '/' . $frontFile and $url = substr($url, 0, 0 - strlen($frontFile) - 1);
        return $url;
    }

    private function fixToUrl($url)
    {
        if (substr($url,-1) == '/') {
            return $url;
        }

        $_ext = array('.php' => '.php', '.html' => '.html');
        $ext = strtolower(strrchr($url, '.'));

        if (!$ext or !isset($_ext[$ext])) {
            $url .= '.html';
        }

        return $url;
    }
}