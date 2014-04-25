<?php
/**
 * 日志组件提供者
 */
\Monkey\App\Config::setComponentProvider('logger',
    array(
        'default_provider'=>'default',
        'default'=>'\Monkey\Logger\Logger',
    )
);
/**
 * MonkeyPHP提供的默认日志组件的配置
 */
\Monkey\App\Config::setComponentConfig('logger','default',
    array(
        //一般错误日志
        'error_enable'    =>true,
        'error_dir'       =>'/logs/error',//表示当前应用目录下的 /logs/error 子目录中
        //sql错误日志
        'sql_enable'    =>true,
        'sql_dir'       =>'/logs/sql',//表示当前应用目录下的 /logs/sql 子目录中
    )
);