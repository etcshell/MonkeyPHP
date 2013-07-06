<?php

config_add(
    array(
        'router'=>array(

            //解析器配置
            'parser'    =>
                array(
                    //三个选择：rewrite（需服务器支持）、pathinfo（需服务器支持）、get（传统方式）
                    'search_mode'               =>'rewrite',
                    //get字段上的显式方法设置，如http://www.xxx.php?r=index
                    'search_get'                =>'r',
                    //默认路由
                    'default'                   =>'index',
                    //路由和参数之间的分割符号
                    //只能是一个英文字符
                    'route_params_separator'    =>'/',
                    //网址修饰符，使得网址看起来像静态网页，这对搜索引擎友好些
                    //如果你的应用中有可能用到多个扩展名，请用竖线隔开（如下）
                    'suffix'                    =>'html|text|json|xml',

                ),

            //存储器配置
            'storager'  =>
                //这里用文件存储路由表。当用其他方式存储路由表时，比如数据库，则这里要根据你的路由存储类做调整。
                array(
                    'data'  => APP_PATH.'/data/router.data.php',
                ),
        ),
    )
);