<?php
\Monkey\App\Config::setComponentProvider('session',
    array(
        'default_provider'=>'file',
        'file'          =>'\Monkey\Session\File',
        'memcache'      =>'\Monkey\Session\Memcache',
        'apc'           =>'\Monkey\Session\Apc',
    )
);
/**
 * MonkeyPHP提供的默认文件缓存的专用配置
 */
\Monkey\App\Config::setComponentConfig('session','file',
    array(
        //Session键名前缀，保证每个应用的Session不会碰撞（尤其是使用Memcache作为存储介质时）。
        'prefix'=>'Monkey',
        'expire'=>1440,//默认缓存时间
        'dir'           =>'/sessionCache',//表示当前应用临时缓存目录下的 '/sessionCache' 子目录中
        'filename'      =>'session',
        'filesize'      =>'15M',
        'check'         =>false,
    )
);
/**
 * MonkeyPHP提供的默认memcache缓存的专用配置
 */
\Monkey\App\Config::setComponentConfig('session','memcache',
    array(
        //Session键名前缀，保证每个应用的Session不会碰撞（尤其是使用Memcache作为存储介质时）。
        'prefix'=>'Monkey',
        'expire'=>1440,//默认缓存时间
        'host'          =>'localhost',
        'port'          =>11211,
        'persistent'    =>'',
        'compressed'    =>FALSE,
    )
);
/**
 * MonkeyPHP提供的默认apc缓存的专用配置
 */
\Monkey\App\Config::setComponentConfig('session','apc',
    array(
        //Session键名前缀，保证每个应用的Session不会碰撞（尤其是使用Memcache作为存储介质时）。
        'prefix'=>'Monkey',
        'expire'=>1440,//默认缓存时间
    )
);
