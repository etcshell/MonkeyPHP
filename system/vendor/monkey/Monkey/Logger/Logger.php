<?php
/**
 * Project MonkeyPHP
 *
 * PHP Version 5.3.9
 *
 * @package   Monkey\Logger
 * @author    黄易 <582836313@qq.com>
 * @version   GIT:<git_id>
 */
namespace Monkey\Logger;

use Monkey;

/**
 * Class Logger
 *
 * 日志组件
 *
 * @package Monkey\Logger
 */
class Logger {

    /**
     * 应用对象
     *
     * @var Monkey\App
     */
    public $app;

    /**
     * 配置
     *
     * @var array
     */
    private $config;

    /**
     * 错误日志
     *
     * @var Error
     */
    private $error;

    /**
     * sql日志
     *
     * @var Sql
     */
    private $sql;

    /**
     * 构造方法
     *
     * @param Monkey\App $app
     */
    public function __construct($app) {
        $this->app = $app;
        $this->config = $config = $app->config()->getComponentConfig('logger', 'default');
        $config['error_dir'] = $app->DIR . $config['error_dir'];
        $config['sql_dir'] = $app->DIR . $config['sql_dir'];
        isset($config['error_enable']) and $this->error = new Error($app, $config);
        isset($config['sql_enable']) and $this->sql = new Sql($app, $config);
    }

    /**
     * 添加错误日志
     *
     * @param string|array $data 单条日志信息
     */
    public function error($data) {
        if ($this->error) {
            $this->error->put($data);
        }
    }

    /**
     * 添加sql日志
     *
     * @param string|array $data 单条日志信息
     */
    public function sql($data) {
        if ($this->sql) {
            $this->sql->put($data);
        }
    }

}