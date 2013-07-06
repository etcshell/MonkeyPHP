<?php
/**
 * Created by JetBrains PhpStorm.
 * User: hyiyou
 * Date: 13-5-7
 * Time: 下午8:30
 * To change this template use File | Settings | File Templates.
 */

namespace Monkey\Router;


/**
 * Class Storager
 * @package Monkey\Router
 *
 * 路由结构为数组：
 * 'name'
 *      =>array(
 *         'controller'=>'Example',//要运行的控制器名。
 *         'action'=>'hello',//要激活的方法
 *         'method'=>int,//代表'all|get|post|put|delete|head'的一种或几种的混合都可以，分别代表值 0,1,2,4,8,16。例如3代表1+2
 *         'enable'=>true,
 *         'role'=>'',//为空表示不需要指定角色，即任何人都可以访问
 *         'token'=>false,//表示不需要指定效验token
 *      ),
 */
class Storager
{

    /**
     * @var \Monkey\Monkey
     */
    private $oMonkey;

    private
        $config,
        $routes
    ;

    /**
     * 构造方法注入
     * @param \Monkey\Monkey|null $monkey 依赖类
     * @param mixed|null $config 配置
     * @return mixed
     */
    public function __construct($monkey,$config)
    {
        $this->oMonkey=$monkey;
        $this->config=$config;
        !$this->config['data'] and $this->config['data']= APP_PATH.'/data/router.data.php';
        $this->routes=include($this->config['data']);
        !$this->routes and $this->routes=array();
    }

    /**
     * 获取一个路由 或 所有路由
     * @param null $name
     * @return mixed
     */
    public function get($name=null)
    {
        return $name ? $this->routes[$name] : $this->routes;
    }

    /**
     * 设置一个路由
     * @param $name
     * @param $router
     */
    public function set($name,$router)
    {
        $this->routes[$name]=$router;
    }

    /**
     * 删除一个路由
     * @param $name
     */
    public function delete($name)
    {
        if(array_key_exists($name, $this->routes))
        {
            unset($this->routes[$name]);
        }
    }

    /**
     * 清空路由表
     */
    public function clear()
    {
        $this->routes=null;
        $this->routes=array();
    }

    /**
     * 保存路由表
     */
    public function save()
    {
        $content='<?php'.PHP_EOL.'return '.var_export($this->routes, TRUE).' ;';
        file_put_contents($this->config['data'],$content,LOCK_EX); //echo '<br/>保存扫描结果到缓存文件中...<br/>';
    }

}