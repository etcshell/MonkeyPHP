<?php
namespace Monkey\Controller;
use Monkey\_Interface\Component;

/**
 * 控制器管理器\Framework\ControllManager
 * @package    Framework
 * @author     HuangYi
 * @copyright  Copyright (c) 2011-07-01——2013-03-30
 * @license    New BSD License
 * @version    $Id: \Framework\ControllManager.php 版本号 2013-03-30 $
 */
class Manager implements Component
{
    private static $_controller_ini_file='/config/controller.ini.php';
    /**
     * @var \Monkey\Monkey
     */
    private $oMonkey;

    private
        $config
    ;

    private function __construct(){}

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
    }

    /**
     * 获取组件实例
     * @return \Monkey\Controller\Manager
     */
    public static function _instance()
    {
        static $self;
        !$self and $self=new self();
        return $self;
    }

    public function getController()
    {

    }

    /*
     * 获取控制器配置，开发时用controller.ini.php，发布后改到缓存中
     */
    public function get($file=null){
        static $controllers=false;
        if($file || $controllers===false){
            !$file and $file=SYSTEM.self::$_controller_ini_file;
            $controllers=include($file);
        }
        return $controllers;
    }
    public function save($list,$file=null){
        !$file and $file=SYSTEM.self::$_controller_ini_file;
        file_save_variable($file, $list);
    }



    /**
     * 分发路由
     */
    public function dispatch(){
        $this->fetchTable();
        $this->_parseRoute();
        $this->_startController(self::$_routerTable[self::$_router]);
    }
    /**
     * @param string $name 控制器名
     * @return \Monkey\Controller\_Interface object 控制器对象
     */
    public function controller($name){
        if(!import(SYSTEM.'/Controller/'.$name.'.php')){
            error_exit('控制器丢失！',1024,__FILE__,__LINE__);
        }
        $class=new \ReflectionClass('Controller\\'.$name);//建立反射类
        if(!$class->implementsInterface('Framework\\Controller\\_Interface'))
            error_exit('控制器不规范！',1024,__FILE__,__LINE__);
        return $class->newInstance();
    }
    private function _startController($router){
        if(empty($router) || !$router['enable'])o_error()->show404();
        if(!empty($router['role'])){
            if(!$this->_checkAuthority($router['role']))
                o_error()->show403();//无权访问
        }
        $control=$this->controller($router['controller']);//
        //if(!Plugin()->before($router['controller'],self::$_params)) return;
        $control->bootup($router['action'],self::$_params);
        //Plugin()->after($router['controller'],self::$_params);
    }
}