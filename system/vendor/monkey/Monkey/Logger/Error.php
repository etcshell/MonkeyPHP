<?php
namespace Monkey\Logger;

/**
 * Error
 * 一般错误日志类
 * @package Monkey\Logger
 */
class Error implements _Interface
{
    private static
        $logDir,
        $logs=array()
    ;
    private
        /**
         * @var \Monkey\App\App
         */
        $app;
    /**
     * 构造方法
     * @param \Monkey\App\App $app
     * @param mixed|null $config 配置
     */
    public function __construct($app,$config)
    {
        $this->app=$app;
        self::$logDir= dir_format($app->DIR.($config['error_dir'] ? $config['error_dir'] : '/logs/error'));
        self::$logDir.='/'.date("Y-m-d",$app->TIME).'/'.date("H",$app->TIME);
        $app->shutdown()->register(array($this,'write'));
    }

    /**
     * 添加一条日志信息
     * @param string|array $data 日志信息
     */
    public function put($data)
    {
        self::$logs[] = $data;
    }

    /**
     * 写入日志文件
     * 不需要手动调用
     * @return bool
     */
    public function write()
    {
        $temp=self::$logDir;
        if(!dir_check($temp)){
            return ;
        }
        $file =self::$logDir.'/'.date("Y-m-d-H-i",$this->app->TIME).".log.txt";
        foreach(self::$logs as $log){
            if(!is_array($log)){
                $this->_write2($log,$file);
                continue;
            }
            if(isset($log['backtrace']) ){
                unset($log['backtrace']);
            }
            if(isset($log['code']) ){
                $this->_write2($log,$temp.'/'.$log['code'].".log.txt");
            }
            else{
                $this->_write2($log,$file);
            }
        }
    }

    private function _write2($log,$file){
        $content=PHP_EOL.PHP_EOL.PHP_EOL;
        if(isset($log['time'])){
            $content.='----[ '.$log['time'].' ]----';
            unset($log['time']);
        }
        else{
            $content.='----[ '.date('Y-m-d H:i:s',$this->app->TIME).' ]----';
        }
        if(is_array($log)){
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
        else{
            $content.=PHP_EOL.$log;
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