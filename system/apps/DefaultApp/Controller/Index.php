<?php
namespace DefaultApp\Controller;

use Monkey\Controller\Web;

/**
 * 控制器示例 Index
 */

class Index extends Web{

    public function action_index()
    {
        $param=$this->getRouteParameter();
        if(empty($param)){
            echo '--你好hello!--<br/>';
        }
        if($param['language']=='zh'){
            echo '--你好!--<br/>';
        }
        if($param['language']=='en'){
            echo '--hello!--<br/>';
        }
        echo date('Y-m-d H:i:s');

    }

    public function action_hello()
    {
        echo '测试hello!<br/>';
    }

}