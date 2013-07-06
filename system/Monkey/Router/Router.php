<?php
/**
 * Created by JetBrains PhpStorm.
 * User: hyiyou
 * Date: 13-5-7
 * Time: 下午8:30
 * To change this template use File | Settings | File Templates.
 */

namespace Monkey\Router;

use Monkey\_Interface\Component;

class Router implements Component
{
    /**
     * @var \Monkey\Monkey
     */
    private $oMonkey;

    /**
     * @var Storager
     */
    private $oStorager;

    /**
     * @var Parser
     */
    private $oParser;
    private
        $config
    ;
    /**
     * 构造函数
     */
    private function __construct(){ }

    /**
     * 组件初始化注入
     * @param \Monkey\Monkey|null $monkey 依赖类
     * @param mixed|null $config 配置
     * @return mixed
     */
    public function _initialize($monkey = null, $config = null)
    {
        $this->oMonkey=$monkey;
        $this->config=$config;
        $this->oStorager= new Storager($monkey, $config['storager']);
        $this->oParser= new Parser($this, $monkey, $config['parser']);
    }

    /**
     * 获取Router实例
     * @return \Monkey\Router\Router
     */
    public static function _instance(){
        static $self;
        !$self and $self=new self();
        return $self;
    }

    /**
     * 获取路由存储器
     * @return \Monkey\Router\Storager
     */
    public function Storager()
    {
        return $this->oStorager;
    }

    /**
     * @return \Monkey\Router\Parser
     */
    public function Parser()
    {
        return $this->oParser;
    }

    /**
     * 获取请求路由
     * @return array|null 存在时候为array；不存在则为空值。
     *
     * 虽然返回值为array，仅仅表示路由存在，不表示该路由启用或用户有权访问该路由。
     */
    public function getRoute()
    {
        return $this->oParser->getRoute();
    }

    /**
     * 获取请求路径中的参数
     * @return string
     */
    public function getParametersOfPath()
    {
        return $this->oParser->getParametersOfPath();
    }

    /**
     * 获取请求路径中的扩展名
     * @return null|string
     */
    public function getSuffix()
    {
        return $this->oParser->getSuffix();
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
        return $this->oParser->packageURL($routeName,$parameters,$suffix);
    }

}