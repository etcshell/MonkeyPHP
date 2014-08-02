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
 * Interface ErrorInterface
 *
 * 日志接口
 *
 * @package Monkey\Logger
 */
interface ErrorInterface
{

    /**
     * 构造方法
     *
     * @param Monkey\App $app
     * @param mixed|null $config 配置
     */
    public function __construct($app, $config);

    /**
     * 添加一条日志信息
     *
     * @param string|array $data 日志信息
     */
    public function put($data);

    /**
     * 写入日志文件
     * 不需要手动调用
     *
     * @return bool
     */
    public function write();

}