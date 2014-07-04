<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 14-3-19
 * Time: 下午3:34
 */

namespace Test\Controller\Blog;

use Monkey\Controller;

/**
 * Blog
 * 这个类演示路由可以支持子目录，
 * 只需在路由映射中正确填写控制器的命名空间即可
 * 如'get/blog'=>'Blog\Blog:index',或 '/blog'=>'Blog\Blog:index',
 * 其中没有“Test\Controller\”，这个实际上是框架帮你自动加上了。
 * 因此“Blog\Blog:index”等价于“Test\Controller\Blog\Blog:index”。
 * @package Test\Controller\Blog
 */
class Blog extends Controller
{
    public function action_index()
    {
        $response=$this->getResponse();
        $response->writeLine('博客首页');
    }
} 