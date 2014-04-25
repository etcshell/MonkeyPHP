<?php
namespace Monkey\App;

use Monkey;
use Composer\Autoload;

/**
 * AppCreator
 * 应用生成器类
 * @package Monkey\App
 */
class AppCreator {

    /**
     * @var \Monkey\App\App
     */
    private $app;

    public function __construct($appName,$appDir,$staticDir)
    {
        if( version_compare(PHP_VERSION, '5.3.8') < 0 ){
            exit('MonkeyPHP目前只能运行在【PHP 5.3.8】及以上的环境下，请升级你的php版本.');
        }
        Monkey\Monkey::$DIR= strtr(Monkey\Monkey::$DIR,DIRECTORY_SEPARATOR,'/');
        //自动加载应用程序的命名空间
        //$appName=ucfirst(strtolower($appName));
        Autoload\Initializer::getLoader()->set($appName,$appDir);
        $appClassName= '\\'.$appName.(substr(PHP_SAPI, 0, 3) == 'cli'? '\\AppCli' :'\\AppWeb');
        $this->app= new $appClassName();
        $app= $this->app;
        empty($app->NAME) and $app->NAME=$appName;
        $app->TIME=$_SERVER['REQUEST_TIME'];
        empty($app->DIR) and $app->DIR=strtr($appDir,DIRECTORY_SEPARATOR,'/').'/'.$appName;
        $app->MONKEY_DIR=Monkey\Monkey::$DIR;
        $this->setError();
        $this->setConfig();
        $this->setEnvironment();
        $app->FRONT_ROOT_DIR= $staticDir;
        $app->FRONT_ROOT_URL= $_SERVER['DOCUMENT_ROOT']==$staticDir?'':substr($staticDir, strlen($_SERVER['DOCUMENT_ROOT']));
        $app->container= new Monkey\Container($app);//装载注入容器
    }

    /**
     * 获取应用
     * @return App
     */
    public function getApp()
    {
        return $this->app;
    }

    private function setError()
    {
        $debug=$this->app->DEBUG;
        //接管错误和异常处理
        error_reporting($debug);
        //注册系统默认异常处理函数
        set_exception_handler('framework_exception_handler');
        //注册系统默认错误处理函数
        set_error_handler('framework_error_handler',$debug);
    }

    private function setConfig()
    {
        $app=$this->app;
        $app->config= new Config($app);
        //下面的代码可以用Config类的方法完成，但这里重写可以提高速度，Config内的方法用于后期动态修改配置用。
        $configFile=$app->DIR.'/temp/Config/data.php';
        if($app->DEBUG && file_exists($configFile)) unlink($configFile);
        if(file_exists($configFile)){
            Config::$data=unserialize(file_get_contents($configFile))+Config::$data;
        }else{
            foreach(glob(dirname(Monkey\Monkey::$DIR) .'/Globe/Configs/*.ini.php', GLOB_NOESCAPE) as $data){
                require $data;
            }
            foreach(glob($app->DIR.'/Configs/*.ini.php', GLOB_NOESCAPE) as $data){
                require $data;
            }
            dir_check(dirname($configFile));
            file_put_contents($configFile,serialize(Config::$data),LOCK_EX);
        }
    }

    private function setEnvironment()
    {
        date_default_timezone_set(Config::get('timezone', 'PRC'));
        //去掉(还原)因为magic_quotes_gpc开启时加上的转义字符，当 magic_quotes_gpc (GPC, Get/Post/Cookie) 打开时，GPC所有的 ' (单引号), " (双引号), \ (反斜线) and 空字符会自动转为含有反斜线的转义字符。
        if(get_magic_quotes_gpc()){
            $_POST=stripslashes_deep($_POST);
            $_GET=stripslashes_deep($_GET);
            $_COOKIE=stripslashes_deep($_COOKIE);
        }
        //修复IIS服务器$_SERVER['DOCUMENT_ROOT']失效的情况
        if(!isset($_SERVER['DOCUMENT_ROOT'])){
            if(isset($_SERVER['SCRIPT_FILENAME'])){
                $_SERVER['DOCUMENT_ROOT']=substr($_SERVER['SCRIPT_FILENAME'], 0, 0 - strlen($_SERVER['PHP_SELF']));
            }
            elseif(isset($_SERVER['PATH_TRANSLATED'])){
                $_SERVER['DOCUMENT_ROOT']=substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0 - strlen($_SERVER['PHP_SELF']));
            }
        }
        //修复IIS的原始URI
        if(!isset($_SERVER['REQUEST_URI']) && isset($_SERVER['HTTP_X_REWRITE_URL'])){
            $_SERVER['REQUEST_URI']=$_SERVER['HTTP_X_REWRITE_URL'];
        }
        //修正DOCUMENT_ROOT末尾可能因为apache配置造成多余一个'/'。
        $_SERVER['DOCUMENT_ROOT']=dir_format($_SERVER['DOCUMENT_ROOT']);
    }

}
