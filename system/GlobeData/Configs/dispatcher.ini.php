<?php

config_add(
    array(
        //dispatcher配置
        'dispatcher'=>array(
            //

            'token_key'=>'mkk',//效验Session的token键名
            'token_seed'=>base64_encode('Monkey'),//效验Session的token特征值（种子）
            'prefix_key'=>APP_NAME,//Session键名前缀，保证每个应用的Session不会碰撞（尤其是使用Memcache作为存储介质时）。

            'expiry'=>1440,//session过期时间

            'driver'=>'file',

        ),
    )
);