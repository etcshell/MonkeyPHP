<?php
namespace Controller;

use Library\Upload;
use Monkey\Controller\_Interface;

/**
 * Example控制器\Controller\Example
 * @package    Controller
 * @author     HuangYi
 * @copyright  Copyright (c) 2011-07-01——2013-03-30
 * @license    New BSD License
 * @version    $Id: \Controller\Example.php 版本号 2013-03-30 $
 */
class Example implements _Interface
{
    public function bootup($action,$params){
        if(method_exists($this,$action)){
            $this->$action($params);
        }else{
            $this->hello();
        }
    }
    public function hello($params=null){
        !$params and $params='World';
        echo 'Hello ', $params, ' !';
    }
    public function upload($params=null){
        if($params=='receive'){
            $restzip=Upload::storeFile("uppath",array(SYSTEM.'/imageUPLOADimage', SYSTEM.'/zipUPLOADzip'),null,array("jpg|bmp|png","rar|zip"),'3M');
            dump($restzip);
        }else{
            $actionUrl= o_route_manager()->getURL().'/receive';
            include (config()->dir_module . '/Example/uploadui.php');
        }
    }
}