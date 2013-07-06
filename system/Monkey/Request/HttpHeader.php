<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Administrator
 * Date: 13-5-23
 * Time: 下午6:05
 * To change this template use File | Settings | File Templates.
 */

namespace Monkey\Request;


class HttpHeader {

    private
        $requestHeaders= null,
        $cacheControls=null,
        $accept=null,
        $languages=null;

    public function __construct()
    {
        $this->requestHeaders=$this->getallheaders();
        $this->cacheControls=$this->getCacheControls();
    }

    /**
     * 获取Cache-Control头域的某个缓存指令的值
     * 获取缓存控制请求头
     * @param string|null $name 缓存指令名
     * @param null $defaultValue 默认值
     * @return array|null
     */
    public function getCacheControl($name=null,$defaultValue=null)
    {
        if($name===null)
        {
            return $this->cacheControls;
        }
        else
        {
            $name=strtolower($name);
            if(array_key_exists($name,$this->cacheControls))
                return $this->cacheControls[$name];
            else
                return $defaultValue;
        }
    }

    /**
     * 判断Cache-Control头域的某个缓存指令是否存在
     * 判断某个缓存指令是否存在
     * @param string $name 指令名
     * @return bool
     */
    public function hasCacheControl($name)
    {
        $name=strtolower($name);
        return array_key_exists($name,$this->cacheControls);
    }

    /**
     * Connection头域
     * 获取连接控制器请求头
     * @return null  默认为close
     */
    public function getConnection()
    {
        return $this->_get('Connection','close');
    }

    /**
     * 获取请求时间
     * @return mixed
     */
    public function getDate()
    {
        return $_SERVER['REQUEST_TIME'];
    }

    /**
     * Accept请求头：
     * 获取浏览器端可以接受的文档或媒体类型列表。
     * @return array|null 如果存在就是数组
     */
    public function getAccept()
    {
        if($this->accept===null)
        {
            $this->accept= $this->_getList('Accept', 'text/html');
        }
        return $this->accept;
    }

    /**
     * Accept-Charset请求头
     * 获取浏览器可以接收的字符集列表
     * @return array
     */
    public function getAcceptCharsets()
    {
        return $this->_getList('Accept-Charset','utf-8');
    }

    /**
     * Accept-Encoding请求头
     * 获取浏览器可以接收的编码方法列表
     * @return array
     */
    public function getAcceptEncodings()
    {
        return explode(',', $this->_get('Accept-Encoding'));
    }

    /**
     * Accept-Language请求头
     * 获取浏览器可以接收的自然语言列表
     * @return array|null
     */
    public function getAcceptLanguages()
    {
        if($this->languages===null)
        {
            $l=$this->_getList('Accept-Language','en');
            $ls=array();
            foreach($l as $item)
            {
                $item=explode('-', $item);
                if($item['0'] == 'i' && isset($item['1']))
                {
                    $ls[]= $item['1'];
                }
                else
                {
                    $ls[]= $item['0'];
                }
            }
            $this->languages=$ls;
        }
        return $this->languages;
    }

    /**
     * Accept-Ranges请求头：
     * 判断浏览器是否希望获取文档的一部分（片段）
     * @return null bytes表示希望获取字节为单位的片段，none表示希望获取整个文档。
     */
    public function getAcceptRanges()
    {
        return $this->_get('Accept-Ranges', 'none');
    }

    /**
     * Ranges请求头：
     * @return null
     *
     * 获取浏览器索取的文档片段
     * 返回值示例————
     * 表示头500个字节：bytes=0-499
     * 表示第二个500字节：bytes=500-999
     * 表示最后500个字节：bytes=-500
     * 表示500字节以后的范围：bytes=500-
     * 表示0个字节后的所有字节（即整个文件）：bytes=0- （一般不用这个，因为不用这个就表示响应整个文档了）
     * 第一个和最后一个字节：bytes=0-0,-1
     * 同时指定几个范围：bytes=500-600,601-999
     */
    public function getRanges()
    {
        return $this->_get('Ranges'); //Range:bytes=500-600,601-999
    }

    /**
     * If-Range请求头：
     * @return null
     *
     * 判断浏览器是否只需要文档的缺少部分
     * 浏览器告诉 WEB 服务器，如果我请求的对象没有改变，就把我缺少的部分给我，如果对象改变了，就把整个对象给我。
     * 浏览器通过发送请求对象的 ETag 或者 自己所知道的最后修改时间给 WEB 服务器，让其判断对象是否改变了。
     * 总是跟 Range 头部一起使用。
     */
    public function getIfRange()
    {
        return $this->_get('If-Range');
    }

    /**
     * 获取HTTP认证的请求信息
     * 当PHP_AUTH_DIGEST验证返回字符串
     * @return string|
     *
     * 回答服务器的权限验证的信息。
     * 当浏览器接收到来自WEB服务器的 WWW-Authenticate 响应时，表名服务器要求有某种权限才能响应请求，
     * 这时，可以用 Authorization请求头 来回应向服务器表明自己的身份。
     * 如 Authorization: Basic QWxhZGRpbjpvcGVuIHNlc2FtZQ==
     */
    public function getAuthorization()
    {
        return $this->requestHeaders['Authorization'];
    }

    /**
     * 获取HTTP认证的请求信息
     * @return array ( 'type'=>验证类型, 'user'=>用户名, 'password'=>密码 )
     */
    public function getAuthorizationArray()
    {
        return array(
            'type'=>isset($_SERVER['AUTH_TYPE']) ? $_SERVER['AUTH_TYPE'] : 'Base',
            'user'=>isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : $_SERVER['AUTH_USER'],
            'password'=>isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : $_SERVER['AUTH_PASSWORD'],
        );
    }

    /**
     * User-Agent请求头:
     * @return null
     *
     * 获取浏览器的软件环境信息
     * 用户代理，是一个特殊字符串头，
     * 使得服务器能够识别客户端使用的操作系统及版本、CPU 类型、浏览器及版本、浏览器渲染引擎、浏览器语言、浏览器插件等
     */
    public function getUserAgent()
    {
        return $this->_get('User-Agent');
    }

    /**
     * 获取客户端浏览器的具体软件名
     * @access public
     * @return string
     */
    public function getBrowser() {
        $browser = get_browser(null, true);
        return $browser['browser'].$browser['majorver'];
    }

    /**
     * 获取客户端操作系统信息
     * @access public
     * @return string
     */
    public function getClientOS() {
        $browser = get_browser(null, true);
        return $browser['platform'];
    }

    /**
     * Age请求头：
     * @return null
     *
     * 获取实体文档生成了的时间
     * 当代理服务器用自己缓存的实体去响应请求时，用该头部表明该实体从产生到现在经过多长时间了。
     */
    public function getAge()
    {
        return $this->_get('Age');
    }

    /**
     * Host请求头：
     * @return string
     *
     * 获取请求的主机名
     * 指定请求的服务器的域名和端口号（80端口号可以省略）。
     * 如 Host：rss.sina.com.cn:8080
     */
    public function getHost()
    {
        if(!isset($_SERVER['HTTP_HOST']) && isset($_SERVER['HTTP_X_FORWARDED_HOST']))
        {
            $_SERVER['HTTP_HOST']=$_SERVER['HTTP_X_FORWARDED_HOST'];
        }
        return $_SERVER['HTTP_HOST'];
    }

    /**
     * If-Match请求头：
     * @return null
     *
     * 判断Etag标志的实体内容是否匹配
     * 只有请求内容与实体相匹配才有效。如果匹配时只需返回304头即可。
     */
    public function getIfMatch()
    {
        return $this->_get('If-Match');
    }

    /**
     * If-None-Match请求头：
     * @return null
     *
     * 判断ETag标志的实体内容是否改变（不匹配）
     * 如果内容未改变返回304代码，参数为服务器先前发送的Etag，与服务器回应的Etag比较判断是否改变
     * 如果对象的 ETag 改变了，其实也就意味著对象也改变了，才执行请求的动作。
     */
    public function getIfNoneMatch()
    {
        return $this->_get('If-None-Match');
    }

    /**
     * If-Modified-Since请求头：
     * @return null
     *
     * 获取发送文档的最近修改时间
     * 如果这个时间之后，服务器没有修改过文档，只需向浏览器发送304状态头即可
     */
    public function getIfModifiedSince()
    {
        return $this->_get('If-Modified-Since');
    }

    /**
     * 获取If-Unmodified-Since请求头
     * @return null
     *
     * 如果请求的对象在该头部指定的时间之后没修改过，才执行请求的动作（比如返回对象）。
     */
    public function getIfUnmodifiedSince()
    {
        return $this->_get('If-Unmodified-Since');
    }

    /**
     * 获取Proxy-Authenticate请求头
     * @return null
     *
     * 代理服务器响应浏览器，要求其提供代理身份验证信息。
     * 如 Proxy-Authorization：浏览器响应代理服务器的身份验证请求，提供自己的身份信息。
     */
    public function getProxyAuthenticate()
    {
        return $this->_get('Proxy-Authenticate');
    }

    /**
     * 获取Max-Forwards请求头
     * @return null
     *
     * 限制信息通过代理和网关传送的时间
     * 如 Max-Forwards: 10
     */
    public function getMaxForwards()
    {
        return $this->_get('Max-Forwards');
    }

    /**
     * 获取Via请求头
     * @return null
     *
     * 通知中间网关或代理服务器地址，通信协议
     * 列出从客户端到 OCS 或者相反方向的响应经过了哪些代理服务器，他们用什么协议（和版本）发送的请求。
     * 当客户端请求到达第一个代理服务器时，该服务器会在自己发出的请求里面添加 Via 头部，并填上自己的相关信息，
     * 当下一个代理服务器收到第一个代理服务器的请求时，会在自己发出的请求里面复制前一个代理服务器的请求的Via 头部，并把自己的相关信息加到后面，
     * 以此类推，当 OCS 收到最后一个代理服务器的请求时，检查 Via 头部，就知道该请求所经过的路由。
     * 例如：Via：1.0 236.D0707195.sina.com.cn:80 (squid/2.6.STABLE13)
     */
    public function getVia()
    {
        return $this->_get('Via');
    }

    /**
     * 获取Referer请求头
     * @return null
     *
     * 浏览器向 WEB 服务器表明自己是从哪个 网页/URL 获得/点击 当前请求中的网址/URL。
     * 例如 Referer：http://www.sina.com/index.html
     * 但是这个可以伪造，不太可靠。
     */
    public function getReferer()
    {
        return $this->_get('Referer');
    }

    /**
     * 获取Content-Type请求头
     * @return null
     *
     * 请求的与实体对应的MIME信息
     * 如 Content-Type: application/x-www-form-urlencoded
     */
    public function getContentType()
    {
        return $this->_get('Content-Type');
    }

    /**
     * 获取Content-Length请求头
     * @return null
     *
     * 请求的内容长度
     * 如 Content-Length: 348
     */
    public function getContentLength()
    {
        return $this->_get('Content-Length');
    }

    /**
     * 获取Expect请求头
     * @return null
     *
     * 请求的特定的服务器行为
     */
    public function getExpect()
    {
        return $this->_get('Expect');
    }

    /**
     * 获取From请求头
     * @return null
     *
     * 发出请求的用户的Email
     * 如 From: user@email.com
     */
    public function getFrom()
    {
        return $this->_get('From');
    }

    /**
     * 获取Warning请求头
     * @return null
     */
    public function getWarning()
    {
        return $this->_get('Warning');
    }

    /**
     * 获取指定请求头的通用方法
     * @param string $name 请求头名
     * @param string|null $defaultValue 默认的请求值
     * @return string|null
     */
    public function get($name, $defaultValue=null)
    {
         return $this->_get( $this->formatName($name), $defaultValue);
    }

    private function _get($name, $defaultValue=null)
    {
        return isset($this->requestHeaders[$name]) ? $this->requestHeaders[$name] : $defaultValue;
    }

    /**
     * 格式化响应头的字段名
     * @param string $name
     * @return string
     */
    private function formatName($name)
    {
        return preg_replace('/\-(.)/e', "'-'.strtoupper('\\1')", strtr(ucfirst(strtolower($name)), '_', '-'));
    }

    private function getallheaders()
    {
        if (function_exists('getallheaders'))
        {
            return getallheaders();
        }
        else
        {
            $headers=array();
            foreach ($_SERVER as $name => $value)
            {
                if (substr($name, 0, 5) == 'HTTP_')
                {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
            if (isset($_SERVER['PHP_AUTH_DIGEST']))
            {
                $headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
            }
            elseif (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
            {
                $headers['Authorization'] = array(
                    'type'=>$_SERVER['AUTH_TYPE'],
                    'user'=>$_SERVER['PHP_AUTH_USER'],
                    'password'=>$_SERVER['PHP_AUTH_PW']
                );
                //base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $_SERVER['PHP_AUTH_PW']);
            }
            elseif(isset($_SERVER['AUTH_USER']) && isset($_SERVER['AUTH_PASSWORD']))
            {
                $headers['Authorization'] = array(
                    'type'=>$_SERVER['AUTH_TYPE'],
                    'user'=>$_SERVER['AUTH_USER'],
                    'password'=>$_SERVER['AUTH_PASSWORD']
                );
                //base64_encode($_SERVER['AUTH_USER'] . ':' . $_SERVER['AUTH_PASSWORD']);
            }
            if (isset($_SERVER['CONTENT_LENGTH']))
            {
                $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
            }
            $headers['Content-Type'] = $this->getAcceptContentType();
            return $headers;
        }
    }

    private function getCacheControls()
    {
        $cc=$this->_get('Cache-Controls');//no-cache, no-store, max-age=0, must-revalidate'
        $cc=explode(',', $cc);
        $ccs=array();
        foreach($cc as $item)
        {
            $item=explode('=', trim($item));
            $ccs[$item[0]]=$item[1];
        }
        return $ccs;
    }

    private function _getList($name,$defaultValue=null)
    {
        $temp=$this->_get($name, $defaultValue);
        $t=strstr($temp,';',true) and $accept=$t;
        return explode(',', $temp);
    }
}