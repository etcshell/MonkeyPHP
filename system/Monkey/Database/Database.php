<?php
namespace Monkey\Database;
use Monkey\_Interface\Component;

/**
 * 数据层管理器\Framework\DatabaseManager
 * @package    Framework
 * @author     HuangYi
 * @copyright  Copyright (c) 2011-07-01——2013-03-30
 * @license    New BSD License
 * @version    $Id: \Framework\DatabaseManager.php 版本号 2013-03-30 $
 */
class Database implements Component
{
    /**
     * @var \Monkey\Monkey
     */
    private $oMonkey;

    private
        $config,
        $pool,
        $default,
        $oConnections
    ;


    /**
     * 构造方法
     */
    private function __construct()
    {
        if( !extension_loaded('pdo') )
        {
            error_exit('没有安装pdo驱动扩展,请先在php.ini中配置安装pdo！',1024,__FILE__,__LINE__);
        }
    }

    /**
     * 组件初始化注入
     * @param \Monkey\Monkey|null $monkey 依赖类
     * @param mixed|null $config 配置
     * @return mixed
     */
    public function _initialize($monkey = null, $config = null)
    {
        $this->clear();
        $this->oMonkey=$monkey;
        $this->config=$config;

        $this->pool= $config['pool'];
        $this->default= $config['default_connection'];

        if(!$this->default || !isset($this->pool[$this->default])) {
            reset($this->pool);
            $this->default= key($this->pool);
        }

    }

    /**
     * 获取组件实例
     * @return \Monkey\Database\Database
     */
    public static function _instance()
    {
        static $self;
        !$self and $self=new self();
        return $self;
    }

    /**
     * 获取指定名称的查询连接
     *
     * @param string|null $connectName 连接名称。留空时，使用默认连接名
     * @return Query\Connection|bool|null
     *
     * 1.正确返回Mysql\Connection；
     * 2.不存在指定的连接（包括默认连接）返回null；
     * 3.连接失败返回false。
     */
    public function getConnection($connectName=null)
    {
        $connectName= $this->_getConfigName($connectName);

        if(!$connectName) {
            return null;
        }

        if(!isset($this->oConnections[$connectName])) {
            $this->oConnections[$connectName]= $this->connecting($connectName);
        }

        return $this->oConnections[$connectName];
    }

    /**
     * @param $connectName
     * @return Query\Connection
     */
    private function connecting( $connectName )
    {
        $config=$this->pool[$connectName];
        $class='Query\\'.ucfirst(strtolower($config['protocol'])).'\\Connection';
        try {
            $connect = new $class($this->oMonkey,$connectName,$config);
        } catch (\PDOException $e) {
            $error=array(
                'error_title'       =>'连接到PDO时出错。',
                'message'           =>$e->getMessage(),
                'code'              =>$e->getCode(),
            );
            $this->oMonkey->getLogger()->put('sql',$error);
            return false;
        }
        return $connect;
    }

    /**
     * 设置一条连接的配置
     * @param string $connectionName 连接名
     * @param array $config 连接配置
     */
    public function setConfig($connectionName,$config)
    {
        $connectionName=strtolower($connectionName);
        $this->pool[$connectionName]=$config;
        if(isset($this->oConnections[$connectionName])) {
            $this->oConnections[$connectionName]=null;
        }
    }

    /**
     * 获取指定名称的连接配置
     * 当指定连接不存在时候，返回null；
     * 当不指定连接名称时，返回默认连接。
     * @param $connectionName
     * @return array
     */
    public function getConfig($connectionName=null)
    {
        $connectionName=$this->_getConfigName($connectionName);
        return $connectionName ? $this->pool[$connectionName] : null;
    }

    /**
     * 获取连接池中所有连接的配置
     * @return array
     */
    public function getAllConfig()
    {
        return $this->pool;
    }

    /**
     * 删除某个连接
     * @param $name
     */
    public function deleteConfig($name)
    {
        $name=strtolower($name);
        if(!array_key_exists($name,$this->pool)) {
            return;
        }
        unset($this->pool[$name]);
        if($this->default==$name) {
            reset($this->pool);
            $this->default= key($this->pool) ;
        }
        if($this->oConnections[$name]) {
            $this->oConnections[$name]=null;
        }
    }

    private function _getConfigName($name=null)
    {
        if($name===null) {
            return $this->default;
        }
        else if(!$name) {
            return null;
        }
        else {
            $name=strtolower($name);
            return isset($this->pool[$name]) ? $name : null;
        }
    }

    private function clear()
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