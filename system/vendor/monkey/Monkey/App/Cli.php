<?php
namespace Monkey\App;

/**
 * Cli
 * 命令行应用基类
 * 所有命令行应用继承此类
 * @package Monkey\App
 */
class Cli extends App{
    public
        $isCli=true,
        $type='cli';

} 