<?php

config_add(
    array(
        //选择缓存：默认为File（无需安装），推荐Apc（需要安装），Memcache等更多缓存请见缓存类库目录
        'cache'=>array(
            //设置默认缓存
            'default_cache'  =>'file',
            //如果各个缓存中没有配置默认缓存时间，将使用这个值作为默认缓存时间
            'default_expire' =>3600,

            /*
             * 文件缓存的专用配置
             */
            'file'=>array(
                'expire'=>3600,//默认缓存时间
                'dir'           =>APP.'/temp/fileCache',//缓存文件的绝对路径，留空为 TEMP.'/filecache'
                'filename'      =>'data',
                'filesize'      =>'15M',
                'check'         =>false,
            ),

            /**
             * memcache缓存的专用配置
             */
            'memcache'=>array(
                'expire'=>3600,//默认缓存时间
                'host'          =>'localhost',
                'port'          =>11211,
                'persistent'    =>'',
                'compressed'    =>FALSE,
            ),

            /**
             * apc缓存的专用配置
             */
            'apc'=>array(
                'expire'=>3600,//默认缓存时间
            ),

            /**
             * xcache缓存的专用配置
             */
            'xcache'=>array(
                'expire'=>3600,//默认缓存时间
            ),

            /**
             * eaccelerator缓存的专用配置
             */
            'eaccelerator'=>array(
                'expire'=>3600,//默认缓存时间
            ),

        ),
    )
);