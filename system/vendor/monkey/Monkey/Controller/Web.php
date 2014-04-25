<?php
namespace Monkey\Controller;

use Monkey;

/**
 * Web
 * Web控制器基类
 * @package Monkey\Controller
 */
class Web extends Controller{

    public
        /**
         * @var Monkey\Request\Request
         */
        $request,
        /**
         * @var \Monkey\Response\Response
         */
        $response
    ;
} 