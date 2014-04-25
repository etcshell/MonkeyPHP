<?php
namespace Monkey;

use Monkey\App;

class Monkey {
    public static
        $DIR=__DIR__
    ;
    private static
        $app,
        $VERSION= '2014.04.01'
    ;

    /**
     * 创建应用程序实例
     * @param $appName
     * @param $staticDir
     * @return \Monkey\App\App
     * @throws \Exception
     */
    public static function createApp($appName,$staticDir)
    {
        if(self::$app!==null){
            throw new \Exception('每个请求只能创建一个应用程序！');
        }
        $appDir=dirname(dirname(dirname(__DIR__))).DIRECTORY_SEPARATOR.'apps';
        $creator=new App\AppCreator($appName,$appDir,$staticDir);
        self::$app=$creator->getApp();
        unset($creator);
        return self::$app;
    }

    /**
     * 获取应用程序
     * @return \Monkey\App\App
     */
    public static function app()
    {
        return self::$app;
    }

    /**
     * 获取版本号
     * @return string
     */
    public static function version()
    {
        return self::$VERSION;
    }
}