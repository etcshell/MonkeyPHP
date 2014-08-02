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
 * Class Cache
 *
 * html伪静态缓存类
 *
 * @package Monkey\View
 */
class Cache {
    /**
     * 应用对象
     *
     * @var Monkey\App
     */
    public $app;

    /**
     * 缓存真实的物理绝对路径
     *
     * @var string
     */
    private $cacheFile;

    /**
     * 缓存有效期文件
     *
     * @var string
     */
    private $expireFile;

    /**
     * 构造方法
     *
     * @param Monkey\App $app
     * @param string|null $cacheFile 默认使用路由作为缓存文件名（不含后缀名，路径相对于应用程序目录）
     */
    public function __construct($app, $cacheFile = null) {
        $this->app = $app;
        $this->setFile($cacheFile ? $cacheFile : $app->router()->getPath());
    }

    /**
     * 设置缓存文件
     *
     * @param string $file 相对于临时文件夹
     *
     * @return $this
     */
    public function setFile($file) {
        $this->cacheFile = $this->app->DIR . '/temp/html' . $file . '.php';
        $this->expireFile = $this->cacheFile . '_expire.php';
        dir_check(dirname($this->cacheFile));

        return $this;
    }

    /**
     * 设置html缓存
     *
     * @param string $html //html内容
     * @param int $expire 缓存时间，默认保存时间为0（永久保存），24小时为86400*1
     *
     * @return bool
     */
    public function store($html, $expire = 0) {
        file_put_contents($this->cacheFile, $html, LOCK_EX);

        if ($expire != 0) {
            $expire = '<?php' . PHP_EOL . 'return ' . ($expire + $this->app->TIME) . ' ;';
            file_put_contents($this->expireFile, $expire, LOCK_EX);
        }

        return true;
    }

    /**
     * 读取html缓存
     *
     * @return string
     */
    public function fetch() {
        if ($this->exists()) {
            return require $this->cacheFile;
        }
        else {
            return '';
        }
    }

    /**
     * 载入html缓存并直接显示
     *
     * @return bool
     *
     * 成功返回true，失败返回false
     */
    public function load() {
        if (!$this->exists()) {
            return FALSE;
        }

        require $this->cacheFile;

        return TRUE;
    }

    /**
     * 删除某个html缓存单元
     *
     * @return bool
     *
     * 成功返回true，失败返回false
     */
    public function delete() {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }

        if (file_exists($this->expireFile)) {
            unlink($this->expireFile);
        }
    }

    /**
     * 清除所有html缓存
     *
     * @return boolean
     */
    public function clear() {
        return dir_delete($this->app->DIR . '/temp/html');
    }

    /**
     * 是否存在某个或者某些html缓存
     *
     * @return bool|string
     */
    private function exists() {
        if (!file_exists($this->cacheFile)) {
            $this->delete();
            return FALSE;
        }

        if (!file_exists($this->expireFile)) {
            return TRUE;
        }

        $expireTime = include $this->expireFile;

        if ($expireTime >= $this->app->TIME) {
            return TRUE;
        }

        $this->delete();

        return FALSE;
    }
}