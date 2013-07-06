<?php

config_add(
    array(
        /**
         * 日志组件配置
         * 每个日志类型一个子配置
         */
        'logger'=>array(
            'error'=>array(
                'enable'    =>true,
                'dir'       =>APP.'/logs/error',
            ),
            'sql'=>array(
                'enable'    =>true,
                'dir'       =>APP.'/logs/sql',
            ),
        ),
    )
);