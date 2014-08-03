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
 * Class Error
 *
 * 一般错误日志类
 *
 * @package Monkey\Logger
 */
class Error implements ErrorInterface {

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
    private static $logDir;

    /**
     * 日志信息
     *
     * @var array
     */
    private static $logs = array();

    /**
     * 构造方法
     *
     * @param Monkey\App $app
     * @param mixed|null $config 配置
     */
    public function __construct($app, $config) {
        $this->app = $app;
        self::$logDir = dir_format($app->DIR . (isset($config['error_dir']) ? $config['error_dir'] : '/logs/error'));
        self::$logDir .= '/' . date("Y-m-d", $app->TIME) . '/' . date("H", $app->TIME);
        $app->shutdown()->register(array($this, 'write'));
    }

    /**
     * 添加一条日志信息
     *
     * @param string|array $data 日志信息
     */
    public function put($data) {
        self::$logs[] = $data;
    }

    /**
     * 写入日志文件
     * 不需要手动调用
     *
     * @return bool
     */
    public function write() {
        $temp = self::$logDir;

        if (!dir_check($temp)) {
            return false;
        }

        $file = self::$logDir . '/' . date("Y-m-d-H-i", $this->app->TIME) . ".log.txt";

        foreach (self::$logs as $log) {
            if (!is_array($log)) {
                $this->write2($log, $file);
                continue;
            }

            if (isset($log['code'])) {
                $this->write2($log, $temp . '/' . $log['code'] . ".log.txt");
            }
            else {
                $this->write2($log, $file);
            }
        }

        return true;
    }

    private function write2($log, $file) {
        $content = PHP_EOL . PHP_EOL . PHP_EOL;

        if (isset($log['time'])) {
            $content .= '----[ ' . $log['time'] . ' ]----';
            unset($log['time']);
        }
        else {
            $content .= '----[ ' . date('Y-m-d H:i:s', $this->app->TIME) . ' ]----';
        }

        if (is_array($log)) {
            foreach ($log as $key => $value) {

                if (is_array($value)) {
                    $content .= PHP_EOL . '[' . $key . "] array(";

                    foreach ($value as $k => $v) {
                        $content .= PHP_EOL . "\t\t[" . $k . "]=>" . $v;
                    }

                    $content .= PHP_EOL . ')';

                }
                else {
                    $content .= PHP_EOL . '[' . $key . "] " . $value;
                }
            }

        }
        else {
            $content .= PHP_EOL . $log;
        }

        error_reporting(0);
        error_log($content, 3, $file);
        error_reporting($this->app->DEBUG);
    }

}
/*
    public function _write()
    {
        $temp=self::$logDir;
        if(!dir_check($temp)){
            return false;
        }
        $file =$temp.'/'.date("Y-m-d-H-i",$this->app->TIME).".log.txt";
        $temp=date('Y-m-d H:i:s',$this->app->TIME);
        //写入文件，记录错误信息
        $content=PHP_EOL.PHP_EOL.PHP_EOL
            .'===============[date: '.$temp.' ]===============';
        foreach(self::$logs as $log){
            if(is_array($log)){
                if(isset($log['backtrace']) ){
                    unset($log['backtrace']);
                }
                if(isset($log['time'])){
                    $content.=PHP_EOL.'----[ '.$log['time'].' ]----';
                    unset($log['time']);
                }
                else{
                    $content.=PHP_EOL.'----[ '.$temp.' ]----';
                }
                foreach($log as $key=>$value){
                    if(is_array($value)){
                        $content.=PHP_EOL.'['.$key."] array(";
                        foreach($value as $k=>$v){
                            $content.=PHP_EOL."\t\t[".$k."]=>".$v;
                        }
                        $content.=PHP_EOL.')';
                    }
                    else{
                        $content.=PHP_EOL.'['.$key."] ".$value;
                    }
                }
            }
            else if( is_string($log) ){
                $content.=PHP_EOL.'----[ '.$temp.' ]----'.PHP_EOL.$log;
            }
        }
        error_reporting(0);
        $temp=error_log($content, 3, $file);
        error_reporting(app()->getDebug());
        return $temp;
    }
*/