<?php
//选择缓存：默认为File（无需安装），推荐Apc（需要安装），Memcache等更多缓存请见缓存类库目录
/**
 * 缓存组件提供者
 */
\Monkey\App\Config::setComponentProvider('cache',
    array(
        'default_provider'=>'file',
        'file'          =>'\Monkey\Cache\File',
        'memcache'      =>'\Monkey\Cache\Memcache',
        'apc'           =>'\Monkey\Cache\Apc',
        'xcache'        =>'\Monkey\Cache\Xcache',
        'eaccelerator'  =>'\Monkey\Cache\Eaccelerator',
    )
);
/**
 * 文件缓存的专用配置
 */
\Monkey\App\Config::setComponentConfig('cache','file',
    array(
        'expire'=>3600,//默认缓存时间
        'dir'           =>'/fileCache',//缓存文件的相对路径（相对临时缓存目录），留空为/filecache
        'filename'      =>'data',
        'filesize'      =>'15M',
        'check'         =>false,
    )
);
/**
 * memcache缓存的专用配置
 */
\Monkey\App\Config::setComponentConfig('cache','memcache',
    array(
        'expire'=>3600,//默认缓存时间
        'host'          =>'localhost',
        'port'          =>11211,
        'persistent'    =>'',
        'compressed'    =>FALSE,
    )
);
/**
 * apc缓存的专用配置
 */
\Monkey\App\Config::setComponentConfig('cache','apc',
    array(
        'expire'=>3600,//默认缓存时间
    )
);
/**
 * xcache缓存的专用配置
 */
\Monkey\App\Config::setComponentConfig('cache','xcache',
    array(
        'expire'=>3600,//默认缓存时间
    )
);
/**
 * eaccelerator缓存的专用配置
 */
\Monkey\App\Config::setComponentConfig('cache','eaccelerator',
    array(
        'expire'=>3600,//默认缓存时间
    )
);

