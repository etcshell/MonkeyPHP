<?php

config_add(
    array(
        /**
         * 上传配置
         *
         */
        'upload'=>array(
            'dir'  =>dir_format(dirname(FRONT_FILE)).'/upload',
            'file_system_charset'   =>'GB2312',//一般中文Windows为GB2312，Linux为utf-8
        ),
    )
);