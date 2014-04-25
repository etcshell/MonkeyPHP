<?php
namespace Monkey;

/**
 * Exception
 * 异常接管类
 * @package Monkey
 */
class Exception extends \Exception{

    private static
        $_errorInfo,
        $_title = array(
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
        $app=Monkey::app();
        $backtrace=debug_backtrace();
        $info=$backtrace[0];
        $lines=file($info['file']);
        $info['source']=trim($lines[$info['line']-1]);
        $x='throw new Monkey\Exception(';
        $y=substr($info['source'],0,strlen($x));
        if($y==$x){
            array_shift($backtrace);
            $info=$backtrace[0];
            $lines=file($info['file']);
            $info['source']=trim($lines[$info['line']-1]);
        }
        $x='throw new Monkey\BreakException(';
        $y=substr($info['source'],0,strlen($x));
        if($y==$x){
            array_shift($backtrace);
            $info=$backtrace[0];
            $lines=file($info['file']);
            $info['source']=trim($lines[$info['line']-1]);
        }
        $info['time']=date('Y-m-d H:i:s',$app->TIME);
        $info['title']= isset(self::$_title[$this->code])? self::$_title[$this->code] : '应用程序错误';
        $info['code']=$this->code;
        $info['path']= $app->request()->getUri();
        $info['ip'] = $app->request()->getIP();
        $info['message']=$this->message;
        foreach ($backtrace as $key=>$value){
            $lines=file($value['file']);
            $backtrace[$key]['source']=trim($lines[$value['line']-1]);
            if(isset($backtrace[$key]['object'])) unset($backtrace[$key]['object']);
        }
        $info['backtrace']=print_r($backtrace, true);
        self::$_errorInfo=$info;
        !$app->DEBUG and $app->logger()->error(self::$_errorInfo);
    }

    public static function getErrorInfo(){
        return self::$_errorInfo;
    }
}