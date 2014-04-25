<?php
namespace Monkey\Logger;

/**
 * Logger
 * 日志组件
 * @package Monkey\Logger
 */
class Logger
{
    private
        /**
         * @var \Monkey\App\App
         */
        $app,
        $config,
        /**
         * @var Error
         */
        $error,
        /**
         * @var Sql
         */
        $sql
    ;
    /**
     * @param \Monkey\App\App $app
     */
    public function __construct($app)
    {
        $this->app=$app;
        $this->config = $config= $app->config()->getComponentConfig('logger','default');
        $config['error_dir'] = $app->DIR.$config['error_dir'];
        $config['sql_dir'] = $app->DIR.$config['sql_dir'];
        $config['error_enable'] and $this->error= new Error($app,$config);
        $config['sql_enable'] and $this->sql= new Sql($app,$config);
    }

    /**
     * 添加错误日志
     * @param string|array $data 单条日志信息
     */
    public function error($data)
    {
        if($this->error) $this->error->put($data);
    }

    /**
     * 添加sql日志
     * @param string|array $data 单条日志信息
     */
    public function sql($data)
    {
        if($this->sql) $this->sql->put($data);
    }

}