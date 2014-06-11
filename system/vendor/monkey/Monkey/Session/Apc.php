<?php
namespace Monkey\Session;
use Monkey\Session;

/**
 * Apc
 * Session的APC实现
 * @package Monkey\Session
 */
class Apc extends Session
{
    /**
     * @param \Monkey\App\App $app
     */
    public function __construct($app)
    {
        if(!extension_loaded('apc')) $app->exception('会话出错:没有安装APC扩展。',2048,__FILE__,__LINE__);
        $this->app=$app;
        $this->config = $app->config()->getComponentConfig('session','apc');
        $this->start();
    }
    /**
     * 打开Session文件
     * @param string $path
     * @param string $name
     * @return boolean
     */
    public function open($path, $name){
        //因为没有用文件存储Session，所以用不着
        return true;
    }
    /**
     * 关闭Session文件
     * @return boolean
     */
    public function close(){
        //因为没有用文件存储Session，所以用不着
        return true;
    }
    /**
     * 从session中读取数据
     * @param	string	$sessionid	session的ID
     * @return 	mixed	返回session中对应的数据
     */
    public function read($sessionid){
        $out=apc_fetch($this->_storageKey($sessionid));
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
    public function write($sessionid, $data){
        return apc_store($this->_storageKey($sessionid), $data, $this->_expire);
    }
    /**
     * 销毁Session
     * @param string $sessionid session的ID
     * @return boolean
     */
    public function destroy($sessionid){
        return apc_delete($this->_storageKey($sessionid));
    }
    /**
     * 回收过期的session
     * @param integer $lifetime
     * @return boolean
     */
    public function gc($lifetime){
        //无需额外回收,apc有自己的过期回收机制
        return true;
    }
}