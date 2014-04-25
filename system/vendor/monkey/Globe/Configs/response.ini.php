<?php
/**
 * 响应组件提供者
 */
\Monkey\App\Config::setComponentProvider('response',
    array(
        'default_provider'=>'web',
        'web'     =>'\Monkey\Response\Response',
        'cli'     =>'\Monkey\Response\ResponseCli',
    )
);
/**
 * MonkeyPHP提供的默认web浏览器响应组件的配置
 */
\Monkey\App\Config::setComponentConfig('response','web',
    array(
        'charset'  =>'UTF-8',
    )
);
/**
 * MonkeyPHP提供的默认cli命令行响应组件的配置
 */
\Monkey\App\Config::setComponentConfig('response','cli',
    array(
        'charset'  =>'UTF-8',
    )
);