<?php
namespace Label;

/**
 * 自定义标签示例\Label\Example
 * @package    Label
 * @author     HuangYi
 * @copyright  Copyright (c) 2011-07-01——2013-03-30
 * @license    New BSD License
 * @version    $Id: \Label\Example.php 版本号 2013-03-30 $
 */
class Example{
    public static function copyright($params=null){
        return 'Copyright By 2012 IDreamPHP';
    }
    public static function icp($params=null){
        return 'IDreamPHP 备案号：00000000';
    }
    public static function colorTitle($params=null){
        return array(
            array('0039b6', 'M'),
            array('c41200', 'o'),
            array('f3c518', 'n'),
            array('0039b6', 'k'),
            array('c41200', 'e'),
            array('f3c518', 'y'),
            array('30a72f', 'P'),
            array('c41200', 'H'),
            array('0039b6', 'P'),
        );
    }
    public static function menu($params=null){
        return array(
            array( o_request()->getRelativeUrlRoot().'/'.config()->front_filename, '首页'),
            array('#', '新闻'),
            array('#', '下载'),
            array('#', '文档'),
            array('#', '教程'),
            array('#', '问答'),
            array('#', '论坛'),
            array('#', '关于'),
        );
    }

    public static function getResource($p=null){
        return o_skin_manager()
            ->getResourceForHtml($p['subDir'], $p['type'], $p['filename']);
    }
}
