<?php
namespace Library;

use Monkey;
/**
 * html
 * @category   html伪静态缓存
 * @package    扩展库
 * @author     HuangYi
 * @copyright  Copyright (c) 2012-4-1——至今
 * @license    New BSD License
 * @version    $Id: html.class.php 版本号 2013-1-1  $
 *
 */
final class Html{
    /**
     * 直接缓存html源代码
     */
    private function __construct() {}
    /**
     * @static
     * 设置html缓存
     * @param string $html_content //html内容
     * @param int $expire 缓存时间，默认保存时间为0（永久保存），24小时为86400*1
     * @param string|null $filename 缓存html文件名
     * @return bool
     */
    public static function store($html_content,$expire=0,$filename=null){
        !$filename and $filename=  self::getFilename();
        $path= pathinfo($filename);
        dir_check($path['dirname']);
        file_put_contents($filename, $html_content, LOCK_EX);
        if($expire!=0){
            $expire='<?php'.PHP_EOL.'return '.($expire+ TIME).' ;';
            $filename=self::_expire_filename($filename);
            file_put_contents($filename, $expire, LOCK_EX);
        }
        return true;
    }
    /**
     * 读取html缓存
     * @param string $filename  缓存html文件名
     * @param &mixed $result    要保存的结果地址
     * @return bool             成功返回true，失败返回false
     */
    public static function fetch($filename=null){
        !$filename and $filename=  self::getFilename();
        if(self::_exists($filename)){
            require $filename;
            return TRUE;
        }  else {
            return FALSE;
        }
    }
    /**
     * @static
     * 删除某个html缓存单元
     * @param string $filename  缓存html文件名
     * @return bool             成功返回true，失败返回false
     */
    public static function delete($filename=null){
        !$filename and $filename=  self::getFilename();
        return self::_delete($filename);
    }
    /**
     * 清除所有html缓存
     * @return boolean
     */
    public static function clear(){
        return dir_delete(config()->dir_temp.'/html');
    }
    //一下为辅助方法
    /*--------------------------------------*/
    private static function getFilename(){
        static $filename;
        !$filename and $filename=config()->dir_temp.'/html/'.o_route_manager()->getRoute(true);
        return $filename;
    }
    /**
     * 是否存在某个或者某些html缓存
     * @param string $filename  缓存html文件名
     * @return bool|string
     */
    private static function _exists($filename){
        if(!file_exists($filename)){
            self::_delete($filename);
            return FALSE;
        }
        $expireFile=self::_expire_filename($filename);
        if(!file_exists($expireFile)) return TRUE;
        $expireTime=include $expireFile;
        if($expireTime >= TIME) return TRUE;
        self::_delete($filename);
        return FALSE;
    }
    private static function _delete($filename){
        if(file_exists($filename)) unlink ($filename);
        if(file_exists(self::_expire_filename($filename))){
       unlink (self::_expire_filename($filename));
        }
        return TRUE;
    }
    private static function _expire_filename($filename){
        return $filename.'_expire.php';
    }
}