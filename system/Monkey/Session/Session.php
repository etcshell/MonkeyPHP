<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Administrator
 * Date: 13-5-21
 * Time: 下午10:51
 * To change this template use File | Settings | File Templates.
 */

namespace Monkey\Session;


use Monkey\_Interface\Component;

class Session implements Component
{
    /**
     * @var \Monkey\Monkey
     */
    private $oMonkey;

    private
        $initialized,
        $config,
        $prefix
    ;

    private function __construct()
    {

    }

    /**
     * 组件初始化注入
     * @param \Monkey\Monkey|null $monkey 依赖类
     * @param mixed|null $config 配置
     * @return mixed
     */
    public function _initialize($monkey = null, $config = null)
    {
        //防止重复初始化
        if($this->initialized)
            return;
        else
            $this->initialized=true;

        //载入配置
        $driver= __NAMESPACE__.'\\Driver\\'.ucfirst(strtolower($config['driver']));
        !$config['expiry'] and $config['expiry']=ini_get('session.gc_maxlifetime');
        $this->oMonkey=$monkey;
        $this->config=$config;
        $this->prefix= $this->getConfig('prefix_key',APP_NAME);

        //载入Session驱动
        $configForDriver=$config[$config['driver']];
        !$configForDriver['expiry'] and $configForDriver['expiry']=$config['expiry'];
        $driver::init($configForDriver);

        ini_set('session.save_handler', 'user');
        session_set_save_handler(
            array($driver, 'open'),
            array($driver, 'close'),
            array($driver, 'read'),
            array($driver, 'write'),
            array($driver, 'destroy'),
            array($driver, 'gc')
        );
        register_shutdown_function('session_write_close');//修复apc的bug

        //验证Session的真伪
        $ck=$monkey->getCookie();
        $tokenKey= $this->getConfig('token_key','mkk');
        $tokenSeed= $this->getConfig('token_seed','TW9ua2V5');//默认种子是 base64_encode('Monkey')
        if(!isset($_COOKIE[session_name()]))
        {//第一次使用Session，直接生成不可逆的token值
            $ck->set($tokenKey, $this->getTokenValue($tokenSeed), $config['expire']);
        }
        else
        {//非首次使用Session，验证token的存在性和真伪性
            $token=$ck->get($tokenKey,false);
            if(!$token || $token!==$this->getTokenValue($tokenSeed))
            {
                error_exit('请别乱来！',2048);
            }
        }

        //启动session
        session_start();
    }

    /**
     * 获取\Monkey\Session\Session组件实例
     * @return \Monkey\Session\Session
     */
    public static function _instance()
    {
        static $self;
        !$self and $self=new self();
        return $self;
    }

    /**
     * 设置Session值
     * @param string $name 键名
     * @param mixed $value 值
     */
    public function set($name, $value)
    {
        $_SESSION[$this->prefix.$name]=$value;
    }

    /**
     * 读取Session值
     * @param string $name 键名
     * @param null $defaultValue 默认值
     * @return mixed
     */
    public function get($name, $defaultValue=null)
    {
        $name=$this->prefix.$name;
        return isset($_SESSION[$name]) ? $_SESSION[$name] : $defaultValue;
    }

    /**
     * 销毁Session
     */
    public function clear()
    {
        session_unset();
        session_destroy();
        $this->oMonkey->getCookie()->set( $this->getConfig('token_key','mkk'), '', -1 );
    }

    /**
     * 生成令牌值
     * @param string $tokenSeed 令牌种子
     * @return string
     */
    private function getTokenValue($tokenSeed)
    {
        return md5(md5(session_id()).$tokenSeed);
    }

    private function getConfig($key, $defaultValue=null)
    {
        return isset($this->config[$key]) ? $this->config[$key] : $defaultValue;
    }

}