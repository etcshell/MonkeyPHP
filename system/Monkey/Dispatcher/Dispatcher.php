<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Administrator
 * Date: 13-5-14
 * Time: 下午1:42
 * To change this template use File | Settings | File Templates.
 */

namespace Monkey\Dispatcher;

use Monkey\BreakException;
use Monkey\_Interface\Component;

class Dispatcher implements Component
{
    /**
     * @var \Monkey\Monkey
     */
    private $oMonkey;

    private
        $config
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
        $this->oMonkey= $monkey;
        $this->config= $config;
    }

    /**
     * 获取组件实例
     * @return \Monkey\Dispatcher\Dispatcher
     */
    public static function _instance()
    {
        static $self;
        !$self and $self=new self();
        return $self;
    }

    public function dispatching()
    {
        try
        {


        }
        catch(BreakException $e)
        {

        }
        catch(\Exception $e)
        {

        }
    }

}