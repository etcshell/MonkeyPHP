<?php

/******** 路由器到控制器的映射表——简称路由映射表（示例） ********/
//其中请求方法get可以省略，其它如post等则不能省略
return array(//静态路由
    'get/' => 'Index:index', //'Index:index'相当于请求\DefaultApp\Controller\Index类的action_index方法
    '/' => 'Index:index', //效果同上

    'get/hello' => 'Index:hello', '/hello' => 'Index:hello', //效果同上

    '/blog' => 'Blog\Blog:index', //控制器支持子命名空间

    //动态标识路由，使用了路由组件配置中的编译标签
    '/{zh|en}:language' => 'Index:index', //{zh|en}的匹配结果将作为参数名language的值
    '/blog/{s}:title' => 'Blog:get', //{s}的匹配结果将作为参数名title的值
    'post/article/{year}/{month}/{s}:year:month:title' => 'Article:modify', //这里有三个参数

    //动态正则路由，路由中直接使用正则表达式，但有个限制：不能嵌套括号！
    '/article/([1-9]\d{3})/(1[0,1,2]|[1-9])/([^\/]+):year:month:title' => 'Article:get',);