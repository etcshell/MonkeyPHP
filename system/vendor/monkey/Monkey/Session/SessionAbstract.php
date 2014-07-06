<?php
/**
 * Project MonkeyPHP
 *
 * PHP Version 5.3.9
 *
 * @package   Monkey
 * @author    黄易 <582836313@qq.com>
 * @version   GIT:<git_id>
 */
namespace Monkey\Session;

/**
 * Abstract Class Session
 *
 * session抽象父类
 *
 * @package Monkey
 */
abstract class SessionAbstract
{
    /**
     * 防冲突前缀
     *
     * @var string
     */
    protected $prefix;

    /**
     * 过期时间
     *
     * @var int
     */
    protected $expire = 3600;

    /**
     * 应用实例
     *
     * @var App
     */
    protected $app;

    /**
     * 配置
     *
     * @var array
     */
    protected $config = array();

    /**
     * 构造方法
     *
     * @param App $app
     */
    public function __construct($app)
    {

    }

    /**
     * 启动Session
     */
    protected function start()
    {
        //设置
        $config = $this->config;
        $this->prefix = (isset($config['prefix']) ? $config['prefix'] : $this->app->NAME) . '_session_';
        $this->expire = isset($config['expire']) ? $config['expire'] : ini_get('session.gc_maxlifetime');

        //注册Session处理器
        ini_set('session.save_handler', 'user');
        session_set_save_handler(
            array($this, 'open'),
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc')
        );

        //修复apc的bug
        register_shutdown_function('session_write_close');

        //启动session
        session_start();
    }

    /**
     * 打开Session文件
     *
     * @param string $path
     * @param string $name
     *
     * @return boolean
     */
    abstract public function open($path, $name);

    /**
     * 关闭Session文件
     *
     * @return boolean
     */
    abstract public function close();

    /**
     * 从session中读取数据
     *
     * @param    string $sessionId session的ID
     *
     * @return    mixed    返回session中对应的数据
     */
    abstract public function read($sessionId);

    /**
     * 向session中添加数据
     *
     * @param string $sessionId session的ID
     * @param string $data 序列化的Session变量
     *
     * @return boolean
     */
    abstract public function write($sessionId, $data);

    /**
     * 销毁Session
     *
     * @param string $sessionId session的ID
     *
     * @return boolean
     */
    abstract public function destroy($sessionId);

    /**
     * 回收过期的session
     *
     * @param integer $lifetime
     *
     * @return boolean
     */
    abstract public function gc($lifetime);

    /**
     * 生成存储键
     *
     * @param $sessionId
     *
     * @return string
     */
    protected function _storageKey($sessionId)
    {
        return $this->prefix . $sessionId;
    }
}