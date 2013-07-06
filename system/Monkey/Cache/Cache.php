<?php
namespace Monkey\Cache;
use Monkey\_Interface\Component;


/**
 * 缓存管理器\Monkey\Cache\Cache
 * @package    \Monkey\Cache
 * @author     HuangYi
 * @copyright  Copyright (c) 2011-07-01——2013-03-30
 * @license    New BSD License
 * @version    $Id: \Monkey\Cache\Cache.php 版本号 2013-03-30 $
 */
class Cache implements Component
{
    /**
     * @var \Monkey\Monkey
     */
    private $oMonkey;

    /**
     * @var \Monkey\Cache\_Interface
     */
    private $oDefaultCache;

    private $config;


    private static $cacher=array();

    private function __construct()
    {
        // TODO: Implement __construct() method.
    }

    /**
     * 组件初始化注入
     * @param \Monkey\Monkey|null $monkey 依赖类
     * @param mixed|null $config 配置
     * @return mixed
     */
    public function _initialize($monkey = null, $config = null)
    {
        $this->oMonkey=$monkey;
        !$config['default_expire'] and $config['default_expire']=3600;
        $this->config=$config;
    }

    /**
     * 获取缓存管理组件实例
     * @return \Monkey\Cache\Cache
     */
    public static function _instance()
    {
        static $self;
        !$self and $self=new self();
        return $self;
    }

    /**
     * @return \Monkey\Cache\_Interface
     */
    public function getDefaultCache()
    {
        if(!$this->oDefaultCache)
        {
            $type=strtolower($this->config['default_cache']);
            $this->oDefaultCache = $this->createCacheByType($type);
        }
        return $this->oDefaultCache;
    }

    /**
     * 获取指定类型的缓存
     * @param string|null $type 为空时取配置中default_cache项的值。
     * @return \Monkey\Cache\_Interface
     */
    public function getCacheByType($type=null)
    {
        $type= $type ? strtolower($type) : strtolower($this->config['default_cache']);
        !self::$cacher[$type] and self::$cacher[$type]=$this->createCacheByType($type);
        return self::$cacher[$type];
    }


    /**
     * @return \Monkey\Cache\Apc
     */
    public function getApcCache(){
        return $this->getCacheByType('apc');
    }

    /**
     * @return \Monkey\Cache\Eaccelerator
     */
    public function getEacceleratorCache(){
        return $this->getCacheByType('eaccelerator');
    }

    /**
     * @param array $config
     * @return \Monkey\Cache\File
     */
    public function newFileCache($config=null ){
        return $this->createCacheByType('file',$config);
    }

    /**
     * @return \Monkey\Cache\File
     */
    public function getFileCache()
    {
        return $this->getCacheByType('file');
    }

    /**
     * @param array $config
     * @return \Monkey\Cache\Memcache
     */
    public function newMemcacheCache($config=null){
        return $this->createCacheByType('memcache',$config);
    }

    /**
     * @return \Monkey\Cache\Memcache
     */
    public function getMemcacheCache(){
        return $this->getCacheByType('memcache');
    }

    /**
     * @return \Monkey\Cache\Xcache
     */
    public function getXcache(){
        return $this->getCacheByType('xcache');
    }

    /**
     * 创建缓存
     * @param string $type 类型（全小写字母），默认使用file
     * @param null|array $config
     * @return \Monkey\Cache\_Interface
     */
    private function createCacheByType($type, $config=null)
    {
        $class= $type ? ucfirst($type) : 'File';
        !$config and $config = $this->config[$type];
        !$config['expire'] and $config['expire'] = $this->config['default_expire'];
        return new $class($config);
    }
}