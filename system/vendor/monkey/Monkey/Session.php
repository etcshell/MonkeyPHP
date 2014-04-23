<?php
namespace Monkey;
/**
  * session接口
  */
abstract class Session
{
    protected
        /**
         * @var \Monkey\App\App
         */
        $app,
        $config,
        $prefix,
        $_expire=3600
    ;
    /**
     * @param \Monkey\App\App $app
     */
    public function __construct($app)
    {

    }
    protected function init()
    {
        $config=$this->config;
        $this->prefix= empty($config['prefix']) ? $this->app->NAME : $config['prefix'];
        $this->_expire= isset($config['expire']) ? $config['expire'] : ini_get('session.gc_maxlifetime');

        ini_set('session.save_handler', 'user');
        session_set_save_handler(
            array($this, 'open'),
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc')
        );
        register_shutdown_function('session_write_close');//修复apc的bug
        //启动session
        session_start();
    }
    /**
     * 打开Session文件
     * @param string $path
     * @param string $name
     * @return boolean
     */
    abstract public function open($path, $name);
    /**
     * 关闭Session文件
     * @return boolean
     */
    abstract public function close();
    /**
     * 从session中读取数据
     * @param	string	$sessionid	session的ID
     * @return 	mixed	返回session中对应的数据
     */
    abstract public function read($sessionid);
    /**
     * 向session中添加数据
     * @param string $sessionid session的ID
     * @param string $data 序列化的Session变量
     * @return boolean
     */
    abstract public function write($sessionid, $data);
    /**
     * 销毁Session
     * @param string $sessionid session的ID
     * @return boolean
     */
    abstract public function destroy($sessionid);
    /**
     * 回收过期的session
     * @param integer $lifetime
     * @return boolean
     */
    abstract public function gc($lifetime);

    protected function _storageKey($sessionid){
        return $this->prefix.'_session_'.$sessionid;
    }
}