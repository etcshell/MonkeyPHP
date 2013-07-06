<?php
/*
 * 整个MKCMS的唯一入口文件
 */
//定义前端路径
define('FRONT_FILE', strtr(__FILE__,DIRECTORY_SEPARATOR,'/') );
//将用户请求交给启动程序。
require(dirname(FRONT_FILE).'/../system/Applications/Default/Application.php');