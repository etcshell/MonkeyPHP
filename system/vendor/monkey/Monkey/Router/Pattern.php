<?php
/**
 * Project MonkeyPHP
 *
 * PHP Version 5.3.9
 *
 * @package   Monkey\Router
 * @author    黄易 <582836313@qq.com>
 * @version   GIT:<git_id>
 */
namespace Monkey\Router;

use Monkey;

/**
 * Class Pattern
 *
 * 路由匹配类
 *
 * @package Monkey\Router
 */
class Pattern {

    /**
     * 应用对象
     *
     * @var Monkey\App
     */
    public $app;

    /**
     * 路由映射表的存储文件
     *
     * @var string
     */
    private $mapFile;

    /**
     * 编译好的路由映射匹配表的存储文件
     *
     * @var string
     */
    private $patternFile;

    /**
     * 编译好的路由映射匹配表
     *
     * @var array
     */
    private $patterns = array();

    /**
     * 匹配选项
     *
     * @var array
     */
    private $patternOption = array();

    /**
     * 构造方法
     *
     * @param Monkey\App $app
     * @param string $config 配置
     */
    public function __construct($app, $config) {
        $this->app = $app;
        $this->patternOption = $config['pattern_option'];
        $this->mapFile = $app->DIR . ($config['map_file'] ? $config['map_file'] : '/data/router.map.php');
        $this->patternFile = $app->DIR . '/temp/router/pattern.php';
        $this->loadPattern();
    }

    /**
     * 匹配路径
     *
     * @param string $method 请求方法
     * @param string $path 请求路径
     *
     * @return array(
     *      'router'=>  路由器名
     *      //下面的参数是可能有的
     *      'params'=>array(  参数对
     *          'paramName' => 'paramValue',
     *          ...
     *      )
     * )
     */
    public function matchPath($method, $path) {
        $method = strtolower($method);
        $path = '/' . trim($path, '/');
        $extArray = array('.php' => '.php', '.html' => '.html');
        $ext = strtolower(strrchr($path, '.'));

        if (isset($extArray[$ext])) {
            $path = substr($path, 0, 0 - strlen($extArray[$ext]));
        }

        if (isset($this->patterns['static'][$method . $path])) {
            return array('router_name' => $this->patterns['static'][$method . $path]);
        }

        $key = $method . '-' . substr_count($path, '/');

        if (!isset($this->patterns[$key])) {
            return array();
        }

        foreach ($this->patterns[$key] as $pattern) {
            if (strpos($path, $pattern['prefix']) === 0 and preg_match('#^' . $pattern['prefix'] . $pattern['pattern'] . '$#', $path, $matches)
            ) {
                array_shift($matches);
                return array('router_name' => $pattern['router'], 'params' => array_combine(explode(':', $pattern['params']), $matches));
            }
        }

        return array();
    }

    /**
     * 还原路径
     *
     * @param string $pattern 路径模式  get/test/abc-{zh|en}/blog/{year}-([1-9]\d*):language:year:id
     * 或者不要请求方法及节数：
     * /test/abc-{zh|en}/blog/{year}-([1-9]\d*):language:year:id
     * 当$parameters的参数顺序和路径模式中的参数顺序一致时，$pattern可以省略参数列表：
     * get/test/abc-{zh|en}/blog/{year}-([1-9]\d*)
     * 最精简的形式：
     * /test/abc-{zh|en}/blog/{year}-([1-9]\d*)
     *
     * @param string $parameters 参数  array('language'=>'en','year'=>'2014','id'=4025)
     *
     * @return string  /test/abc-en/blog/2014-4025
     */
    public function packagePath($pattern, $parameters = null) {
        $pattern = strstr($pattern, '/'); // /test/abc-{zh|en}/blog/{year}-([1-9]\d*):language:year:id

        if (!$parameters) {
            return $pattern;
        }

        if (strpos($pattern, ':') === false) {
            $pattern = str_replace(array_keys($this->patternOption), $this->patternOption, $pattern);

            foreach ($parameters as $parameter) {
                $pattern = preg_replace('/\([^\)]+\)/', $parameter, $pattern, 1);
            }

        }
        else {
            $names = explode(':', $pattern);
            $pattern = array_shift($names);
            $pattern = str_replace(array_keys($this->patternOption), $this->patternOption, $pattern);

            foreach ($names as $name) {
                $pattern = preg_replace('/\([^\)]+\)/', $parameters[$name], $pattern, 1);
            }
        }

        return $pattern;
    }

    /**
     * 清除编译好的路由匹配表
     */
    public function clearPatternCompiled() {
        file_exists($this->patternFile) and unlink($this->patternFile);
    }

    private function loadPattern() {
        if (file_exists($this->patternFile) and filemtime($this->patternFile) >= filemtime($this->mapFile)) {
            $this->patterns = unserialize(file_get_contents($this->patternFile));

        }
        else {
            $patterns = include($this->mapFile);

            foreach ($patterns as $pattern => $router) {
                $this->addPattern($pattern, $router);
            }

            dir_check(dirname($this->patternFile));
            file_put_contents($this->patternFile, serialize($this->patterns), LOCK_EX); //echo '<br/>保存扫描结果到缓存文件中...<br/>';
        }
    }

    /**
     * 添加匹配
     * @param string $pattern 匹配，格式为 method/pattern 如：
     *    空匹配：       get/  或  /   其中get可以省略，其它如post则不能省略，下同
     *    含路径匹配：    get/article/list   或  /article/list
     *    含变量匹配：    get/{zh|en}/blog/{i}:language:id  3表示路由节数（下同），zh|en表示可选值，i表示值类型（i为整数，s为字符串），language和id表示参数名（下同）
     *    含固定变量匹配： post/article/{year}/{month}/{s}:year:month:title   {year}和{month}表示已配置匹配变量。
     *    含正则匹配：    get/(zh|en)/blog/([1-9]\d*):language:id     每节括号内为正则表达示，括号不能嵌套，一个变量对应一对括号
     *    含变量、固定变量、正则匹配： get/{zh|en}/blog/{year}-([1-9]\d*):language:year:id   每节括号内为正则表达示，括号不能嵌套，一个变量对应一对括号
     * @param string $router
     */
    private function addPattern($pattern, $router) {
        $pattern[0] == '/' and $pattern = 'get' . $pattern;
        //        如果路由映射表严格书写则不需要下面这两行
        //        $pattern=trim($pattern,'/');
        //        strpos($pattern,'/')===false  and $pattern.='/';

        if (strpos($pattern, ':') === false) {
            $this->patterns['static'][$pattern] = $router; //get/article/list
            return;
        }

        //get/test/abc-{zh|en}/blog/{year}-([1-9]\d*):language:year:id
        $patterns = explode(':', $pattern, 2); //get/test/abc-{zh|en}/blog/{year}-([1-9]\d*)   language:year:id
        $params = $patterns[1]; //language:year:id
        $key = strstr($patterns[0], '/', true); //get
        $key .= '-' . (substr_count($patterns[0], '/') - substr_count($patterns[0], '\/')); //get-4
        $pattern = strstr($patterns[0], '/'); //      /test/abc-{zh|en}/blog/{year}-([1-9]\d*)
        $prefix = explode('{', $pattern, 2); //    /test/abc-    zh|en}/blog/{year}-([1-9]\d*)
        $prefix = explode('(', $prefix[0], 2); //    /test/abc-
        $prefix = substr($prefix[0], 0, strrpos($prefix[0], '/') + 1); //含开头和末尾的/   /test/
        $pattern = substr($pattern, strlen($prefix)); //  abc-{zh|en}/blog/{year}-([1-9]\d*)
        $pattern = str_replace(array_keys($this->patternOption), $this->patternOption, $pattern); //{year}等简记名替换   abc-(zh|en)/blog/([1-2]\d{3})-([1-9]\d*)
        strtolower(strrchr($pattern, '.')) == '.html' and $pattern = substr($pattern, 0, -5);
        //get/test/abc-{zh|en}/blog/{year}-([1-9]\d*):language:year:id
        //解析为以get-4为Key的二级数组：
        $this->patterns[$key][] = array('prefix' => $prefix, //   /test/
            'pattern' => $pattern, //    abc-(zh|en})/blog/([1-2]\d{3})-([1-9]\d*)
            'params' => $params, //   language:year:id
            'router' => $router, //  $router
        );
    }
}