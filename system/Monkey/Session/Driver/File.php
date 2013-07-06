<?php
namespace Monkey\Session\Driver;

use Monkey\Monkey;

/**
 * \Monkey\Session\Driver\File
 * @package    Framework\Session
 * @author     HuangYi
 * @copyright  Copyright (c) 2011-07-01——2013-03-30
 * @license    New BSD License
 * @version    $Id: \Framework\Session\File.php 版本号 2013-03-30 $
 */
class File
{
    private static $_expiry=3600;
    /**
     * @var \Monkey\Cache\_Interface
     */
    private static $_cacher;
    /**
     * @static
     * 初使化和开启session
     * @param array $config 配置，可以是array('expiry'=>1440) ,结构见配置文件
     * @return bool
     */
    public static function init($config){
        self::$_expiry=$config['expiry'];
        self::$_cacher=monkey()->getCache()->newFileCache($config);
    }
    /**
     * 打开Session文件
     * @param string $path
     * @param string $name
     * @return boolean
     */
    public static function open($path, $name){
        //因为没有用文件存储Session，所以用不着
        return true;
    }
    /**
     * 关闭Session文件
     * @return boolean
     */
    public static function close(){
        //因为没有用文件存储Session，所以用不着
        return true;
    }
    /**
     * 从session中读取数据
     * @param   string  $sessionid  session的ID
     * @return  mixed   返回session中对应的数据
     */
    public static function read($sessionid){
        $out='';
        if(self::$_cacher->fetch(self::_storageKey($sessionid), $out)){
            return $out;
        }  else {
            return '';
        }
    }
    /**
     * 向session中添加数据
     * @param string $sessionid session的ID
     * @param string $data 序列化的Session变量
     * @return boolean
     */
    public static function write($sessionid, $data){
        return self::$_cacher->store(self::_storageKey($sessionid), $data, self::$_expiry);
    }
    /**
     * 销毁Session
     * @param string $sessionid session的ID
     * @return boolean
     */
    public static function destroy($sessionid){
        return self::$_cacher->delete(self::_storageKey($sessionid));
    }
    /**
     * 回收过期的session
     * @param integer $lifetime
     * @return boolean
     */
    public static function gc($lifetime){
        //无需额外回收,缓存有自己的过期回收机制
        return true;
    }
    private static function _storageKey($sessionid){
        return 'session_'.$sessionid;
    }
}