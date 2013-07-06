<?php
namespace Module\Admin;


use Monkey;


class Main implements Monkey\Module\_Interface
{
    private static $_instance;
    private $_config;
    private $_dir;
    private $_moduleName='Admin';

    private function __construct()
    {
        $this->_dir=dirname(__FILE__);
        $this->_config=include($this->_dir.'/config.ini.php');
    }

    public static function _instance(){
        !self::$_instance and self::$_instance=new self();
        return self::$_instance;
    }

    /**
     * 新建资源\Module\Admin\Main
     * @param string $operation 操作名
     * @param mixed $resource 资源数据
     * @return boolean 成功为TRUE，失败为FALSE
     */
    public function post($operation,$resource)
    {

    }

    /**
     * 获取指定资源
     * @param string $operation 操作名
     * @param mixed $resource 资源匹配数据（用于定位资源）
     * @return mixed
     */
    public function get($operation,$resource)
    {

    }

    /**
     * 更新或新建指定资源
     * @param string $operation 操作名
     * @param mixed $resource 资源数据（新的资源数据，同时兼备定位资源的作用）
     * @return boolean 成功为TRUE，失败为FALSE
     */
    public function put($operation,$resource)
    {

    }

    /**
     * 删除指定资源
     * @param string $operation 操作名
     * @param mixed $resource 资源数据（用于定位资源）
     * @return boolean 成功为TRUE，失败为FALSE
     */
    public function delete($operation,$resource)
    {

    }

    /**
     * 模块翻译
     * @param string|null $append 文件名的附加标识。
     * @return string
     */
    public function getLanguage($append = null)
    {
        return o_language_manager()->getLanguageData($this->_moduleName,$append);

    }

    /**
     * 获取模块配置
     * @param null|string $key 配置项键名，为空时返回所有配置
     * @return mixed
     */
    public function getConfig($key = null)
    {
        if($key) return $this->_config[$key];
        else return $this->_config;
    }

    /**
     * 获取模板文件路径或目录
     * @return Monkey\Template
     */
    public function newTemplate()
    {
        return new Monkey\Template('Admin');
    }
}