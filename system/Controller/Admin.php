<?php
namespace Controller;


use Monkey\Controller\_Interface;

class Admin implements _Interface
{

    public function bootup($action,$params)
    {
        $this->$action($params);
    }

    public function login($p=null)
    {
        if($p=='go'){
            echo '登录中……';
        }else{
            o_module('Admin')->newTemplate()
                ->assign('title','IDreamCMS管理员登录')
                ->display('login.html',false);
        }
    }


}