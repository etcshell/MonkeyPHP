<?php

/******** 控制器配置，开发时用，发布后改到缓存中 ********/
return array(
    'index'=>array(
        'controller'=>'Index',
        'action'=>'index',
        'method'=>0,//代表'all|get|post|put|delete|head'的一种或几种的混合都可以，分别代表值 0,1,2,4,8,16
        'enable'=>true,
        'role'=>'',
        'token'=>true,
    ),
    'hello'=>array(
        'controller'=>'Example',
        'action'=>'hello',
        'method'=>1,//代表'all|get|post|put|delete|head'的一种或几种的混合都可以，分别代表值 0,1,2,4,8,16
        'enable'=>true,
        'role'=>'',
        'token'=>false,
    ),
    'upload'=>array(
        'controller'=>'Example',
        'action'=>'upload',
        'method'=>2,//代表'all|get|post|put|delete|head'的一种或几种的混合都可以，分别代表值 0,1,2,4,8,16
        'enable'=>true,
        'role'=>'',
        'token'=>false,
    ),
    'adminlogin'=>array(
        'controller'=>'Admin',
        'action'=>'login',
        'method'=>1,//代表'all|get|post|put|delete|head'的一种或几种的混合都可以，分别代表值 0,1,2,4,8,16
        'enable'=>true,
        'role'=>'',
        'token'=>true,
    ),
);