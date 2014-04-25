<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 14-3-19
 * Time: 下午3:34
 */

namespace Test\Controller\Blog;

use Monkey\Controller\Web;

class Blog extends Web
{
    public function action_index()
    {
        $response=$this->getResponse();
        $response->writeLine('博客首页');
    }
} 