<?php
namespace Controller;

use Monkey\Controller\_Interface;

/**
 * index控制器\Controller\Index
 * @package    Controller
 * @author     HuangYi
 * @copyright  Copyright (c) 2011-07-01——2013-03-30
 * @license    New BSD License
 * @version    $Id: \Controller\Index.php 版本号 2013-03-30 $
 */
class Index implements _Interface
{
    private $module;
    public function __construct(){
        $this->module= o_module('Index');
    }
    public function bootup($action,$params){
        if($params=='' or $params=='index'){
            $this->before();
        }else{
            //其它action
        }
        $this->$action();
    }
    private function index(){
        $this->module->newTemplate()
            ->assign('title','模板标签解析例子')//一般标签赋值
            ->display('example.html');//显示模板
    }
    private function before(){
        //todo before
    }

}

//dump(router::get());
//dump('测试');
//dump($path);