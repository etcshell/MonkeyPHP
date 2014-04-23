<?php
/**
 * cookie组件提供者
 */
\Monkey\App\Config::setComponentProvider('cookie',
    array(
        'default_provider'=>'default',
        'default'=>'\Monkey\Cookie\Cookie',
    )
);
/**
 * MonkeyPHP提供的默认cookie组件配置
 */
\Monkey\App\Config::setComponentConfig('cookie','default',
    array(
        'prefix'     =>'mk_',
        'defExpire'     =>2592000,//30天
        'defDomain'     =>'',
    )
);