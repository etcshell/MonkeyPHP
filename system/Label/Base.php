<?php
namespace Label;

/**
 * 基础标签\Label\Base
 * @package    Label
 * @author     HuangYi
 * @copyright  Copyright (c) 2011-07-01——2013-03-30
 * @license    New BSD License
 * @version    $Id: \Label\Base.php 版本号 2013-03-30 $
 */
class Base{
    public static function copyright($params=null){
        return 'Copyright By 2012 IDreamPHP';
    }
    public static function icp($params=null){
        return 'IDreamPHP 备案号：00000000';
    }

    /**
     *
     * @param null $p
     * @return string
     */
    public static function getResource($p){
        return o_skin_manager()
            ->getResourceForHtml($p['subDir'], $p['type'], $p['filename']);
    }
}
