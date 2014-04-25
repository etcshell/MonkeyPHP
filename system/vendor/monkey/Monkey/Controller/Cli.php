<?php
namespace Monkey\Controller;

use Monkey;

/**
 * Cli
 * 命令行控制器基类
 * @package Monkey\Controller
 */
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