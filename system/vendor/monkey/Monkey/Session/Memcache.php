<?php
namespace Monkey\Session;
use Monkey\Session;

/**
 * Memcache
 * Session的Memcache实现
 * @package Monkey\Session
 */
class Memcache extends Session
{
    /**
     * @var Memcache
     */
    private $handler=null;
    /**
     * @param \Monkey\App\App $app
     */
    public function __construct($app)
    {
        if(!extension_loaded('apc')) $app->exception('会话出错:没有安装APC扩展。',2048,__FILE__,__LINE__);
        $this->app=$app;
        $this->config = $app->config()->getComponentConfig('session','memcache');
        $this->handler=new \Memcache();
        if(!$this->handler->connect($this->config['host'],$this->config['port'])){
            $app->exception('会话出错:Memcache连接失败。',2048,__FILE__,__LINE__);
        }
        $this->init();
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
        $out=$this->handler->get($this->_storageKey($sessionid));
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
    public function write($sessionid, $data){
        $method=$data ? 'set' : 'replace';
        return $this->handler->$method($this->_storageKey($sessionid), $data, MEMCACHE_COMPRESSED, $this->_expire);
    }
    /**
     * 销毁Session
     * @param string $sessionid session的ID
     * @return boolean
     */
    public function destroy($sessionid){
        $this->handler->delete($this->_storageKey($sessionid));
        return ;
    }
    /**
     * 回收过期的session
     * @param integer $lifetime
     * @return boolean
     */
    public  function gc($lifetime){
        //无需额外回收,memcache有自己的过期回收机制
        return true;
    }
}