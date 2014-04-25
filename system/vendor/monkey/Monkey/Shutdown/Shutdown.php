<?php
namespace Monkey\Shutdown;

/**
 * Shutdown组件
 * @package Monkey\Shutdown
 */

class Shutdown
{
    private
        /**
         * @var \Monkey\App\App
         */
        $app,
        $Callbacks=array(),
        $calledByMe='This is called by the framework'
    ;
    /**
     * @param \Monkey\App\App $app
     */
    public function __construct($app)
    {
        $this->app=$app;
        register_shutdown_function(array($this,'execute'), $this->calledByMe);
        $config=$app->config->getComponentConfig('shutdown', 'default');
        foreach($config as $call){
            $this->register($call);
        }
    }

    /**
     * 注册结束后的回调函数
     * 用法：
     * 1. $shutdown->register( 'myfunction' );
     * 2. $shutdown->register( 'myclass::staticmethod' );
     * 3. $shutdown->register( array('myclass', 'method') );
     * 4. $shutdown->register( array( $myObject, 'method') );
     *
     * @param string|array $Callback
     *
     */
    public function register($Callback)
    {
        $this->Callbacks[]=$Callback;
    }

    public function getCallbacks()
    {
        return $this->Callbacks;
    }

    public function execute()
    {
        if(func_get_arg(0)!=$this->calledByMe)return;
        foreach($this->Callbacks as $Callback)
        {
            call_user_func($Callback);
        }
    }
}