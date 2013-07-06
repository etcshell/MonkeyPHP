<?php
class frame_install_ implements ControlInterface
{
    private $tpl;
    public function __construct(){

    }
    public function bootup($action,$path){
        if(!method_exists($this,$action))o_error()->show404();

        $this->$action($path);
    }
    private function index(){
        $this->tpl->assign('next_url',$this->_url('install/license'))->display('index.tpl.php');
    }
    private function license(){
        $this->tpl->assign('next_url',$this->_url('install/test'))->display('xieyi.tpl.php');
    }
    private function test(){
        $this->tpl->assign('next_url',$this->_url('install/database'))->display('test.tpl.php');
    }
    private function database(){
        $this->tpl->assign('next_url',$this->_url('install/setdb'))->display('database.tpl.php');
    }
    private function setdb(){
        $this->tpl->assign('next_url',$this->_url('install/doing'))->display('database.tpl.php');
    }
    private function _url($router){

    }
}
