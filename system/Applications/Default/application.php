<?php

//定义应用名称
define('APP_NAME','foreground');
//定义后端路径
define('APP_PATH', strtr(__DIR__,DIRECTORY_SEPARATOR,'/') );
//定义调试环境
define('DEBUG',E_ALL ^ E_NOTICE ^ E_WARNING );
//启动程序
require __DIR__.'/../../Monkey/Bootstrap.php';

