<?php
/*
 * 前端界面的入口
 */
//启动自动加载
require(__DIR__.'/../../system/vendor/autoload.php');
//建立应用,参数1：应用名；参数2：静态资源目录。
$app= \Monkey\Monkey::createApp('Test',strtr(__DIR__,DIRECTORY_SEPARATOR,'/'));
//运行应用
$app->run();