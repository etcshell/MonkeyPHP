<?php
namespace Test\Controller;

use Monkey\Controller;

/**
 * 控制器示例 Index
 */

class Index extends Controller
{

    /**
     * index action示例，方面名前面必须加“action_”前缀，以标明这是浏览器路由访问的方法
     */
    public function action_index()
    {
        $res = $this->response;
        //演示使用响应对象向浏览器发送内容
        $res->writeLine('测试 response::writeLine');
        $res->writeLine('');
        $param=$this->getRouteParameter();
        if(empty($param)){
            $res->writeLine('--你好hello!--');
        }
        if($param['language']=='zh'){
            $res->writeLine('--你好!--');
        }
        if($param['language']=='en'){
            $res->writeLine('--hello!--');
        }
        $res->writeLine(date('Y-m-d H:i:s'));
    }

    /**
     * hello测试
     * 方面名前面必须加“action_”前缀，以标明这是浏览器路由访问的方法
     */
    public function action_hello()
    {
        $this->response->writeLine('测试hello!');
    }

}