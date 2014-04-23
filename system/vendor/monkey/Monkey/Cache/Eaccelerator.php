<?php
namespace Monkey\Cache;

use Monkey\Cache;

/**
 * cache的Eaccelerator实现\Monkey\Cache\Eaccelerator
 */
class Eaccelerator implements Cache
{
    private $_expire = 3600;
    /**
     * eaccelerator缓存实现
     */
    public function __construct($app)
    {
        if(!extension_loaded('eaccelerator'))
            throw new \Exception('没有安装eaccelerator扩展,请先在php.ini中配置安装eaccelerator。');
        $config=$app->config->getComponentConfig('cache','eaccelerator');
        $this->_expire=$config['expire'];
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
//        eaccelerator_gc();//清除过期数据
        return eaccelerator_put($key, serialize($value), $time);
    }

    /**
     * 读取缓存
     * @param string $key       要读取的缓存项目名称
     * @param mixed &$result    要保存的结果地址
     * @return bool             成功返回true，失败返回false
     */
    public function fetch($key, &$result){;
        $result=NULL;
        $temp=eaccelerator_get($key);
        if($temp===NULL) return FALSE;
        $result=unserialize($temp);
        return TRUE;
    }
    /**
     * 清除缓存
     * @return $this
     */
    public function clear(){
        return TRUE;//没有找到eaccelerator的清除接口
    }
    /**
     * 删除缓存单元
     * @param string $key
     * @return $this
     */
    public function delete($key){
        eaccelerator_rm($key);
    }
}