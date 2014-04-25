<?php
namespace Monkey\Logger;

/**
 * 日志接口 _Interface
 * @package Monkey\Logger
 */

interface _Interface
{

    /**
     * 构造方法
     * @param \Monkey\App\App $app
     * @param mixed|null $config 配置
     */
    public function __construct($app, $config);

    /**
     * 添加一条日志信息
     * @param string|array $data 日志信息
     */
    public function put($data);

    /**
     * 写入日志文件
     * 不需要手动调用
     * @return bool
     */
    public function write();

}