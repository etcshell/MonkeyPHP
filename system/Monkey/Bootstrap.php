<?php
/**
 * MonkeyPHP启动代码
 */

/*
 * 常量列表：
 *
 * FRONT_FILE       前端文件名
 * APP_PATH         应用路径
 * APP_NAME         应用名
 *
 * FRONT_URL        前端网址
 *
 * DEBUG            调试级别
 * __VERSION__      MonkeyPHP版本号
 * TIME             请求时间
 * SYSTEM           系统目录
 */

if(!defined('FRONT_FILE') || !defined('APP_PATH') || !defined('APP_NAME'))
{
    exit('Invalid call !');
}
require __DIR__ . '/Core/function.php';

//定义常量
!defined('DEBUG') and define('DEBUG',0);
define('__VERSION__', '0.0-2013.07.01' );
define('TIME', $_SERVER['REQUEST_TIME']);//请求时间
define('SYSTEM', strtr(dirname(__DIR__),DIRECTORY_SEPARATOR,'/') );//系统目录
define('FRONT_URL', bootstrap());

/**
 * 应用信息容器类
 */
class App
{
    public static $config=array();
    public static $error=array();
    private function __construct(){}
}

/**
 * 获取一个配置项
 * @param string $name 配置名称
 * @param mixed  $default 返回失败时的默认值
 * @return mixed 配置值
 */
function config_get($name, $default = null)
{
    return isset(App::$config[$name]) ? App::$config[$name] : $default;
}

/**
 * 批量添加配置
 * @param array $configs
 */
function config_add($configs = array())
{
    App::$config = array_merge(App::$config, $configs);
}

function bootstrap()
{
    static $isBootstrap;
    if($isBootstrap)
        return;
    else
        $isBootstrap=true;

    //检查php的版本号
    if( version_compare(PHP_VERSION, '5.3.11') < 0 )
    {
        exit('FrameworkPHP目前只能运行在【PHP 5.3.11】及以上的环境下，请升级你的php版本.');
    }

    //设置调试
    error_reporting(DEBUG);

    //注册自动加载
    function  __FrameworkAutoload($class) {   // convert namespace to full file path
        $class = SYSTEM.'/' . strtr($class, '\\', '/') . '.php';
        require_once($class);
    }
    spl_autoload_register('__FrameworkAutoload');

    //设置时区
    date_default_timezone_set(App::$config['sys_timezone']);

    //去掉(还原)因为magic_quotes_gpc开启时加上的转义字符，当 magic_quotes_gpc (GPC, Get/Post/Cookie) 打开时，GPC所有的 ' (单引号), " (双引号), \ (反斜线) and 空字符会自动转为含有反斜线的转义字符。
    if(get_magic_quotes_gpc()){
        $_POST=stripslashes_deep($_POST);
        $_GET=stripslashes_deep($_GET);
        $_COOKIE=stripslashes_deep($_COOKIE);
    }

    //修复IIS服务器$_SERVER['DOCUMENT_ROOT']失效的情况
    if(!isset($_SERVER['DOCUMENT_ROOT']))
    {
        if(isset($_SERVER['SCRIPT_FILENAME']))
        {
            $_SERVER['DOCUMENT_ROOT']=substr($_SERVER['SCRIPT_FILENAME'], 0, 0 - strlen($_SERVER['PHP_SELF']));
        }
        elseif(isset($_SERVER['PATH_TRANSLATED']))
        {
            $_SERVER['DOCUMENT_ROOT']=substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0 - strlen($_SERVER['PHP_SELF']));
        }
    }

    //修正DOCUMENT_ROOT末尾可能因为apache配置造成多余一个'/'。
    $_SERVER['DOCUMENT_ROOT']=dir_format($_SERVER['DOCUMENT_ROOT']);

    //加载配置
    $configFile=APP_PATH.'/Config.php';
    if(DEBUG && file_exists($configFile)) unlink($configFile);
    if(file_exists($configFile))
    {
        config_add(unserialize(file_get_contents($configFile)));
    }
    else
    {
        $dir= APP_PATH.'/Configs';
        foreach(glob($dir.'/*.ini.php', GLOB_NOESCAPE) as $config)
        {
            require $config;
        }
        $dir= SYSTEM.'/GlobeData/Configs';
        foreach(glob($dir.'/*.ini.php', GLOB_NOESCAPE) as $config)
        {
            require $config;
        }
        file_put_contents($configFile,serialize(App::$config),LOCK_EX);
    }

    $absolute=dirname(FRONT_FILE);
    $root=$_SERVER['DOCUMENT_ROOT'];
    return $root==$absolute?'':substr($absolute, strlen($root));
}
/**
 * 报错并结束程序
 * 会在浏览器显示错误页
 * @param string $message
 * @param int $code
 * @param null|string $file
 * @param null|int $line
 * @throws Exception
 */
function error_exit($message,$code=0,$file=null,$line=null)
{
    throw new Exception($message,$code,$file,$line);
}

/**
 * 停止并跳出整个调度器
 * 在浏览器中正常显示已经生成了的网页
 * @param string $message
 * @param int $code
 * @param null|string $file
 * @param null|int $line
 * @throws Monkey\BreakException
 */
function stop_break($message,$code=0,$file=null,$line=null)
{
    throw new Monkey\BreakException($message,$code,$file,$line);
}

/**
 * 获取MonkeyPHP框架服务容器对象
 * 这个容器中有框架提供的一切组件
 * @return \Monkey\Monkey
 */
function monkey()
{
    static $oMonkey;
    !$oMonkey and $oMonkey=new Monkey\Monkey(App::$config);
    return $oMonkey;
}