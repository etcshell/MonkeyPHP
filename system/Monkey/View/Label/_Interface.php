<?php
namespace Monkey\Label;

/**
 * 标签方案接口\Framework\Label\_Interface
 * @package    Framework\Label
 * @author     HuangYi
 * @copyright  Copyright (c) 2011-07-01——2013-03-30
 * @license    New BSD License
 * @version    $Id:\Framework\Label\_Interface.php 版本号 2013-03-30 $
 */
interface _Interface
{
    /**
     * 直接替换型标签
     * @return array
     */
    public static function replaceTag();
    /**
     * 回调替换型标签
     * @return array
     */
    public static function callbackTag();
}
