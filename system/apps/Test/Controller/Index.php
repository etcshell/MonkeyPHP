<?php
namespace Test\Controller;

use Monkey\Controller\Web;

/**
 * 控制器示例 Index
 */

class Index extends Web{

    /**
     * index action示例，方面名前面必须加“action_”前缀，以标明这是浏览器路由访问的方法
     */
    public function action_index()
    {
        //演示使用响应对象向浏览器发送内容
        $response=$this->getResponse();
        //$response->setCache('test',60);
        $response->writeLine('测试 response::writeLine');
        $response->writeLine('');
        $param=$this->getRouteParameter();
        if(empty($param)){
            $response->writeLine('--你好hello!--');
        }
        if($param['language']=='zh'){
            $response->writeLine('--你好!--');
        }
        if($param['language']=='en'){
            $response->writeLine('--hello!--');
        }
        //ob_start();
        //ob_end_flush();
        //response()->HttpHeader()->setContentType();

        $response->writeLine(date('Y-m-d H:i:s'));

        //$data=db()->select('role')->where('rid',1)->execute(1)->fetch();

        //dump($data);

    }

    /**
     * hello测试
     * 方面名前面必须加“action_”前缀，以标明这是浏览器路由访问的方法
     */
    public function action_hello()
    {
        echo '测试hello!<br/>';
    }

}