<?php
/**
 * Project MonkeyPHP
 *
 * PHP Version 5.3.9
 *
 * @package   Monkey\Request
 * @author    黄易 <582836313@qq.com>
 * @version   GIT:<git_id>
 */
namespace Monkey\Request;

use Monkey;

/**
 * Class Request
 *
 * 请求组件
 *
 * @package Monkey\Request
 */
class Request
{
    /**
     * 请求头对象
     *
     * @var \Monkey\Request\Header
     */
    private $header = null;

    /**
     * 请求参数
     *
     * @var array
     */
    private $parameters = array();

    /**
     * 是否为搜索引擎的爬虫
     *
     * @var bool
     */
    private $spider = false;

    /**
     * 请求网址协议+域名+端口号部分
     *
     * @var string
     */
    private $uriPrefix = null;

    /**
     * www文档根目录
     *
     * @var string
     */
    private $_documentRoot;

    /**
     * 获取请求的相对网址
     *
     * @var string
     */
    private $_requestUri;

    /**
     * 构造方法
     */
    public function __construct()
    {
        //去掉(还原)因为magic_quotes_gpc开启时加上的转义字符，
        //当 magic_quotes_gpc (GPC, Get/Post/Cookie) 打开时，
        //GPC所有的 ' (单引号), " (双引号), \ (反斜线) and 空字符会自动转为含有反斜线的转义字符。
        if (get_magic_quotes_gpc()) {
            $_POST = stripslashes_deep($_POST);
            $_GET = stripslashes_deep($_GET);
            $_COOKIE = stripslashes_deep($_COOKIE);
        }

        isset($_SERVER["argv"]) and $this->parameters = (array)$_SERVER["argv"];

        $this->parameters += (array)$_POST + (array)$_GET;

        //当你确定你的 .htaccess 文件有这句时 RewriteRule ^(.*)$ index.php?/$1&%{QUERY_STRING} [L]
        //可以删除下面这个代码块
        //begin
//        if (empty($_GET)) {
//            $get = strstr($this->getUrl(), '?');
//            if (!empty($get)) {
//                $get = explode('&', substr($get,1));
//                foreach ($get as $v){
//                    $v = explode('=', $v);
//                    $this->parameters[trim($v[0])] = trim($v[1]);
//                }
//            }
//        }
        //end
    }

    /**
     * 设置请求参数
     *
     * @param string $name 参数名
     * @param $value 参数值
     */
    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    /**
     * 获取$_GET、$_POST、$_SERVER["argv"]信息
     *
     * @param int|string $name 参数名称
     * @param string $defaultValue 当获取变量失败的时候返回该值,默认该值为null
     *
     * @return string
     */
    public function getParameter($name, $defaultValue = null)
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : $defaultValue;
    }

    /**
     * 批量获取$_GET、$_POST、$_SERVER["argv"]参数值
     *
     * @example $request->getParameters();//获取整个Request参数集
     * @example $request->getParameters(array($name1,$name2=>$default2,$name3));//获取三个Request参数，其中第二个参数有默认值。
     *
     * @param string $names 参数名称：为空则返回整个Request；允许在数组中使用默认值，方法见例二。
     *
     * @return array
     */
    public function getParameters($names = null)
    {
        return $this->_getByNames($names, $this->parameters);
    }

    /**
     * 获取HTTP请求头对象
     *
     * @return Header
     */
    public function header()
    {
        if ($this->header === null) {
            $this->header = new Header();
        }
        return $this->header;
    }

    /**
     * 获取请求方法
     *
     * 'GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'NONE', 'OPTIONS', 'TRACE', 'CONNECT'
     *
     * @return string 一般为'GET'或'POST'
     */
    public function getMethod()
    {
        return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    }

    /**
     * 获取请求的相对网址
     * 由于结果不是相对于前端控制器的，所以不常用
     *
     * @return string
     */
    public function getUrl()
    {
        if (empty($this->_requestUri)) {

            if (!isset($_SERVER['REQUEST_URI']) && isset($_SERVER['HTTP_X_REWRITE_URL'])) {
                //修复IIS的原始URI
                $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
            }

            $this->_requestUri = $_SERVER['REQUEST_URI'];

        }

        return $this->_requestUri;
    }

    /**
     * 获取请求的绝对网址
     * 通常是浏览器地址栏的全部信息
     *
     * @return string
     */
    public function getUri()
    {
        if ($this->isAbsUri())
            return $this->getUrl();
        else
            return $this->getUriPrefix() . $this->getUrl();
    }

    /**
     * 判断是否是HTTPS安全协议
     *
     * @return bool
     */
    public function isHttps()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) and strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https') {
            return true;
        } elseif (isset($_SERVER['HTTPS'])) {
            return strtolower($_SERVER['HTTPS']) == 'on';
        }

        return false;
    }

    /**
     * 获取请求网址协议+域名+端口号部分
     *
     * @return string
     */
    public function getUriPrefix()
    {
        if ($this->uriPrefix !== null) {
            return $this->uriPrefix;
        }

        $isHttps = $this->isHttps();
        $standardPort = $isHttps ? '443' : '80';
        $protocol = $isHttps ? 'https' : 'http';
        $host = explode(":", $this->getHost());

        if (count($host) == 1) {
            $host[] = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : '';
        }

        if ($host[1] == $standardPort || empty($host[1])) {
            unset($host[1]);
        }

        return $this->uriPrefix = $protocol . '://' . implode(':', $host);
    }

    /**
     * 获取代理列表
     *
     * @return array
     */
    public function getProxy()
    {
        if (empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return array();
        }

        $ips = $_SERVER['HTTP_X_FORWARDED_FOR'];

        return strpos($ips, ', ') ? explode(', ', $ips) : array($ips);
    }

    /**
     * 判断请求网址是否是绝对地址
     *
     * @return bool
     */
    public function isAbsUri()
    {
        return stripos($_SERVER['REQUEST_URI'], 'http/') === 0;
    }

    /**
     * 判断php是否运行在命令行模式下
     *
     * @return bool
     */
    public function isCli()
    {
        return php_sapi_name() == 'cli';
    }

    /**
     * 是否是AJAX请求
     * 支持Prototype, Mootools, jQuery等的AJAX
     * 能识别跨域ajax，跨域ajax返回true
     *
     * @return Boolean
     */
    public function isAjax()
    {
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']))
            return $_SERVER['HTTP_ACCEPT'] == 'text/javascript, application/javascript, */*';
        else
            return strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * 是否是flash请求
     *
     * @return bool
     */
    public function isFlash()
    {
        return isset($_SERVER['HTTP_USER_AGENT'])
        &&
        (
            stripos($_SERVER['HTTP_USER_AGENT'], 'Shockwave') !== false
            ||
            stripos($_SERVER['HTTP_USER_AGENT'], 'Flash') !== false
        );
    }

    /**
     * 获取$_ENV信息（environment）
     *
     * @param string $name $_ENV的键值名称
     * @param string $defaultValue 当获取变量失败的时候返回该值,默认该值为null
     *
     * @return string
     */
    public function getEnvironment($name, $defaultValue = null)
    {
        return isset($_ENV[$name]) ? $_ENV[$name] : $defaultValue;
    }

    /**
     * 批量获取$_ENV信息（environment）
     *
     * @param array|null $name $_ENV的键值名称，为空时返回整个环境变量
     *
     * @return array
     */
    public function getEnvironments($name = null)
    {
        return $this->_getByNames($name, $_ENV);
    }

    /**
     * 判断常用的爬虫函数
     *
     * @author Mer
     *
     * @return boolean
     */
    public function isSpider()
    {
        if ($this->spider === null) {
            $this->spider = false;
            $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
            $spiders = include(__DIR__ . '/data/spider.php');
            foreach ($spiders as $c) {
                if (strpos($agent, $c) !== false) {
                    $this->spider = true;
                    break;
                }
            }
        }
        return $this->spider;
    }

    /**
     * 获取cookie变量
     *
     * @param string $name 变量名
     * @param null $defaultValue 默认的变量值
     *
     * @return mixed|null
     */
    public function getCookie($name, $defaultValue = null)
    {
        isset($_COOKIE[$name]) && ($defaultValue = $_COOKIE[$name]);
        return $defaultValue;
    }

    /**
     * 获取客户端IP
     *
     * @return string
     */
    public function getIP()
    {
        static $ip = null;

        if ($ip) {
            return $ip;
        }

        if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown")) {
            $ip = getenv("HTTP_CLIENT_IP");

        } elseif (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown")) {
            $ip = getenv("HTTP_X_FORWARDED_FOR");

        } elseif (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")) {
            $ip = getenv("REMOTE_ADDR");

        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")) {
            $ip = $_SERVER['REMOTE_ADDR'];

        } else {
            $ip = "unknown";
        }

        $ip === '::1' and $ip = '127.0.0.1';

        return $ip;
    }

    /**
     * 批量获取参数
     *
     * @param $names
     * @param $data
     *
     * @return array
     */
    private function _getByNames($names, &$data)
    {
        if (!$names) {
            return $data;
        }

        $result = array();

        foreach ($names as $key => $value) {
            if (is_int($key))
                $result[$value] = isset($data[$value]) ? $data[$value] : null;
            else
                $result[$key] = array_key_exists($key, $data) ? $data[$key] : $value;
        }

        return $result;
    }

    /**
     * 获取请求域名
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->getHost();
    }

    /**
     * Host请求头：
     *
     * @return string
     *
     * 获取请求的主机名
     * 指定请求的服务器的域名和端口号（80端口号可以省略）。
     * 如 Host：rss.sina.com.cn:8080
     */
    public function getHost()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            //IIS
            $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
        } else {
            //Other
            $host = $_SERVER['HTTP_HOST'];
        }

        return $host;
    }
    /**
     * 获取命令行方式请求的文件根目录
     *
     * @return string
     */
    public function getRoot()
    {
        return getcwd();
    }

    /**
     * 获取web方式请求的www根目录
     *
     * @return string
     */
    public function getDocumentRoot()
    {

        if ($this->_documentRoot) {
            return $this->_documentRoot;
        }

        //修复IIS服务器$_SERVER['DOCUMENT_ROOT']失效的情况
        if (!isset($_SERVER['DOCUMENT_ROOT'])) {
            if (isset($_SERVER['SCRIPT_FILENAME'])) {

                $_SERVER['DOCUMENT_ROOT'] = substr(
                        $_SERVER['SCRIPT_FILENAME'],
                        0,
                        0 - strlen($_SERVER['PHP_SELF'])
                );

            } elseif (isset($_SERVER['PATH_TRANSLATED'])) {

                $_SERVER['DOCUMENT_ROOT'] = substr(
                        str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']),
                        0,
                        0 - strlen($_SERVER['PHP_SELF'])
                );
            }

        }

        //修正DOCUMENT_ROOT末尾可能因为apache配置造成多余一个'/'。
        $_SERVER['DOCUMENT_ROOT'] = dir_format($_SERVER['DOCUMENT_ROOT']);

        $this->_documentRoot = $_SERVER['DOCUMENT_ROOT'];

        return $this->_documentRoot;
    }

    /**
     * 获取请求内容主体
     *
     * @return string
     */
    public function getInput()
    {
        return @(string)file_get_contents('php://input');
    }

    /**
     * 获取请求时间
     *
     * @return int
     */
    public function getTime()
    {
        return $_SERVER['REQUEST_TIME'];
    }

    /**
     * 获取用户选择的语言
     *
     * @param string $defaultLanguage 获取的默认语言
     *
     * @return string
     */
    public function getLanguage($defaultLanguage = 'zh-cn')
    {
        $lan = $this->getCookie('language', $defaultLanguage);

        if (empty($lan)) {
            $lan = $this->header()->getAcceptLanguages();
        }

        return isset($lan[0]) ? $lan[0] : $lan;
    }

}