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
 * Class Sql
 *
 * 数据库错误日志类
 *
 * @package Monkey\Logger
 */
class Sql implements ErrorInterface {
    /**
     * 应用对象
     *
     * @var Monkey\App $app
     */
    public $app;

    /**
     * 日志保存目录
     *
     * @var string
     */
    private static $dir;

    /**
     * 日志信息
     *
     * @var array
     */
    private static $sqlErrors = array();

    /**
     * 构造方法
     *
     * @param Monkey\App $app
     * @param mixed|null $config 配置
     */
    public function __construct($app, $config) {
        $this->app = $app;
        self::$dir = dir_format($app->DIR . (isset($config['sql_dir']) ? $config['sql_dir'] : '/logs/sql'));
        self::$dir .= '/' . date("Y-m-d", $app->TIME) . '/' . date("H", $app->TIME);
        $app->shutdown()->register(array($this, 'write'));
    }

    /**
     * 添加一条日志信息
     *
     * @param string|array $data 日志信息
     */
    public function put($data) {
        self::$sqlErrors[] = $data;
    }

    /**
     * 写入日志文件
     * 不需要手动调用
     *
     * @return bool
     */
    public function write() {
        $temp = self::$dir;

        if (!dir_check($temp)) {
            return false;
        }

        $file = $temp . '/' . date("Y-m-d-H-i", $this->app->TIME) . ".log.txt";
        $temp = date('Y-m-d H:i:s', $this->app->TIME);
        //写入文件，记录错误信息
        $content = PHP_EOL . PHP_EOL . PHP_EOL . '===============[date: ' . $temp . ' ]===============' . PHP_EOL;

        foreach (self::$sqlErrors as $i => $sqlError) {
            $content .= PHP_EOL . '[ 第' . $i . '条 ]';

            if (is_array($sqlError)) {
                foreach ($sqlError as $key => $value) {
                    $content .= PHP_EOL . $key . "\t\t : " . $value;
                }

            }
            else {
                if (is_string($sqlError)) {
                    $content .= PHP_EOL . $sqlError;
                }
            }
        }

        error_reporting(0);
        $temp = error_log($content, 3, $file);
        error_reporting($this->app->DEBUG);

        return $temp;
    }
}