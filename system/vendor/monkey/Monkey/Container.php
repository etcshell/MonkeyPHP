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
 * Class Container
 *
 * 依赖容器类
 *
 * @package Monkey
 */
class Container {

    /**
     * 应用基类
     *
     * @var App
     */
    private static $app;

    /**
     * 配置器对象
     *
     * @var Config
     */
    private static $config;

    /**
     * 组件保存数组
     *
     * @var array
     */
    private static $components;

    /**
     * @param App $app
     */
    public function __construct($app) {
        self::$app = $app;
        self::$config = $app->config();
    }

    /**
     * 注册组件
     *
     * @param string $name 组件名
     * @param null|string|object|\Closure $handler 处理对象的类名、对象（要求已经初始化了）、甚至是匿名函数。
     * @param string $provider
     */
    public function setter($name, $handler, $provider = null) {
        if (!isset(self::$components[$name])) {
            //添加组件组
            $comp = self::$config->getComponentProvider($name);
            self::$components[$name] =
                !empty($comp) ? $comp : array('default_provider' => empty($provider) ? 'default' : $provider);
        }

        //添加具体组件
        empty($provider) and $provider = self::$components[$name]['default_provider'];
        self::$components[$name][$provider] = $handler;
    }

    /**
     * 获取组件实例（此处获取的结果均为单例）
     *
     * @param string $name
     * @param string $provider
     *
     * @return object|\Closure
     */
    public function getter($name, $provider = null) {
        if (!isset(self::$components[$name])) {
            //添加组件组
            self::$components[$name] = self::$config->getComponentProvider($name);
            if (!isset(self::$components[$name])) {
                return null;
            }
        }

        //获取组件实例
        empty($provider) and $provider = self::$components[$name]['default_provider'];
        $component = self::$components[$name][$provider];

        if (is_string($component)) {
            $component = new $component(self::$app);
            self::$components[$name][$provider] = $component;
        }

        return $component;
    }

} 