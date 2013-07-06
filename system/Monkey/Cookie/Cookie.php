<?php
namespace Monkey\Cookie;

use Monkey;

/**
 * \Monkey\Cookie\Cookie
 * @category   cookie工具
 * @package    \Monkey\Cookie
 * @author     HuangYi
 * @copyright  Copyright (c) 2012-4-1——至今
 * @license    New BSD License
 * @version    $Id: \Monkey\Cookie\Cookie.class.php 版本号 2013-1-1  $
 *
 */
final class Cookie implements Monkey\_Interface\Component
{
    private
        $defPrefix = "mk_", //cookie前缀
        $defExpire = 2592000, //cookie时间 30天
        $defDomain = '',
        $languageKey='MonkeyLanguage',
        $newCookies;//新建的cookie值
    /**
     * 构造函数
     */
    private function __construct(){    }

    /**
     * 组件初始化注入
     * @param \Monkey\Monkey|null $monkey 依赖类
     * @param mixed|null $config 配置
     * @return mixed
     */
    public function _initialize($monkey = null, $config = null)
    {
        $this->defPrefix= $config['defPrefix'];
        $this->defExpire= $config['defExpire'];
        $this->defDomain= $config['defDomain'];

        if(isset($_SERVER['HTTP_X_FORWARDED_HOST']))//IIS
        $this->defDomain= $_SERVER['HTTP_X_FORWARDED_HOST'];
        elseif(isset($_SERVER['HTTP_HOST']))//Other
        $this->defDomain= $_SERVER['HTTP_HOST'];

    }

    /**
     * 获取Cookie实例
     * @return \Monkey\Cookie\Cookie
     */
    public static function _instance(){
        static $self;
        !$self and $self=new self();
        return $self;
    }

    /**
     * 获取某cookie变量的值
     * 获取的数值是进过64decode解密的,注:参数支持数组
     * @param string $name   cookie变量名
     * @param mixed|null $defaultValue   cookie变量值
     * @return mixed|null
     */
    public  function get($name, $defaultValue = null) {
        $name=$this->defPrefix.$name;
        if(isset($this->newCookies[$name]) )
        {
            if($this->newCookies[$name]['expire']>TIME)
                $defaultValue=$this->newCookies[$name];
        }
        elseif(isset($_COOKIE[$name]))
        {
            $defaultValue=unserialize(base64_decode($_COOKIE[$name]));
        }
        return $defaultValue;
    }

    /**
     * 获取新建cookies
     * @return array
     */
    public function getNewCookies()
    {
        return $this->newCookies;
    }

    /**
     * 设置一个Cookie
     * 注:这里设置的cookie值是经过64code加密过的,要想获取需要解密.参数支持数组（用本类的get方法）
     * @param string $name
     * @param string $value
     * @param int|null $expire 持续的有效时间,默认为见配置.设置为0或负数相当于删除它
     * @param string $path 起作用的访问目录
     * @param string $domain 起作用的主域名
     * @param bool $secure 是否是https协议才能使用
     * @param bool $httpOnly 是否只允许服务器使用这个Cookie值，为真时浏览器中的js不能访问
     */
    public function set($name, $value, $expire = null, $path = '/', $domain = '', $secure = false, $httpOnly = false)
    {
        $name=$this->defPrefix.$name;
        if ($expire === null)
        {
            $expire= $this->defExpire;
        }
        else
        {
            $expire= is_numeric($expire) ? (int)$expire : 0;
        }
        if(isset($this->newCookies[$name]) && $expire<1)
        {
            unset($this->newCookies[$name]);
        }
        else
        {
            $this->newCookies[$name] = array(
                'name'     => $name,
                'value'    => base64_encode(serialize($value)),
                'expire'   => $expire ? $expire+TIME : 0,
                'path'     => $path,
                'domain'   => $domain ? $domain : $this->defDomain,
                'secure'   => $secure ? true : false,
                'httpOnly' => $httpOnly,
            );
        }
    }

    /**
    * 删除某个Cookie值
    * @access public
    * @param string $name   cookie的变量值
    * @return void
    */
    public function delete($name) {
        $this->set($name, '', 0-$this->defExpire);
        unset($_COOKIE[$this->defPrefix.$name]);
    }

    /**
    * 清空cookie
    * @access public
    * @return void
    */
    public function clear() {
        $this->newCookies=null;
        $this->newCookies=array();
        unset($_COOKIE);
    }

    /**
     * 获取用户选择的语言
     * @param string|null $defaultLanguage   默认的语言
     * @return string
     */
    public function getLanguage($defaultLanguage = null)
    {
        return $this->get($this->languageKey, $defaultLanguage);
    }

    /**
     * 设置用户选择的语言名
     * @param string $lanName 语言名称
     * @param int|null $expire 持续的有效时间,默认为见配置.设置为0或负数相当于删除它
     */
    public function setLanguage($lanName='zh-cn', $expire = null)
    {
        $this->set($this->languageKey, $lanName ,$expire);
    }
}