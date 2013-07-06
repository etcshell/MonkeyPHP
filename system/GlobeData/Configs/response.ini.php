<?php

config_add(
    array(
        'response'     =>array(
            /**
             * 启用gzip：
             * 只有在不能控制服务器时才开启；
             * 如果能在服务器中开启，则尽量在服务器中开启，
             * 因为服务器中开启可以压缩发送文本和所有的资源文件，
             * 而在这里开启不能压缩资源文件。
             */
            'gzip_enable'  =>true,
            'gzip_level'    =>6,
            /**
             * 响应的字符集
             */
            'charset'  =>'UTF-8',
            /**
             * 默认的响应自然语言
             */
            'language_default'  =>'zh-cn',
        ),
    )
);