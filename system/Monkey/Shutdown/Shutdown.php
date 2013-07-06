<?php
/**
 * Created by JetBrains PhpStorm.
 * User: hyiyou
 * Date: 13-5-1
 * Time: 下午7:43
 * To change this template use File | Settings | File Templates.
 */

namespace Monkey\Shutdown;


use Monkey\_Interface\Component;

class Shutdown implements Component
{
    /**
     * @var \Monkey\Monkey
     */
    private $oMonkey;

    private
        $callbalks=array(),
        $calledByMe='This is called by the framework'
    ;
    private function __construct()
    {
        register_shutdown_function(array($this,'execute'), $this->calledByMe);
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
        foreach($config as $call)
        {
            $this->registerCallbalk($call);
        }
    }

    /**
     * 获取shutdown管理器实例
     * @return \Monkey\Shutdown\Shutdown
     */
    public static function _instance()
    {
        static $self;
        !$self and $self=new self();
        return $self;
    }

    /**
     * 注册结束后的回调函数
     * 用法：
     * 1. $shutdown->registerCallbalk( 'myfunction' );
     * 2. $shutdown->registerCallbalk( 'myclass::staticmethod' );
     * 3. $shutdown->registerCallbalk( array('myclass', 'method') );
     * 4. $shutdown->registerCallbalk( array( $myObject, 'method') );
     *
     * @param string|array $callbalk
     *
     */
    public function registerCallbalk($callbalk)
    {
        $this->callbalks[]=$callbalk;
    }

    public function getCallbalks()
    {
        return $this->callbalks;
    }

    public function execute()
    {
        if(func_get_arg(0)!=$this->calledByMe)return;
        $callbalks=self::_instance()->getCallbalks();
        foreach($callbalks as $callbalk)
        {
            call_user_func($callbalk);
        }
    }
}