<?php
namespace Monkey;

/**
 * 皮肤管理器类\Framework\SkinManager
 * @package    Framework
 * @author     HuangYi
 * @copyright  Copyright (c) 2011-07-01——2013-03-30
 * @license    New BSD License
 * @version    $Id: \Framework\SkinManager.php 版本号 2013-03-30 $
 */

class Skin
{
    private $_allSkins;
    private $_defaultSkin;
    private $_userSkin;

    private static $_cookieKeyOfSkin='FrameworkSkin';
    private static $_instance;

    private function __construct()
    {
        $this->_allSkins= config()->view_skin_all;
        $this->_defaultSkin= config()->view_skin_default;
        $this->_userSkin=$this->_getUserSkin();
    }

    /**
     * 获取皮肤管理器单例
     * @return \Monkey\Skin
     */
    public static function _instance()
    {
        !self::$_instance and self::$_instance=new self();
        return self::$_instance;
    }

    /**
     * 获取所有皮肤名列表
     * @return string 结果等价于oApp()->allSkinsSet;
     */
    public function getAllSkins()
    {
        return $this->_allSkins;
    }

    /**
     * 获取默认的皮肤名
     * @return string 结果等价于oApp()->defaultSkinSet;
     */
    public function getDefaultSkin()
    {
        return $this->_defaultSkin;
    }

    /**
     * 返回当前用户选择的皮肤
     * @return string
     */
    public function getUserSkin()
    {
        return $this->_userSkin;
    }
    /**
     * 获取当前用户选择的皮肤
     * @return string
     */
    private function _getUserSkin()
    {
        if(isset($_COOKIE[self::$_cookieKeyOfSkin]))
            return $_COOKIE[self::$_cookieKeyOfSkin];
        else
            return $this->_defaultSkin;
    }

    /**
     * 设置用户选择的皮肤
     * @param string $skin 皮肤名
     * @return \Monkey\Skin
     */
    public function setUserSkin($skin)
    {
        $this->_userSkin=$skin;
        setcookie(self::$_cookieKeyOfSkin,$skin,TIME+60*60*24*30);
        return $this;
    }


    /**
     * 获取用于Html的资源文件
     * @param string $subDir= common | front | admin 分别代表公共、前台、后台
     * @param string $type= js | css | image
     * @param string $filename 文件名，含扩展名
     * @return string 优先返回当前皮肤中的资源路径，失败后返回默认皮肤中的资源路径，最后失败了则返回空字符串。
     */
    public function getResourceForHtml($subDir, $type, $filename)
    {
        $sub='/'.$subDir;
        $file='/'.$type.'/'.$filename;
        $statics=config()->dir_statics;
        $_statics=o_request()->getRelativeUrlRoot().'/statics';
        if($type=='js' || $subDir=='common' ){
            if(file_exists($statics.$sub.$file))return $_statics.$sub.$file;
        }else{
            if(file_exists($statics.$sub.'/'.$this->_userSkin.$file))
                return $_statics.$sub.'/'.$this->_userSkin.$file;
            if(file_exists($statics.$sub.'/default'.$file))
                return $_statics.$sub.'/default'.$file;
        }
        return '';
    }

    /**
     * 构造资源文件的路径
     * @param string $subDir= common | front | admin 分别代表公共、前台、后台
     * @param string $type= js | css | image
     * @param string $filename 文件名，含扩展名
     * @param string $skin  皮肤名
     * @param bool $isForHtml=false 生成路径是否用于Html
     * @return string
     */
    public function buildPathOfResource($subDir, $type, $filename, $skin, $isForHtml=false)
    {
        $dir=$this->getDirOfResource($subDir,$type,$skin,$isForHtml);
        $pathinfo = pathinfo($filename);
        return $dir.'/'.$pathinfo['basename'];
    }

    /**
     * 获取资源文件目录
     * @param string $subDir= common | front | admin 分别代表公共、前台、后台
     * @param string $type= js | css | image
     * @param string $skin=null  皮肤名，默认为当前皮肤名
     * @param bool $isForHtml=true 生成路径是否用于Html
     * @return string
     */
    public function getDirOfResource($subDir, $type, $skin=null, $isForHtml=true)
    {
        if($type=='js' || $subDir=='common' ) $skin='';
        elseif(!$skin) $skin='/'.$this->_userSkin;
        else $skin='/'.$skin;
        if($isForHtml)return o_request()->getRelativeUrlRoot().'/statics/'.$subDir.$skin.'/'.$type;
        else return config()->dir_statics.'/'.$subDir.$skin.'/'.$type;
    }

    /**
     * 安装资源文件
     * @param string $subDir= common | front | admin 分别代表公共、前台、后台
     * @param string $type= js | css | image
     * @param string $sourceFile 源文件
     * @param string $skin=null  皮肤名
     * @return bool
     */
    public function installResource($subDir, $type, $sourceFile, $skin)
    {
        $saveFile=$this->buildPathOfResource($subDir,$type,$sourceFile,$skin,false);
        if(file_exists($saveFile))return false;
        return copy($sourceFile,$saveFile);
    }

    /**
     * 卸载资源文件
     * @param string $subDir= common | front | admin 分别代表公共、前台、后台
     * @param string $type= js | css | image
     * @param string $file 目标文件名
     * @param string $skin=null  皮肤名
     * @return bool
     */
    public function uninstallResource($subDir, $type, $file, $skin)
    {
        $saveFile=$this->buildPathOfResource($subDir,$type,$file,$skin,false);
        if(!file_exists($saveFile))return true;
        return unlink($saveFile);
    }

}