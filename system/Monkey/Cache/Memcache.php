<?php
namespace Monkey\Cache;

/**
 * cache的Memcache实现\Monkey\Cache\Memcache
 * @package    Monkey\Cache
 * @author     HuangYi
 * @copyright  Copyright (c) 2011-07-01——2013-03-30
 * @license    New BSD License
 * @version    $Id: \Monkey\Cache\Memcache.php 版本号 2013-03-30 $
 */
class Memcache implements _Interface
{
    private static $_expire = 3600;
    private $_connection;
    private $_compressed=false;     //MEMCACHE_COMPRESSED
    private $_addservered=false;
    /**
     * memcache缓存实现
     */
    public function __construct($config)
    {
        if(!extension_loaded('memcache')){
            throw new \Exception('没有安装memcache扩展,请先在php.ini中配置安装memcache。');
        }
        $this->_compressed=$config['compressed']?$config['compressed']:FALSE;
        $this->_connection=new \Memcache();
        $this->addserver($config);
    }

    /**
     * 设置缓存
     * @param string $key 要设置的缓存项目名称
     * @param mixed $value 要设置的缓存项目内容
     * @param int $time 要设置的缓存项目的过期时长，默认保存时间为 -1，永久保存为 0
     * @return bool 保存是成功为true ，失败为false
     */
    public function store($key,$value,$time=-1){
        if($time==-1 ) $time=self::$_expire;
        $sValue=serialize($value);
        if(!$this->add($key,$sValue,$time)){
            return $this->set($key,$sValue,$time);
        }
        return TRUE;
    }
    /**
     * 读取缓存
     * @param string $key       要读取的缓存项目名称
     * @param mixed &$result    要保存的结果地址
     * @return bool             成功返回true，失败返回false
     */
    public function fetch($key, &$result){;
        $result=NULL;
        $this->_checkserver( );
        $temp=$this->get($key);
        if($temp===FALSE) return FALSE;
        $result=unserialize($temp);
        return TRUE;
    }
    /**
     * 清除缓存
     * @return $this
     */
    public function clear(){
        $this->_checkserver( );
        $this->_connection->flush();
        return ;
    }
    /**
     * 删除缓存单元
     * @param string $key
     * @return $this
     */
    public function delete($key){
        $this->_checkserver( );
        $this->_connection->delete($key);
        return ;
    }
    /**
     * 添加memcache服务器
     * @param array $server 结构array('host'=>'','port'=>'','persistent'=>'')
     */
    public function addserver($server){
        if(!$this->_connection->addserver($server['host'],$server['port'],$server['persistent'])) {
            throw new \Exception('连接memcache服务器时失败，请确认你的连接参数是否正确。');
        }
        $this->_addservered=TRUE;
    }
    //以下是兼容方法
    public function add($key,$value,$time=-1){
        $this->_checkserver( );
        if($time==-1 ) $time=$this->_expire;
        return $this->_connection->add( $key, $value, $this->_compressed, $time);
    }
    public function set($key,$value,$time=-1){
        $this->_checkserver( );
        if($time==-1 ) $time=$this->_expire;
        return $this->_connection->set( $key, $value, $this->_compressed, $time);
    }
    public function get($key){
        return $this->_connection->get($key);
    }
    public function stats() {
        $this->_connection->getStats();
        return ;
    }
    /**
     * 析构函数
     *
     * @access public
     * @return void
     */
    public function __destruct() {
        if(	$this->_addservered ) $this->_connection->close();
    }
    //辅助函数
    private function _checkserver(){
        if(!$this->_addservered){
            throw new \Exception('连接memcache服务器时失败。');
        }
    }
}