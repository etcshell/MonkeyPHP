<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 14-3-8
 * Time: 下午8:39
 */

namespace Monkey\Controller;

use Monkey;
class Cli extends Controller{

    public
        /**
         * @var Monkey\Request\RequestCli
         */
        $request,
        /**
         * @var \Monkey\Response\ResponseCli
         */
        $response
    ;

    /**
     * @return Monkey\Request\RequestCli
     */
    protected function getRequest()
    {
        return $this->request;
    }

    /**
     * @return Monkey\Response\ResponseCli
     */
    protected function getResponse()
    {
        return $this->response;
    }
}