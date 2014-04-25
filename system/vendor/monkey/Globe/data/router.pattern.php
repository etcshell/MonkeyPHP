<?php
//路由匹配表，独立出来可以使得多个路径匹配同一路由器，从而简化路径匹配表达式的设计
/**
 * route设计：

完全匹配（addStaticPattern）：静态匹配
全字符、无参数
get/
get/blog
get/article/list

普通参数匹配（addDynamicPattern）：动态匹配
get/{zh|en}:language
get/blog/{i}:id
get/{zh|en}/blog/{i}:language:id
post/blog/{i}:id
get/blog/{s}:title
post/blog/{s}:title
post/{zh|en}/blog/{s}:language:title
get/article/{s}:title
post/article/{year}/{month}/{s}:year:month:title

正则匹配（addRegularPattern）：动态匹配
get/blog/([1-9]\d*):id
get/(zh|en)/blog/([1-9]\d*):language:id

 */
/******** 匹配路径到路由器的映射表——简称路由匹配表（示例） ********/
return array(
    ///////////////////////////////////////////
    //静态匹配：
    'static'=>array(
        'get/'=>'index',//匹配为/，如 http://www.google.cn/  或 http://www.google.cn/index.php
        'get/blog'=>'blog',//匹配为/blog，如 http://www.google.cn/blog
        'get/article/list'=>'articleList',//匹配为/blog，如 http://www.google.cn/article/list
        //post建议多用静态匹配，因为post参数不必放在url匹配里，放在post数据中更好
        'put/article'=>'articlePut',
        'post/article'=>'articlePost',
    ),
    
    ///////////////////////////////////////////
    //动态匹配：
    //'get|post|put|delete|head'的一种，数字为匹配被/分割后的节数
    'get-1'=>array(
        //原形为：get/{zh|en}:language，下面是编译后的：//匹配如 http://www.google.cn/zh
        array(
            'prefix'    =>'/',
            'pattern'   =>'(zh|en)',
            'params'    =>'language',
            'router'    =>'index',
        ),
    ),
    'get-2'=>array(
        //原形为：get/blog/{i}:id，下面是编译后的：//匹配如 http://www.google.cn/blog/118
        array(
            'prefix'    =>'/blog/',
            'pattern'   =>'([0-9]\d*)',
            'params'    =>'id',
            'router'    =>'blogRead',
        ),
    ),

    'get-4'=>array(
        //匹配如 http://www.google.cn/article/2014/04/hello-world ， route为articleGet， 参数名为year、month、title
        //原形为：get/article/{year}/{month}/{s}:year:month:title，下面是编译后的：
        array(
            'prefix'    =>'/article/',
            'pattern'   =>'([1-9]\d{3})/(1[0,1,2]|[1-9])/([^\/]+)',
            'params'    =>'year:month:title',
            'router'    =>'articleGet',
        ),
    ),

    //一般都是get（因为post参数不必放在url里，放在post数据中更好）
);