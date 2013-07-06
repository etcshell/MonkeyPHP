<?php
namespace Monkey\Session\Driver;

/**
 * \Monkey\Session\Driver\Apc
 * @package    \Monkey\Session\Driver
 * @author     HuangYi
 * @copyright  Copyright (c) 2011-07-01——2013-03-30
 * @license    New BSD License
 * @version    $Id: \Monkey\Session\Driver\Apc.php 版本号 2013-03-30 $
 */
class Apc
{
    private static $_expiry=3600;
    /**
     * 初使化session
     * @param $config
     */
    public static function init($config){
        if(!extension_loaded('apc')) error_exit('会话出错:没有安装APC扩展。',2048,__FILE__,__LINE__);
        self::$_expiry=$config['expiry'];
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
     * @param	string	$sessionid	session的ID
     * @return 	mixed	返回session中对应的数据
     */
    public static function read($sessionid){
        $out=apc_fetch(self::_storageKey($sessionid));
        if($out===FALSE){
            return '';
        }  else {
            return $out;
        }
    }
    /**
     * 向session中添加数据
     * @param string $sessionid session的ID
     * @param string $data 序列化的Session变量
     * @return boolean
     */
    public static function write($sessionid, $data){
        return apc_store(self::_storageKey($sessionid), $data, self::$_expiry);
    }
    /**
     * 销毁Session
     * @param string $sessionid session的ID
     * @return boolean
     */
    public static function destroy($sessionid){
        return apc_delete(self::_storageKey($sessionid));
    }
    /**
     * 回收过期的session
     * @param integer $lifetime
     * @return boolean
     */
    public static function gc($lifetime){
        //无需额外回收,apc有自己的过期回收机制
        return true;
    }
    private static function _storageKey($sessionid){
        return 'session_'.$sessionid;
    }
}