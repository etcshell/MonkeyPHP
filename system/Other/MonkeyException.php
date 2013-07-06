<?php

/**
 * 异常类\MonkeyException
 * @package    \
 * @author     HuangYi
 * @copyright  Copyright (c) 2011-07-01——2013-03-30
 * @license    New BSD License
 * @version    $Id: \Framework\MonkeyException.php 版本号 2013-03-30 $
 */
final class MonkeyException extends Exception{
    private static $_title = array(
        1=> '致命错误(E_ERROR)',
        2 => '警告(E_WARNING)',
        4 => '语法解析错误(E_PARSE)',
        8 => '提示(E_NOTICE)',
        16 => 'E_CORE_ERROR',
        32 => 'E_CORE_WARNING',
        64 => '编译错误(E_COMPILE_ERROR)',
        128 => '编译警告(E_COMPILE_WARNING)',
        256 => '致命错误(E_USER_ERROR)',
        512 => '警告(E_USER_WARNING)',
        1024 => '提示(E_USER_NOTICE)',
        2047 => 'E_ALL',
        2048 => 'E_STRICT'
    );
    public function __construct($message,$code=0,$file=null,$line=null){
        parent::__construct($message,$code);
        $this->file= is_null($file)? parent::getFile() : $file;
        $this->line= is_null($line)? parent::getLine() : $line;
        $this->fillInfo();
    }
    private function fillInfo() {
        $info=array();
        $info['time']=date('Y-m-d H:i:s',TIME);
        $info['title']= isset(self::$_title[$this->code])? self::$_title[$this->code] : '应用程序错误';
        $info['code']=$this->code;
        //$info['path']= o_request()->getUri();
        //$info['ip'] = ip();
        $info['message']=$this->message;
        $info['file']=$this->file;
        $info['line']=$this->line;
        $lines=file($this->file);
        $info['source']=$lines[$this->line-1];
        $backtrace=debug_backtrace();
        foreach ($backtrace as $key=>$value){
            $lines=file($value['file']);
            $backtrace[$key]['source']=trim($lines[$value['line']-1]);
            if(isset($backtrace[$key]['object'])) unset($backtrace[$key]['object']);
        }
        $info['backtrace']=print_r($backtrace, true);
        o_error()->put($info);
        return;
    }
}