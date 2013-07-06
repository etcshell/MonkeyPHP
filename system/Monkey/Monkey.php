<?php
namespace Monkey;

class Monkey
{
    private static
        $config,
        $registers=array(),
        $components=array(),
        $actionStack       = null,
        $controller        = null,
        $databaseManager   = null,
        $request           = null,
        $response          = null,
        $storage           = null,
        $viewCacheManager  = null,
        $i18n              = null,
        $logger            = null,
        $user              = null
    ;

    public function __construct(&$config)
    {
        self::$config=$config;
    }

    /**
     * 注册组件
     * @param string $name 组件名
     * @param string|object $handler 处理对象的类名、对象（要求已经初始化了）、甚至是匿名函数。
     */
    public function registerComponent($name,$handler)
    {
        self::$config['component_'.strtolower($name)]=$handler;
    }

    /**
     * 获取组件实例
     * @param $name
     * @return mixed
     */
    public function getComponent($name)
    {//, $isSingleton=false
        static $components;
        $name=strtolower($name);
        if(!$components[$name])
        {
            $component= self::$config['component_'.$name];
            if(is_string($component))
            {
                $component=$component::_instance()->_initialize($this, self::$config[$name]);
            }
            $components[$name]=$component;
        }
        return $components[$name];
    }

    /**
     * 获取缓存实例
     * @return \Monkey\Cache\Cache
     */
    public function getCache()
    {
        return $this->getComponent('cache');
    }

    /**
     * 获取Cookie对象
     * @return \Monkey\Cookie\Cookie
     */
    public function getCookie()
    {
        return $this->getComponent('cookie');
    }

    /**
     * @return \Monkey\Request\Request
     */
    public function getRequest()
    {
        return $this->getComponent('request');
    }

    /**
     * @return \Monkey\Upload\Upload
     */
    public function getUpload()
    {
        return $this->getComponent('upload');
    }
    /**
     * @return \Monkey\Response\Response
     */
    public function getResponse()
    {
        return $this->getComponent('response');
    }

    /**
     * @return \Monkey\Html\Html
     */
    public function getHtml()
    {
        return $this->getComponent('html');
    }

    /**
     * @return \Monkey\Session\Session
     */
    public function getSession()
    {
        return $this->getComponent('session');
    }

    /**
     * @return \Monkey\Shutdown\Shutdown
     */
    public function getShutdown()
    {
        return $this->getComponent('shutdown');
    }

    /**
     * @return \Monkey\Logger\Logger
     */
    public function getLogger()
    {
        return $this->getComponent('logger');
    }

    /**
     * @return \Monkey\Router\Router
     */
    public function getRouter()
    {
        return $this->getComponent('router');
    }

    /**
     * @return \Monkey\Dispatcher\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->getComponent('dispatcher');
    }

/////////////////////////////////////////////////////




    public function getController()
    {

    }
    public function getStorage()
    {

    }
    public function getDatabase()
    {

    }
    public function getQuery()
    {

    }

    /**
     * 获取语言管理器
     * @return \Monkey\Language\Manager
     */
    public function getI18N()
    {
        static $init;
        $language_manager=$this->getComponent('language_manager');
        if(!$init)
        {
            $config['waiter']=$this;
            $config['defaultLanguage']=\App::get('response_language_default','zh-cn');
            $language_manager->initialize($config);
            $init=true;
        }
        return $language_manager;
    }

    public function getError()
    {

    }
    public function getView()
    {

    }
    public function getSkin()
    {

    }
    public function getStyle()
    {

    }
    public function getBlock()
    {

    }
}