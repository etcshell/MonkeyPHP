<?php
namespace Monkey\Cache;
use Monkey\Cache;

/**
 * Memcache
 * \Monkey\App\App
 * @package Monkey\Cache
 */
class Memcache implements Cache
{
    private $_expire = 3600;
    private $_connection;
    private $_compressed=false;     //MEMCACHE_COMPRESSED
    /**
     * @param \Monkey\App\App $app
     */
    public function __construct($app)
    {
        if(!extension_loaded('memcache'))
            $app->exception('没有安装memcache扩展,请先在php.ini中配置安装memcache。');
        $config=$app->config()->getComponentConfig('cache','memcache');
        $this->_expire=$config['expire'];
        $this->_compressed=$config['compressed']?$config['compressed']:FALSE;
        $this->_connection=new \Memcache();
        if(!$this->_connection->addserver($config['host'],$config['port'],$config['persistent']))
            $app->exception('连接memcache服务器时失败，请确认你的连接参数是否正确。');

    }

    /**
     * 设置缓存
     * @param string $key 要设置的缓存项目名称
     * @param mixed $value 要设置的缓存项目内容
     * @param int $time 要设置的缓存项目的过期时长，默认保存时间为 -1，永久保存为 0
     * @return bool 保存是成功为true ，失败为false
     */
    public function store($key,$value,$time=-1){
        if($time==-1 ) $time=$this->_expire;
        $sValue=serialize($value);
        if(!$this->_connection->add($key, $sValue, $this->_compressed, $time)){
            return $this->_connection->set( $key, $sValue, $this->_compressed, $time);
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
        $temp=$this->_connection->get($key);
        if($temp===FALSE) return FALSE;
        $result=unserialize($temp);
        return TRUE;
    }

    /**
     * 清除缓存
     * @return $this
     */
    public function clear(){
        $this->_connection->flush();
        return ;
    }

    /**
     * 删除缓存单元
     * @param string $key
     * @return $this
     */
    public function delete($key){
        $this->_connection->delete($key);
        return ;
    }

    /**
     * 获取Memcache的状态
     */
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
        $this->_connection->close();
    }
}