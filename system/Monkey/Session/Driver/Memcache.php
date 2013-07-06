<?php
namespace Monkey\Session\Driver;


/**
 * \Monkey\Session\Driver\Memcache
 * @package    \Monkey\Session\Driver
 * @author     HuangYi
 * @copyright  Copyright (c) 2011-07-01——2013-03-30
 * @license    New BSD License
 * @version    $Id: \Monkey\Session\Driver\Memcache.php 版本号 2013-03-30 $
 */
class Memcache
{
    /**
     * @var Memcache
     */
    private static $handler=null;
    private static $_expiry=3600;
    public static function init($config){
        //将 session.save_handler 设置为 user，而不是默认的 files
        self::$_expiry=$config['expiry'];
        self::$handler=new \Memcache();
        self::$handler->connect($config['host'],$config['port']);
        if(!self::$handler->connect($config['host'],$config['port'])){
            error_exit('会话出错:Memcache连接失败。',2048,__FILE__,__LINE__);
        }
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
        $out=self::$handler->get(self::_storageKey($sessionid));
        if($out===false || $out == null){
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
        $method=$data ? 'set' : 'replace';
        return self::$handler->$method(self::_storageKey($sessionid), $data, MEMCACHE_COMPRESSED, self::$_expiry);
    }
    /**
     * 销毁Session
     * @param string $sessionid session的ID
     * @return boolean
     */
    public static function destroy($sessionid){
        self::$handler->delete(self::_storageKey($sessionid));
        return ;
    }
    /**
     * 回收过期的session
     * @param integer $lifetime
     * @return boolean
     */
    public static function gc($lifetime){
        //无需额外回收,memcache有自己的过期回收机制
        return true;
    }
    private static function _storageKey($sessionid){
        return 'session_'.$sessionid;
    }
}