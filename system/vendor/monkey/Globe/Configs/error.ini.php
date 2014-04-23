<?php
/**
 * 错误报告组件提供者
 */
\Monkey\App\Config::setComponentProvider('errorReporting',
    array(
        'default_provider'=>'default',
        'default'=>'\Monkey\ErrorReporting\ErrorReporting',
    )
);
/**
 * MonkeyPHP提供的默认错误报告组件的配置
 */
\Monkey\App\Config::setComponentConfig('errorReporting','default',
    array(
        //错误提示页模板目录，内部必须有4个文件，见框架自带错误提示页模板；
        //留空，即使用MonkeyPHP自带的错误提示页模板
        //如果填写，比如'/ErrorTemplate'表示当前应用目录下的 ErrorTemplate 子目录中
        'errorTemplate'     =>'',
    )
);