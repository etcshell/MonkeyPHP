<?php
namespace Test\LabelApi;

/**
 * 自定义标签示例
 */

class Base{
    public static function copyright($params=null){
        return 'Copyright By 2012 MonkeyPHP';
    }
    public static function icp($params=null){
        return 'MonkeyPHP 备案号：00000000';
    }
}
