<?php
namespace Monkey\Cookie;

/**
 * Cookie
 * @package Monkey\Cookie
 */
final class Cookie
{
    private
        $prefix = "mk_", //cookie前缀
        $defExpire = 2592000, //cookie时间 30天
        $defDomain = '',
        $languageKey='MonkeyLanguage',
        $newCookies,//新建的cookie值
        $TIME;

    /**
     * @param \Monkey\App\App $app
     */
    public function __construct($app){
        $config=$app->config()->getComponentConfig('cookie','default');
        $this->TIME=$app->TIME;
        $this->prefix= $config['prefix'];
        $this->defExpire= $config['defExpire'];
        $this->defDomain= $config['defDomain'];
        if(isset($_SERVER['HTTP_X_FORWARDED_HOST']))//IIS
            $this->defDomain= $_SERVER['HTTP_X_FORWARDED_HOST'];
        elseif(isset($_SERVER['HTTP_HOST']))//Other
            $this->defDomain= $_SERVER['HTTP_HOST'];
    }

    /**
     * 获取某cookie变量的值
     * 获取的数值是进过64decode解密的,注:参数支持数组
     * @param string $name   cookie变量名
     * @param mixed|null $defaultValue   cookie变量值
     * @return mixed|null
     */
    public  function get($name, $defaultValue = null) {
        $name=$this->prefix.$name;
        if(isset($this->newCookies[$name]) )
        {
            if($this->newCookies[$name]['expire']>$this->TIME)
                $defaultValue=$this->newCookies[$name];
        }
        elseif(isset($_COOKIE[$name]))
        {
            $defaultValue=unserialize(base64_decode($_COOKIE[$name]));
        }
        return $defaultValue;
    }

    /**
     * 获取所有新建的cookies
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
        $name=$this->prefix.$name;
        if ($expire === null){
            $expire= $this->defExpire;
        }
        else{
            $expire= is_numeric($expire) ? (int)$expire : 0;
        }
        if(isset($this->newCookies[$name]) && $expire<1){
            unset($this->newCookies[$name]);
        }
        else{
            $this->newCookies[$name] = array(
                'name'     => $name,
                'value'    => base64_encode(serialize($value)),
                'expire'   => $expire ? $expire+$this->TIME : 0,
                'path'     => $path,
                'domain'   => $domain ? $domain : $this->defDomain,
                'secure'   => $secure ? true : false,
                'httpOnly' => $httpOnly,
            );
        }
    }

    /**
    * 删除Cookie值
    * @access public
    * @param string $name   cookie的变量值，为空时清除整个cookies空间
    * @return void
    */
    public function delete($name=null)
    {
        if(!$name){
            $this->newCookies=null;
            $this->newCookies=array();
            unset($_COOKIE);
        }
        else{
            $this->set($name, '', 0-$this->defExpire);
            unset($_COOKIE[$this->prefix.$name]);
        }
    }

    /**
     * 获取或设置用户选择的语言
     * @param string|null $name   设置用户选择的语言，为空则表示读取用户选择的语言
     * @param int|null $expire 第一参数非空时：设置持续的有效时间,默认为见配置.设置为0相当于删除它；第一参数为空：获取的默认语言
     * @return string
     *
     * $lan= language()   直接获取用户选择的语言
     * $lan= language(null, 'zh-cn')   获取用户选择的语言，如何用户没有设置，则返回'zh-cn'
     * language('zh-cn')  设置用户语言为'zh-cn'
     * language('zh-cn', 2592000)  设置用户语言为'zh-cn'，有效期30天，这个也是默认值
     * language('zh-cn', 0)  删除用户设置的语言
     * language(null, 0)  删除用户设置的语言
     */
    public function language($name = null, $expire = null)
    {
        if($name or $expire===0){
            $this->set($this->languageKey, $name, $expire);
        }
        else{
            return $this->get($this->languageKey, null);
        }
        return null;
    }

}