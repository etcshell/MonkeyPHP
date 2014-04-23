<?php
/**
 * shutdown配置，这里可集中注册要在shutdown时运行的回调程序
 * 这里注册有一个限制：不能注册“对象——方法”类型的回调程序
 *
 * 用法：
 * 1. 'myfunction'                  对应 $shutdown->registerCallback( 'myfunction' );
 * 2. 'myclass::staticmethod'       对应 $shutdown->registerCallback( 'myclass::staticmethod' );
 * 3. array('myclass', 'method')    对应 $shutdown->registerCallback( array('myclass', 'method') );
 * 4. 不支持“对象——方法”型             但程序中可以：$shutdown->registerCallback( array( $myObject, 'method') );
 *
 */
/**
 * shutdown组件提供者
 */
\Monkey\App\Config::setComponentProvider('shutdown',
    array(
        'default_provider'=>'default',
        'default'=>'\Monkey\Shutdown\Shutdown',
    )
);
/**
 * MonkeyPHP提供的默认shutdown组件的配置
 */
\Monkey\App\Config::setComponentConfig('shutdown','default',array());