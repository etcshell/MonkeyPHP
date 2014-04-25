<?php
namespace Monkey\Router;

/**
 * Router
 * 路由组件
 * @package Monkey\Router
 */
class Router
{
    public
        /**
         * @var \Monkey\App\App
         */
        $app,
        /**
         * @var Map
         */
        $map,
        /**
         * @var Pattern
         */
        $pattern
    ;

    private
        $config,
        $path,
        $router,
        $params,
        $root,
        $resource
    ;
    /**
     * @param \Monkey\App\App $app
     */
    public function __construct($app)
    {
        $this->app=$app;
        $config= $app->config()->getComponentConfig('router','default');
        $this->config= $config;
        $this->root= $app->FRONT_ROOT_URL;
        $this->resource= $this->root;
        $this->map= new Map($app, $config);
        $this->pattern= new Pattern($app, $config);
        $this->loadPath();
        $match= $this->pattern->matchPath($this->app->request()->getMethod(),$this->path);
        $this->params= $match['params'];
        $router=array();
        if($match['router_name']){
            $config['router_class_auto_prefix'] and $match['router_name']='\\'.$this->app->NAME.'\\Controller\\'.$match['router_name'];
            list($router['controller'],$router['action'])= explode(':',$match['router_name'],2);
        }
        $this->router= $router;
    }

    /**
     * 获取请求路径
     * @return string。
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * 获取请求路由
     * @return array|null 存在时候为array；不存在则为空值。
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * 获取请求路径中的参数
     * @return array
     */
    public function getParameters()
    {
        return $this->params;
    }

    /**
     * 还原路径
     * @param string $pattern 路径模式  get/test/abc-{zh|en}/blog/{year}-([1-9]\d*):language:year:id
     * 其中请求方法get是可选的 如 /test/abc-{zh|en}/blog/{year}-([1-9]\d*):language:year:id
     * @param string $parameters 参数  array('language'=>'en','year'=>'2014','id'=4025
     * @return string  /test/abc-en/blog/2014-4025
     */
    public function packagePath($pattern,$parameters=null)
    {
        return $this->pattern->packagePath($pattern,$parameters);
    }

    /**
     * 组装相对网址链接
     * @param string $pattern 路径模式  get/test/abc-{zh|en}/blog/{year}-([1-9]\d*):language:year:id
     * 其中请求方法get是可选的 如 /test/abc-{zh|en}/blog/{year}-([1-9]\d*):language:year:id
     * @param string $parameters 参数  array('language'=>'en','year'=>'2014','id'=4025
     * @return string  /.../test/abc-en/blog/2014-4025
     */
    public function packageRelativelyURL($pattern,$parameters=null)
    {
        if($parameters or strpos($pattern,':')!==false){
            return $pattern=$this->pattern->packagePath($pattern,$parameters);
        }
        $url= $this->root;
        //rewrite（需服务器支持）、pathinfo（需服务器支持）、get（传统方式）
        switch($this->config['search_mode']){
            case 'rewrite':
                $url.='/'.$pattern;
                break;
            case 'pathinfo':
                $url.='/'.basename($_SERVER['SCRIPT_NAME']).'/'.$pattern;
                break;
            default ://get
                $url.='/'.basename($_SERVER['SCRIPT_NAME']).'?'.$this->config['search_get'].'='.$pattern;
        }
        return $url;
    }

    /**
     * 组装相对网址链接
     * @param string $pattern 路径模式  get/test/abc-{zh|en}/blog/{year}-([1-9]\d*):language:year:id
     * 其中请求方法get是可选的 如 /test/abc-{zh|en}/blog/{year}-([1-9]\d*):language:year:id
     * @param string $parameters 参数  array('language'=>'en','year'=>'2014','id'=4025
     * @param bool $forceHttps 是否强制使用https协议， false时将使用请求时的协议。
     * @return string  http://www.host.com/.../test/abc-en/blog/2014-4025
     */
    public function packageAbsolutelyURL($pattern,$parameters=null,$forceHttps=false)
    {
        $uri=$this->packageRelativelyURL($pattern,$parameters);
        $uri=$this->app->request()->getUriPrefix().$uri;
        $forceHttps and $uri='https'.strstr($uri,':');
        return $uri;
    }

    private function loadPath()
    {
        $config=$this->config;
        if($this->app->type=='cli'){
            $this->path= $_SERVER["argv"][1];
        }
        else if($config['search_mode']==='rewrite' or $config['search_mode']==='pathinfo'){
            $this->path= $_SERVER['REQUEST_URI'] ? $this->_getParams($_SERVER['REQUEST_URI']) : '';
        }
        else if(isset($_GET[$config['search_get']])) {
            $this->path= $_GET[$config['search_get']];
        }
        else{
            $this->path='';
        }
    }

    private function _getParams($url)
    {
        $temp=strstr($url,'?',true);
        $temp!==false and $url=$temp;
        //默认认为REQUEST_URI包含了子目录，那么就要去除网址偏移量
        $this->root and $url=substr($url,strlen($this->root));
        $url='/'.trim($url,'/');
        $frontFile=basename($_SERVER['SCRIPT_NAME']);
        strrchr($url,'/') == '/'.$frontFile and $url=substr($url, 0, 0-strlen($frontFile)-1);
        return $url;
    }
}