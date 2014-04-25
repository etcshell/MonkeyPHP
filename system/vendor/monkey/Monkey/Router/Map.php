<?php
namespace Monkey\Router;

/**
 * 路由映射管理
 * @package Monkey\Router
 *
 */
class Map
{
    public
        /**
         * @var \Monkey\App\App
         */
        $app;
    private
        $map=array(),
        $map_file,
        $update=false
    ;

    /**
     * 构造方法注入
     * @param \Monkey\App\App $app
     * @param string $config 配置
     * @return mixed
     */
    public function __construct($app,$config)
    {
        $this->app=$app;
        $this->map_file=$app->DIR.($config['map_file'] ? $config['map_file'] : '/data/router.map.php');
        $this->map=include($this->map_file);
    }

    /**
     * 获取所有路由映射信息
     * @return array
     */
    public function getAllMap()
    {
        return $this->map;
    }

    /**
     * 获取一条路由映射信息
     * @param $pattern
     * @return string
     */
    public function get($pattern)
    {
        if($pattern=$this->find($pattern)) return $this->map[$pattern];
        else return '';
    }

    /**
     * 添加一条映射
     * @param $pattern
     *    空匹配：       get/  或  /   其中get可以省略，其它如post则不能省略，下同
     *    含路径匹配：    get/article/list   或  /article/list
     *    含变量匹配：    get/{zh|en}/blog/{i}:language:id  3表示路由节数（下同），zh|en表示可选值，i表示值类型（i为整数，s为字符串），language和id表示参数名（下同）
     *    含固定变量匹配： post/article/{year}/{month}/{s}:year:month:title   {year}和{month}表示已配置匹配变量。
     *    含正则匹配：    get/(zh|en)/blog/([1-9]\d*):language:id     每节括号内为正则表达示，括号不能嵌套，一个变量对应一对括号
     *    含变量、固定变量、正则匹配： get/{zh|en}/blog/{year}-([1-9]\d*):language:year:id   每节括号内为正则表达示，括号不能嵌套，一个变量对应一对括号
     * @param string $controller
     * @param string $action
     */
    public function add($pattern,$controller,$action)
    {
        $pattern[0]=='/' and $pattern='get'.$pattern;
        $pos=strpos($controller,'\\Controller\\');
        $pos!==false and $controller=substr($controller,$pos+12);
        $this->map[$pattern]=$controller.':'.$action;
        $this->update=true;
    }

    /**
     * 删除一条映射
     * @param $pattern
     */
    public function delete($pattern)
    {
        if($pattern=$this->find($pattern)){
            unset($this->map[$pattern]);
            $this->update=true;
        }
    }

    /**
     * 清空路由映射表
     */
    public function clear()
    {
        $this->map=null;
        $this->map=array();
        $this->update=true;
    }

    /**
     * 保存路由映射表
     */
    public function saveMap()
    {
        $content='<?php'.PHP_EOL.'return '.var_export($this->map, TRUE).' ;';
        file_put_contents($this->map_file,$content,LOCK_EX); //echo '<br/>保存扫描结果到缓存文件中...<br/>';
        $this->update=false;
    }

    public function destroy()
    {
        $this->update and $this->saveMap();
    }

    private function find($pattern)
    {
        $pattern[0]=='/' and $pattern='get'.$pattern;
        if(isset($this->map[$pattern])) return $pattern;
        $pattern=strstr($pattern,'/');
        if(isset($this->map[$pattern])) return $pattern;
        return '';
    }
}