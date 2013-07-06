<?php
/**
 * Created by JetBrains PhpStorm.
 * User: hyiyou
 * Date: 13-5-5
 * Time: 上午10:02
 * To change this template use File | Settings | File Templates.
 */

namespace Monkey\Logger\Type;


class Error implements _Interface
{
    private static
        $dir,
        $logs=array()
    ;
    /**
     * 构造方法
     * @param \Monkey\Monkey|null $monkey 依赖类
     * @param mixed|null $config 配置
     */
    public function __construct($monkey,$config)
    {
        self::$dir= $config['dir'] ? $config['dir'] : APP.'/logs/error';
        self::$dir.='/'.date("Y-m-d",TIME).'/'.date("H",TIME);
        $monkey->getShutdown()->registerCallbalk(array($this,'write'));
    }

    /**
     * 添加一条日志信息
     * @param string|array $data 日志信息
     */
    public function put(array $data)
    {
        self::$logs[] = $data;
    }

    /**
     * 批量添加日志信息
     * @param array $datas 日志信息
     */
    public function add(array $datas)
    {
        self::$logs = self::$logs + $datas;
    }

    /**
     * 写入日志文件
     * 不需要手动调用
     * @return bool
     */
    public function write()
    {
        $temp=self::$dir;
        if(!dir_check($temp))
        {
            return false;
        }
        $file =$temp.'/'.date("Y-m-d-H-i",TIME).".log.txt";
        $temp=date('Y-m-d H:i:s',TIME);
        //写入文件，记录错误信息
        $content=PHP_EOL.PHP_EOL.PHP_EOL
            .'===============[date: '.$temp.' ]==============='.PHP_EOL;
        $linel=PHP_EOL.'---------------[ '.$temp.' 第 ';
        $liner=' 条 ]---------------';
        foreach(self::$logs as $i=> $log)
        {
            $content.=$linel.$i.$liner;
            if(is_array($log))
            {
                foreach($log as $key=>$value)
                {
                    $content.=PHP_EOL.$key.'\t\t : '.$value;
                }
            }
            else if( is_string($log) )
            {
                $content.=PHP_EOL.$log;
            }
        }
        error_reporting(0);
        $temp=error_log($content, 3, $file) ;
        error_reporting(DEBUG);
        return $temp;
    }
}