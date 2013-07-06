<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Administrator
 * Date: 13-5-29
 * Time: 下午8:52
 * To change this template use File | Settings | File Templates.
 */

namespace Monkey\Logger;


use Monkey\_Interface\Component;

class Logger implements Component
{
    /**
     * @var \Monkey\Monkey
     */
    private $oMonkey;
    private
        $config,
        $loggers=array()
    ;
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
        $this->oMonkey = $monkey;
        $this->config = $config;
    }

    /**
     * 获取组件实例
     * @return \Monkey\Logger\Logger
     */
    public static function _instance()
    {
        static $self;
        !$self and $self=new self();
        return $self;
    }

    /**
     * 添加日志
     * @param string $type 见日志组件包的Type子目录，目前支持 error | sql
     * @param string|array $data 单条日志信息
     */
    public function put($type, $data)
    {
        if($logger=$this->getLoggerByType($type))
        {
            $logger->put($data);
        }
    }

    /**
     * 批量添加日志信息
     * @param string $type 见日志组件包的Type子目录，目前支持 error | sql
     * @param array $datas 多个单条日志信息组成数组
     */
    public function add($type, array $datas)
    {
        if($logger=$this->getLoggerByType($type))
        {
            $logger->add($datas);
        }
    }

    /**
     * @param $type
     * @return Type\_Interface
     */
    private function getLoggerByType($type)
    {
        $type=strtolower($type);
        if(!isset($this->config[$type]) || !$this->config[$type]['enable'])
        {
            return null;
        }
        if(!$this->loggers[$type])
        {
            $class=__NAMESPACE__.'\\Type\\'.ucfirst($type);
            $this->loggers[$type] = new $class($this->oMonkey,$this->config[$type]);
        }
        return $this->loggers[$type];
    }

}