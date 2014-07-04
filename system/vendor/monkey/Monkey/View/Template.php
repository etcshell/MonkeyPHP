<?php
/**
 * Project MonkeyPHP
 *
 * PHP Version 5.3.9
 *
 * @package   Monkey\View
 * @author    黄易 <582836313@qq.com>
 * @version   GIT:<git_id>
 */
namespace Monkey\View;

use Monkey;

/**
 * Class Template
 *
 * 模板类
 *
 * @package \Monkey\View
 */
class Template
{
    /**
     * 应用对象
     *
     * @var Monkey\App
     */
    public $app;

    /**
     * 普通替换标签
     *
     * @var array
     */
    private static $replaceTag = array();

    /**
     * 回调替换标签
     *
     * @var array
     */
    private static $callbackTag = array();

    /**
     * 模板根目录
     *
     * @var string
     */
    private static $tplRoot;

    /**
     * 模板编译后的保存根目录
     *
     * @var string
     */
    private static $compiledRoot;

    /**
     * 赋值变量列表
     *
     * @var array
     */
    public $variable = array();

    /**
     * 构造方法
     *
     * @param Monkey\App $app
     * @param array $config
     */
    public function __construct($app, array $config)
    {
        if (!self::$replaceTag) {
            Tag::$appName = $app->NAME;
            self::$replaceTag = Tag::getReplaceTag();
            self::$callbackTag = Tag::getCallbackTag();
            $config = $config + array(
                    'template_root' => '/template',
                    'compiled_root' => '/template_compiled'
                );
            self::$tplRoot = $app->DIR . $config['template_root'];
            self::$compiledRoot = $app->TEMP . $config['compiled_root'];
            dir_check(self::$compiledRoot);
        }

        $this->app = $app;
    }

    /**
     * 模板赋值
     *
     * @param string $name 变量名
     * @param mixed $value 变量值
     */
    public function assign($name, $value)
    {
        $this->variable[$name] = $value;
    }

    /**
     * 清空已赋值变量
     */
    public function clearAssigned()
    {
        $this->variable = null;
        $this->variable = array();
    }

    /**
     * 载入模板
     *
     * @param string $tplFilename 模板文件名，相对于模板根目录。
     * @param bool $display 是否直接显示，默认为否
     *
     * @return void|string 当直接显示时无返回，关闭显示后返回字符串
     */
    public function loading($tplFilename, $display = false)
    {
        $tplFilename[0] !== '/' and $tplFilename = '/' . $tplFilename;
        $tplFile = self::$tplRoot . $tplFilename;

        if (!file_exists($tplFile)) {
            new \Exception(':模板文件【' . $tplFilename . '】不存在');
        }

        $compFile = self::$compiledRoot . $tplFilename;

        if ($this->app->DEBUG
            || !file_exists($compFile)
            || (filemtime($compFile) < filemtime($tplFile))
        ) {
            $compiled = $this->_compile($tplFile); //获取经编译后的内容
            dir_check(dirname($compFile)); //保证编译后目录存在
            file_put_contents($compFile, $compiled, LOCK_EX);
        }

        extract($this->variable, EXTR_OVERWRITE);

        //执行模板
        if ($display) {
            include $compFile;
            return '';

        } else {
            ob_start();
            include $compFile;
            return ob_get_clean();
        }

    }

    /**
     * 清空当前模板根目录下的编译缓存
     *
     * @return $this
     */
    public function clearCompiled()
    {
        dir_delete(self::$compiledRoot);
        return $this;
    }

    /**
     * 编译模板
     *
     * @param $tplFile
     *
     * @return mixed|string
     */
    private function _compile($tplFile)
    {
        if (!file_exists($tplFile)) {
            new \Exception(':模板文件【' . str_replace(self::$tplRoot, '', $tplFile) . '】不存在');
        }

        $template = file_get_contents($tplFile); //读取模板内容

        //解析直接替换标签
        foreach (self::$replaceTag as $tag) {
            $template = preg_replace($tag[0], $tag[1], $template);
        }

        //解析回调替换标签
        foreach (self::$callbackTag as $tag) {
            $template = preg_replace_callback($tag[0], $tag[1], $template);
        }

        //解析模板包含（两种方式）
        //{include="/template/login.html" type=style}
        $template = preg_replace_callback(
            '/{include=(\'|")(\S+)\1\s+type=(style|common)}/i',
            array($this, '_inc_tpl'),
            $template
        );

        //去掉剩余的注释，提高加载速度
        if (!$this->app->DEBUG) {
            $template = preg_replace("/<!--(.+?)-->/s", '', $template);
        }

        return $template;
    }

    private function _inc_tpl($matches)
    {
        $tplFilename = $matches[2];
        $tplFilename[0] == '/' and $tplFilename = '/' . $tplFilename;

        return $this->loading(self::$tplRoot . $tplFilename);
    }
}