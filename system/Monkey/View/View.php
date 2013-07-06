<?php
namespace Monkey;

/**
 * 视图管理器类\Framework\ViewManager
 * @package    Framework
 * @author     HuangYi
 * @copyright  Copyright (c) 2011-07-01——2013-03-30
 * @license    New BSD License
 * @version    $Id: \Framework\ViewManager.php 版本号 2013-03-30 $
 */

class View
{
    private $_allStyles;
    private $_defaultStyle;
    private $_userStyle;

    private static $_cookieKeyOfStyle='FrameworkStyle';
    private static $_instance;

    private function __construct()
    {
        $this->_allStyles= config()->view_style_all;
        $this->_defaultStyle= config()->view_style_default;
        $this->_userStyle=$this->_getUserStyle();
    }

    /**
     * 获取视图管理器单例
     * @return \Monkey\View
     */
    public static function _instance()
    {
        !self::$_instance and self::$_instance=new self();
        return self::$_instance;
    }

    /**
     * 获取所有模板样式名列表
     * @return string 结果等价于oApp()->allStyleSet;
     */
    public function getAllStyles()
    {
        return $this->_allStyles;
    }

    /**
     * 获取默认的模板样式名
     * @return string 结果等价于oApp()->defaultStyleSet;
     */
    public function getDefaultStyle()
    {
        return $this->_defaultStyle;
    }

    /**
     * 返回当前用户选择的模板样式
     * @return string
     */
    public function getUserStyle()
    {
        return $this->_userStyle;
    }
    /**
     * 获取当前用户选择的模板样式
     * @return string
     */
    private function _getUserStyle()
    {
        if(isset($_COOKIE[self::$_cookieKeyOfStyle]))
            return $_COOKIE[self::$_cookieKeyOfStyle];
        else
            return $this->_defaultStyle;
    }

    /**
     * 设置用户选择的模板样式
     * @param string $theme 模板样式名
     * @return \Monkey\View
     */
    public function setUserStyle($theme)
    {
        $this->_userStyle=$theme;
        setcookie(self::$_cookieKeyOfStyle,$theme,TIME+60*60*24*30);
        return $this;
    }


    /**
     * 获取样式模板文件
     * @param string $moduleName 模块名
     * @param string $templateFile 模板名（含扩展名）
     * @return string 优先返回当前模板样式中的模板文件，失败后返回默认模板样式中的模板文件，最后失败了则返回空字符串。
     */
    public function getTemplateOfStyle($moduleName, $templateFile)
    {
        $dir=SYSTEM.'/View/Style/';
        $file='/'.$moduleName.'_'.$templateFile;
        if( file_exists($dir.$this->_userStyle.$file) )
            return $dir.$this->_userStyle.$file;
        elseif( file_exists($dir.$this->_defaultStyle.$file) )
            return $dir.$this->_defaultStyle.$file;
        else
            return '';
    }

    /**
     * 获取公共模板文件
     * @param string $templateFile 模板名（含扩展名）
     * @return string 失败了则返回空字符串。
     */
    public function getTemplateOfCommon($templateFile){
        $file=SYSTEM.'/View/common/'.$templateFile;
        if( file_exists($file) )
            return $file;
        else
            return '';
    }

    /**
     * 安装样式模板文件
     * @param string $moduleName 模块名
     * @param string $sourceFile 源文件
     * @param string $style  模板样式名
     * @return bool
     */
    public function installTemplateOfStyle($moduleName, $sourceFile, $style)
    {
        $file = pathinfo($sourceFile);
        $saveFile=SYSTEM.'/View/Style/'.$style.'/'.$moduleName.'_'.$file['basename'];
        if(file_exists($saveFile))return false;
        return copy($sourceFile,$saveFile);
    }

    /**
     * 卸载样式模板文件
     * @param string $moduleName 模块名
     * @param string $file 目标文件名
     * @param string $style  模板样式名
     * @return bool
     */
    public function uninstallTemplateOfStyle($moduleName, $file, $style)
    {
        $file = pathinfo($file);
        $saveFile=SYSTEM.'/View/Style/'.$style.'/'.$moduleName.'_'.$file['basename'];
        if(!file_exists($saveFile))return true;
        return unlink($saveFile);
    }

    /**
     * 安装样式模板文件
     * @param string $sourceFile 源文件
     * @return bool
     */
    public function installTemplateOfCommon($sourceFile)
    {
        $file = pathinfo($sourceFile);
        $saveFile=SYSTEM.'/View/common/'.$file['basename'];
        if(file_exists($saveFile))return false;
        return copy($sourceFile,$saveFile);
    }

    /**
     * 卸载样式模板文件
     * @param string $file 目标文件名
     * @return bool
     */
    public function uninstallTemplateOfCommon($file)
    {
        $file = pathinfo($file);
        $saveFile=SYSTEM.'/View/common/'.$file['basename'];
        if(!file_exists($saveFile))return true;
        return unlink($saveFile);
    }

}