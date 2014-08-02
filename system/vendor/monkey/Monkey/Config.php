<?php
/**
 * Project MonkeyPHP
 *
 * PHP Version 5.3.9
 *
 * @package   Monkey
 * @author    黄易 <582836313@qq.com>
 * @version   GIT:<git_id>
 */
namespace Monkey;

/**
 * Class Config
 *
 * 应用配置管理类
 *
 * @package Monkey
 */
class Config {

    /**
     * 配置数据
     *
     * @var array
     */
    public static $data = array();

    /**
     * 应用配置文件路径
     *
     * @var string
     */
    private $_configFile;

    /**
     * 配置文件编译后的保存位置
     *
     * @var string
     */
    private $_compileFile;

    /**
     * 构造方法
     *
     * @param $configFile
     * @param $tempDir
     */
    public function __construct($configFile, $tempDir) {
        //设置应用配置文件
        $this->_configFile = $configFile;

        //设置配置文件编译后的保存位置
        $this->_compileFile = $tempDir . '/config/' . basename($configFile);
        dir_check(dirname($this->_compileFile));

        //加载配置
        $this->_loadConfig();
    }

    /**
     * 加载配置
     */
    private function _loadConfig() {
        $configFile = $this->_configFile;
        $compileFile = $this->_compileFile;

        if (file_exists($compileFile) and filemtime($compileFile) > filemtime($configFile)) {
            //直接读取配置
            self::$data = unserialize(file_get_contents($compileFile));

        }
        else {

            //加载应用专有配置
            self::$data = (array)include($configFile);

            //加载框架默认配置
            self::$data += include(dirname(strtr(__DIR__, DIRECTORY_SEPARATOR, '/')) . '/Globe/data/config.default.php');

            //编译并保存配置
            file_put_contents($compileFile, serialize(self::$data), LOCK_EX);
        }
    }

    /**
     * 设置一个配置项
     *
     * @param $key
     * @param $value
     */
    public function set($key, $value) {
        self::$data[$key] = $value;
    }

    /**
     * 获取一个配置项
     *
     * @param string $key 配置名称
     * @param mixed $defaultValue 返回失败时的默认值
     *
     * @return mixed 配置值
     */
    public function get($key, $defaultValue = null) {
        return (isset(self::$data[$key]) || self::$data[$key] !== null) ? self::$data[$key] : $defaultValue;
    }

    /**
     * 获取组件提供者
     *
     * @param $component_name
     *
     * @return array|null
     */
    public function getComponentProvider($component_name) {
        return self::$data[$component_name];
    }

    /**
     * 获取组件提供者的配置
     *
     * @param string $component_name
     * @param string $provider 提供者
     *
     * @return array|null
     */
    public function getComponentConfig($component_name, $provider) {
        return self::$data[$component_name . '_' . $provider];
    }

    /**
     * 更新源文件配置
     */
    public function update() {
        file_save_variable($this->_configFile, self::$data);
    }

}