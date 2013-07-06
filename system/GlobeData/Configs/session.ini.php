<?php

config_add(
    array(
        //session配置
        //
        //为空表示不使用框架提供的session管理器
        'session'=>array(
            //选择session驱动
            //默认使用File（需要单独配置）、其次为Apc（不需要单独配置）、Memcache（需要单独配置）
            'driver'=>'file',
            //session过期时间
            'expiry'=>1440,
            //Session键名前缀，保证每个应用的Session不会碰撞（尤其是使用Memcache作为存储介质时）。
            'prefix_key'=>APP_NAME,

            //效验Session的token键名
            'token_key'=>'mkk',
            //效验Session的token特征值（种子）
            'token_seed'=>base64_encode('Monkey'),

            /*
             * 文件缓存的专用配置
             */
            'file'=>array(
                'expire'=>1440,//默认缓存时间
                'dir'           =>APP.'/temp/fileCache',//缓存文件的绝对路径，留空为 TEMP.'/filecache'
                'filename'      =>'session',
                'filesize'      =>'15M',
                'check'         =>false,
            ),

            /**
             * memcache缓存的专用配置
             */
            'memcache'=>array(
                'expire'=>1440,//默认缓存时间
                'host'          =>'localhost',
                'port'          =>11211,
                'persistent'    =>'',
                'compressed'    =>FALSE,
            ),
        ),
    )
);