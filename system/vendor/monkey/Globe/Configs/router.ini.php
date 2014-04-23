<?php
/**
 * 路由组件提供者
 */
\Monkey\App\Config::setComponentProvider('router',
    array(
        'default_provider'=>'default',
        'default'     =>'\Monkey\Router\Router',
    )
);
/**
 * MonkeyPHP提供的默认路由组件的配置
 */
\Monkey\App\Config::setComponentConfig('router','default',
    array(
        //路由存贮配置，相对应用目录。
        'map_file'           => '/data/router.map.php',//路由器到控制器的映射表
        'pattern_option'=>array(
            //路由匹配时的编译标签，简记名（只能用一对花括号括起来）=>正则表达式（只能用一对括号括起来）
            '{i}'       =>"(\d+)",
            '{s}'       =>"([^\/]+)",
            '{year}'    =>"([1-2]\d{3})",
            '{month}'   =>"(1[0,1,2]|[1-9])",
            '{day}'     =>"([1-9]|[1,2][0-9]|3[0,1])",
            '{name}'    =>"(\w+)",
            '{zh|en}'   =>"(zh|en)",
            '{json}'    =>"(\.json)",
        ),
        'router_class_auto_prefix'=>true,  //自动将router表中类名加上前缀 \AppName\Controller\

        //三个选择：rewrite（需服务器支持）、pathinfo（需服务器支持）、get（传统方式）
        'search_mode'               =>'rewrite',
        //get字段上的显式方法设置，如http://www.xxx.php?r=index
        'search_get'                =>'r',
    )
);