<?php
namespace Monkey\Database;

/**
 * Database
 * 数据库组件
 * @package Monkey\Database
 */
class Database
{
    private
        /**
         * @var \Monkey\App\App
         */
        $app,
        $config,
        $pool,
        $default,
        $active,
        $oConnections
    ;

    /**
     * @param \Monkey\App\App $app
     */
    public function __construct($app)
    {
        if( !extension_loaded('pdo') )
            $app->exception('没有安装pdo驱动扩展,请先在php.ini中配置安装pdo！',1024,__FILE__,__LINE__);
        $this->app= $app;
        $config= $app->config()->getComponentConfig('database','default');
        $this->config=$config;
        $this->pool= $config['pool'];
        $this->default= $config['default_connection'];
        if(!$this->default || !isset($this->pool[$this->default])) {
            reset($this->pool);
            $this->default= key($this->pool);
        }
        $this->active=$this->default;
    }

    /**
     * 激活某个连接
     * @param string $name 留空时激活默认连接
     * @return bool
     */
    public function activeConnection($name=null)
    {
        if($name===null) $name=$this->default;
        $name=strtolower($name);
        if(!isset($this->pool[$name])) return false;
        $this->active=$name;
        return true;
    }

    /**
     * 获取指定名称的查询连接
     * @param string|null $name 连接名称。留空时，使用当前活动连接
     * @return \Monkey\Database\Connection|bool|null
     *
     * 1.正确返回\Monkey\Database\Connection；
     * 2.不存在指定的连接（包括默认连接）返回null；
     * 3.连接失败返回false。
     */
    public function getConnection($name=null)
    {
        if(empty($name)) {
            $name= $this->active;
        }
        else {
            $name=strtolower($name);
            $name= isset($this->pool[$name]) ? $name : null;
        }
        if(!$name) {
            return null;
        }
        if(!isset($this->oConnections[$name])) {
            $this->oConnections[$name]= $this->tryConnecting($this->config[$name],$name);
        }
        return $this->oConnections[$name];
    }

    /**
     * 尝试连接
     * @param array $config 连接配置
     * @param string $name 连接名称，留空表示测试连接
     * @return \Monkey\Database\Connection|false
     */
    public function tryConnecting($config, $name='test')
    {
        $class=ucfirst(strtolower($config['protocol']));
        $class= $class=='Mysql'? '' : '\\'.$class;//如果是Mysql驱动，直接使用父类，目的是获得更高的效率
        $class= __NAMESPACE__.$class.'\\Connection';
        try {
            $connect = new $class($this->app,$name,$config);
        } catch (\PDOException $e) {
            $error=array(
                'error_title'       =>'连接到PDO时出错。',
                'message'           =>$e->getMessage(),
                'code'              =>$e->getCode(),
            );
            $this->app->logger()->sql($error);
            return false;
        }
        return $connect;
    }

    /**
     * @return \Monkey\Database\Connection
     */
    private function getActiveConnection()
    {
        $name=$this->active;
        if(!isset($this->oConnections[$name])) {
            $this->oConnections[$name]= $this->tryConnecting($this->config[$name],$name);
        }
        if(!isset($this->oConnections[$name])){
            $this->app->exception('连接数据库出错');
        }
        return $this->oConnections[$name];
    }

    public function __destruct()
    {
        $this->config=null;
        $this->pool=null;
        $this->default=null;

        if($this->oConnections) {
            foreach($this->oConnections as $key=>$connection) {
                $connection=null;
                $this->oConnections[$key]=null;
            }
            $this->oConnections=null;
        }
    }

}