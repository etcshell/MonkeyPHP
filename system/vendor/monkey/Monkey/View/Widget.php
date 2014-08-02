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

/**
 * Abstract Widget
 *
 * 挂件抽象基类
 *
 * @package Monkey\View
 */
abstract class Widget extends View {
    /**
     * 是否缓存挂件渲染结果
     *
     * @var bool
     */
    protected $cacheMe = false;

    /**
     * 渲染结果保存时间
     *
     * @var int
     */
    protected $expire = 0;

    /**
     * 获取挂件
     *
     * @return string
     */
    abstract public function getHtml();

    /**
     * 渲染
     *
     * @param string $tplFilename 模板文件名，相对于模板下挂件根目录。
     *
     * @return string
     */
    public function render($tplFilename) {
        return parent::render('/widget' . $tplFilename, false);
    }

} 