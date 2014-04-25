<?php
/**
 * 请求组件提供者
 */
\Monkey\App\Config::setComponentProvider('request',
    array(
        'default_provider'  =>'web',
        'web'      =>'\Monkey\Request\Request',
        'cli'      =>'\Monkey\Request\RequestCli',
    )
);
/**
 * MonkeyPHP提供的默认web浏览器请求组件的配置
 */
\Monkey\App\Config::setComponentConfig('request','web',array());
/**
 * MonkeyPHP提供的默认cli命令行请求组件的配置
 */
\Monkey\App\Config::setComponentConfig('request','cli',array());