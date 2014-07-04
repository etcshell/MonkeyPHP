<?php
/**
 * Project MonkeyPHP
 *
 * PHP Version 5.3.9
 *
 * @package   Monkey\Session
 * @author    黄易 <582836313@qq.com>
 * @version   GIT:<git_id>
 */
namespace Monkey\Session;

use Monkey;
use Monkey\Session;

/**
 * Class Apc
 *
 * Session的APC实现
 *
 * @package Monkey\Session
 */
class Apc extends Session
{
    /**
     * 构造方法
     *
     * @param Monkey\App $app
     *
     * @throws \Exception
     */
    public function __construct($app)
    {
        if (!extension_loaded('apc')) {
            new \Exception('会话出错:没有安装APC扩展。', 2048, __FILE__, __LINE__);
        }

        $this->app = $app;
        $this->config = $app->config()->getComponentConfig('session', 'apc');
        $this->start();
    }

    /**
     * 打开Session文件
     *
     * @param string $path
     * @param string $name
     *
     * @return boolean
     */
    public function open($path, $name)
    {
        //因为没有用文件存储Session，所以用不着
        return true;
    }

    /**
     * 关闭Session文件
     *
     * @return boolean
     */
    public function close()
    {
        //因为没有用文件存储Session，所以用不着
        return true;
    }

    /**
     * 从session中读取数据
     *
     * @param    string $sessionId session的ID
     *
     * @return    mixed    返回session中对应的数据
     */
    public function read($sessionId)
    {
        $out = apc_fetch($this->_storageKey($sessionId));
        if ($out === FALSE) {
            return '';
        } else {
            return $out;
        }
    }

    /**
     * 向session中添加数据
     *
     * @param string $sessionId session的ID
     * @param string $data 序列化的Session变量
     *
     * @return boolean
     */
    public function write($sessionId, $data)
    {
        return apc_store($this->_storageKey($sessionId), $data, $this->expire);
    }

    /**
     * 销毁Session
     *
     * @param string $sessionId session的ID
     *
     * @return boolean
     */
    public function destroy($sessionId)
    {
        return apc_delete($this->_storageKey($sessionId));
    }

    /**
     * 回收过期的session
     *
     * @param integer $lifetime
     *
     * @return boolean
     */
    public function gc($lifetime)
    {
        //无需额外回收,apc有自己的过期回收机制
        return true;
    }
}