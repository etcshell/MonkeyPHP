<?php
//权限管理：
/**
 * 权限组件提供者
 */
\Monkey\App\Config::setComponentProvider('permission',
    array(
        'default_provider'=>'default',
        'default'         =>'\Monkey\Permission\Permission',
    )
);
/**
 * 默认权限管理的专用配置
 */
\Monkey\App\Config::setComponentConfig('permission','default',
    array(

    )
);