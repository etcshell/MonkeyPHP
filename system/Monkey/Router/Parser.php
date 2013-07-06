<?php
namespace Monkey\Router;

use FrameworkException;

/**
 * 路由解析器\Monkey\Router\Parser
 * @package    \Monkey\Router
 * @author     HuangYi
 * @copyright  Copyright (c) 2011-07-01——2013-03-30
 * @license    New BSD License
 * @version    $Id: \Monkey\Router\Parser.php 版本号 2013-03-30 $
 */
class Parser
{
    /**
     * @var \Monkey\Monkey
     */
    private $oMonkey;

    /**
     * @var Router
     */
    private $oRouter;

    private
        $config,
        $parameters,
        $route,
        $suffix=null,
        $uriPrefix
    ;

    /**
     * 构造方法注入
     * @param \Monkey\Router\Router $router 路由器类
     * @param \Monkey\Monkey|null $monkey 依赖类
     * @param mixed|null $config 配置
     * @return mixed
     */
    public function __construct($router, $monkey,$config)
    {
        $path=$config['suffix'];
        $config['suffix']= $path ? explode( '|', $this->formatSuffix($path) ) : array();
        $this->oRouter=$router;
        $this->oMonkey=$monkey;
        $this->config=$config;
        $path='';
        if($config['search_mode']==='rewrite' or $config['search_mode']==='pathinfo'){
            $path=$monkey->getRequest()->getUrl();
            $path= $path ? $this->_getPath($path) : '';
        }
        else if(isset($_GET[$config['search_get']])) {
            $path=$_GET[$config['search_get']];
        }
        $this->suffix = $this->parseSuffixByPath($path);
        $this->route = $this->parseRouteByPath($path);
        $this->parameters = $path;
    }

    /**
     * 获取请求路由
     * @return array|null 存在时候为array；不存在则为空值。
     *
     * 虽然返回值为array，仅仅表示路由存在，不表示该路由启用或用户有权访问该路由。
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * 获取请求路径中的参数
     * @return string
     */
    public function getParametersOfPath()
    {
        return $this->parameters;
    }

    /**
     * 获取请求路径中的扩展名
     * @return null|string
     */
    public function getSuffix()
    {
        return $this->suffix;
    }

    /**
     * 解析请求扩展名
     * 主要用于手动解析制定路径的扩展名
     * @param string $path 路径
     * @return null|string 空：当路径中不包含配置中制定的扩展名时；字符串：配置中定义的扩展名的一种。
     *
     * 解析完毕后，路径中包含路由和参数部分
     */
    public function parseSuffixByPath(&$path)
    {
        if(empty($path))  {
            return null;
        }

        $temp=strrchr($path,'.');
        if($temp===false){
            return null;
        }

        $temp=$this->formatSuffix(substr($temp,1));
        if( in_array( $temp, $this->config['suffix']) ) {
            $path=substr($path,0,0-strlen($temp)-1);
            return $temp;
        }
        else {
            return null;
        }
    }

    /**
     * 解析路由
     * 主要用于手动解析制定路径包含的路由
     * 注意：手动解析路由前，请先对此路径解析扩展名（即保证路径中的合法扩展名已经去掉）
     * @param string &$path 不含扩展名的路径
     * @return array|null 正确返回路由时候为array；失败返回空值。
     *
     * 解析完毕后，路径参数中仅仅剩下参数部分
     */
    public function parseRouteByPath(&$path){
        $oStorager=$this->oRouter->Storager();
        $path=trim($path,'/');
        $route=null;

        //路径非空
        if($path){
            $spliter=$this->config['route_params_separator'];
            $temp=strstr($path,$spliter, true);
            //部分作为路由时，路径要去除路由部分。
            if($temp!==false and $route=$oStorager->get($temp)){
                $path=substr(strstr($path,$spliter), strlen($spliter));
            }
            //全部作为路由时，路径剩下空字符串。
            if($temp===false and $route=$oStorager->get($path)){
                $path='';
            }
        }

        //没有找到请求的路由时候，尝试默认路由
        if(empty($route) and $temp=$this->config['default']){
            $route=$oStorager->get($temp);
        }

        return $route;
    }

    /**
     * 组装路径
     * 路径由三部分组成（注意顺序）：
     * [路由名][参数][扩展名]
     * @param string $routeName 路由名
     * @param string $parameters 参数
     * @param string|null $suffix 路径扩展名，会自动效验它是否包含在配置里制定的扩展名中
     * @return string
     */
    public function packagePath($routeName,$parameters,$suffix=null)
    {
        if($routeName and $parameters){
            $p=$routeName.$this->config['route_params_separator'].$parameters;
        }
        else{
            $p=$routeName.$parameters;
        }

        if(!$suffix){
            return $p;
        }

        $suffix = $this->formatSuffix($suffix);
        return in_array($suffix, $this->config['suffix']) ? $p.'.'.$suffix : $p;
    }

    /**
     * 组装网址链接
     * 生成绝对链接有利于SEO
     * 网址中的路径由三部分组成（注意顺序）：
     * [路由名][参数][扩展名]
     * @param string $routeName 路由名
     * @param string $parameters 参数
     * @param string|null $suffix 路径扩展名，会自动效验它是否包含在配置里制定的扩展名中
     * @return string
     */
    public function packageURL($routeName,$parameters,$suffix=null)
    {
        if($this->uriPrefix!==null){
            $this->uriPrefix=$this->oMonkey->getRequest()->getUriPrefix() . FRONT_URL;
        }
        $path=$this->packagePath($routeName,$parameters,$suffix);
        $url= $this->uriPrefix;
        //rewrite（需服务器支持）、pathinfo（需服务器支持）、get（传统方式）
        switch($this->config['search_mode']){
            case 'rewrite':
                $url.='/'.$path;
                break;
            case 'pathinfo':
                $url.='/'. basename(FRONT_FILE) .'/'.$path;
                break;
            default ://get
                $url.='/'. basename(FRONT_FILE) .'?'.$this->config['search_get'].'='.$path;
        }
        return $url;
    }


    private function _getPath($url)
    {
        $temp=strstr($url,'?',true);
        $temp!==false and $url=$temp;
        FRONT_URL and $url=substr($url,strlen(FRONT_URL));
        $url=trim($url,'/');
        $url=preg_replace('/\/{2,}/','/',$url);

        $frontFile=basename(FRONT_FILE);

        if($url==$frontFile) {
            return '';
        }

        $temp=strstr($url,'/',true);

        if($temp==$frontFile){
            return substr(strstr($url,'/'),1);
        }
        else {
            return $url;
        }
    }

    private function formatSuffix($suffix)
    {
        return strtolower($suffix);
    }
}