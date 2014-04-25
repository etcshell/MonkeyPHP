<?php
namespace Monkey\App;

/**
 * Class Config
 * @package Monkey\App
 * 职能：存取配置
 * 使用：不建议直接使用，参见\Monkey\App\App。
 */
class Config {

    public static $data=array();
    private static $compileFile;

    /**
     * @param \Monkey\App\App $app
     */
    public function __construct($app)
    {
        self::$compileFile=$app->DIR.'/temp/Config/data.php';
    }

    /**
     * 设置一个配置项
     * @param $key
     * @param $value
     */
    public static function set($key,$value)
    {
        self::$data[$key]=$value;
    }
    /**
     * 获取一个配置项
     * @param string $key 配置名称
     * @param mixed  $defaultValue 返回失败时的默认值
     * @return mixed 配置值
     */
    public static function get($key, $defaultValue=null)
    {
        return (isset(self::$data[$key]) || self::$data[$key]!==null) ? self::$data[$key] : $defaultValue;
    }

    /**
     * 设定组件提供者
     * @param string $component_name
     * @param array $providers  提供者列表，必须指明默认提供者
     * array(
     *    'default_provider'=>'file',
     *    'file'          =>'\Monkey\Cache\File',
     *    'memcache'      =>'\Monkey\Cache\Memcache',
     *    'apc'           =>'\Monkey\Cache\Apc',
     *    ...
     *    );
     */
    public static function setComponentProvider($component_name,array $providers)
    {
        self::$data['_'.$component_name]=$providers;
    }

    /**
     * 获取组件提供者
     * @param $component_name
     * @return array|null
     */
    public static function getComponentProvider($component_name)
    {
        return self::$data['_'.$component_name];
    }

    /**
     * 设定组件提供者的配置
     * @param string $component_name
     * @param string $provider  提供者
     * @param array $data  提供者的配置
     */
    public static function setComponentConfig($component_name,$provider,$data)
    {
        self::$data['_'.$component_name.'_'.$provider]=$data;
    }

    /**
     * 获取组件提供者的配置
     * @param string $component_name
     * @param string $provider  提供者
     * @return array|null
     */
    public static function getComponentConfig($component_name,$provider)
    {
        return self::$data['_'.$component_name.'_'.$provider];
    }

    /**
     * 批量载入目录内的配置文件
     * 配置文件格式见框架提供的默认配置
     * @param $dir
     */
    public function loadFromDir($dir)
    {
        foreach(glob($dir.'/*.ini.php', GLOB_NOESCAPE) as $data){
            require $data;
        }
    }

    /**
     * 保存已经编译的配置
     */
    public function saveCompile()
    {
        dir_check(dirname(self::$compileFile));
        file_put_contents(self::$compileFile,serialize(self::$data),LOCK_EX);
    }

    /**
     * 载入已经编译的配置
     * @return bool
     */
    public function loadCompile()
    {
        if(file_exists(self::$compileFile)){
            self::$data=unserialize(file_get_contents(self::$compileFile))+self::$data;
            return true;
        }
        return false;
    }

    /**
     * 删除已经编译的配置
     */
    public function deleteCompile()
    {
        if(file_exists(self::$compileFile)) unlink(self::$compileFile);
    }


}