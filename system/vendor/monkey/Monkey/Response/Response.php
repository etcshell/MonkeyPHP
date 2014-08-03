<?php
/**
 * Project MonkeyPHP
 *
 * PHP Version 5.3.9
 *
 * @package   Monkey\Response
 * @author    黄易 <582836313@qq.com>
 * @version   GIT:<git_id>
 */
namespace Monkey\Response;

use Monkey;

/**
 * Class Response
 *
 * Web响应组件
 *
 * @package Monkey\Response
 *
 * 相应状态码信息描述：
 * 1xx：信息，请求收到，继续处理
 * 2xx：成功，行为被成功地接受、理解和采纳
 * 3xx：重定向，为了完成请求，必须进一步执行的动作
 * 4xx：客户端错误，请求包含语法错误或者请求无法实现
 * 5xx：服务器错误，服务器不能实现一种明显无效的请求
 */
class Response {

    /**
     * 应用对象
     *
     * @var Monkey\App $app
     */
    public $app;

    /**
     * 输出的编码
     *
     * @var string
     */
    private $charset = 'UTF-8';

    /**
     * 响应主域名
     *
     * @var string
     */
    private $domain = '';

    /**
     * 内容是否已输出
     *
     * @var bool
     */
    private $isSend = false;

    /**
     * 设置输出的头部信息
     *
     * @var array
     */
    private $headers = array();

    /**
     * 新建的Cookies
     *
     * @var array
     */
    private $newCookies = array();

    /**
     * 内容
     *
     * @var string|array
     */
    private $body;

    /**
     * 是否输出json数据
     *
     * @var bool
     */
    private $isJson = false;

    /**
     * 是否仅仅输出头部
     *
     * @var bool
     */
    private $httpHeaderOnly = false;

    /**
     * 立即跳转网址
     *
     * @var string
     */
    private $redirectUrl = '';

    /**
     * 响应状态码
     *
     * @var int
     */
    private $statusCode = 200;

    /**
     * 状态说明
     *
     * @var string
     */
    private $statusText = 'OK';

    /**
     * 状态说明备选列表
     *
     * @var array
     */
    private $statusTexts = array(
        '100' => 'Continue',
        '101' => 'Switching Protocols',
        '200' => 'OK',
        '201' => 'Created',
        '202' => 'Accepted',
        '203' => 'Non-Authoritative Information',
        '204' => 'No Content',
        '205' => 'Reset Content',
        '206' => 'Partial Content',
        '207' => 'Multi-Status',
        '300' => 'Multiple Choices',
        '301' => 'Moved Permanently',
        '302' => 'Found',
        '303' => 'See Other',
        '304' => 'Not Modified',
        '305' => 'Use Proxy',
        '306' => '(Unused)',
        '307' => 'Temporary Redirect',
        '400' => 'Bad Request',
        '401' => 'Unauthorized',
        '402' => 'Payment Required',
        '403' => 'Forbidden',
        '404' => 'Not Found',
        '405' => 'Method Not Allowed',
        '406' => 'Not Acceptable',
        '407' => 'Proxy Authentication Required',
        '408' => 'Request Timeout',
        '409' => 'Conflict',
        '410' => 'Gone',
        '411' => 'Length Required',
        '412' => 'Precondition Failed',
        '413' => 'Request Entity Too Large',
        '414' => 'Request-URI Too Long',
        '415' => 'Unsupported Media Type',
        '416' => 'Requested Range Not Satisfiable',
        '417' => 'Expectation Failed',
        '422' => 'Unprocessable Entity',
        '423' => 'Locked',
        '424' => 'Failed Dependency',
        '500' => 'Internal Server Error',
        '501' => 'Not Implemented',
        '502' => 'Bad Gateway',
        '503' => 'Service Unavailable',
        '504' => 'Gateway Timeout',
        '505' => 'HTTP Version Not Supported',
        '507' => 'Insufficient Storage',
        '509' => 'Bandwidth Limit Exceeded'
    );

    /**
     * 构造方法
     *
     * @param Monkey\App $app
     */
    public function __construct($app) {
        $this->app = $app;

        if ('HEAD' == $app->request()->getMethod()) {
            $this->setHttpHeaderOnly(true);
        }

        $this->charset = $app->config()->get('response_charset', 'UTF-8');
        $this->domain = $app->config()->get('domain', null);

        if (empty($this->domain)) {
            $this->domain = $app->request()->getDomain();
        }

        $app->shutdown()->register(array($this, 'send'));

        if (substr($this->app->request()->header()->getContentType(), -4) == 'json') {
            $this->setJson();
        }

        if (strpos($_SERVER["HTTP_ACCEPT_ENCODING"], 'gzip') !== false &&
            !ini_get('zlib.output_compression') &&
            extension_loaded("zlib") &&
            function_exists('gzencode')
        ) {
            ob_start("ob_gzhandler");

        }
        else {
            ob_start();
        }
    }

    /**
     * 添加Html正文Body文档正文
     *
     * @param string|array $content 内容
     */
    public function addBody($content) {
        if (is_array($content)) {
            $this->body = array_merge((array)$this->body, $content);
        }
        else {
            $this->body .= $content;
        }
    }

    /**
     * 设置响应正文
     *
     * @param string|array $content 内容
     */
    public function setBody($content) {
        $this->body = $content;
    }

    /**
     * 设置响应数据为ajax的json数据
     *
     * @param array $data 响应的数据 array格式，系统自动转换为json格式
     *
     * @return $this
     */
    public function setJson(array $data = array()) {
        $this->setContentType('application/json');
        $this->isJson = true;
        $data and $this->body = $data;
        return $this;
    }

    /**
     * 获取Html正文Body文档正文
     *
     * @return mixed
     */
    public function getBody() {
        $body = $this->body;
        $this->body = null;
        return $body;
    }

    /**
     * 清除Html正文Body内容
     */
    public function clearBody() {
        $this->body = null;
    }

    /**
     * 设置立即重定向地址
     *
     * @param string $url 重定向的地址
     * @param bool $exitNow 立即退出，默认为否
     */
    public function redirect($url, $exitNow = false) {
        $this->httpHeaderOnly = true;
        $this->redirectUrl = $url; //header('Location:'.$url,true,302);

        if ($exitNow) {
            exit;
        }
    }

    /**
     * 发送响应内容
     */
    public function send() {
        if ($this->isSend) {
            return;
        }

        $this->isSend = true;
        $this->sendHeaders();
        $httpHeaderOnly = ($this->httpHeaderOnly or ($this->statusCode > 399));

        if (!$httpHeaderOnly) {
            echo $this->isJson ? json_encode((array)$this->getBody()) : implode("\n", (array)$this->getBody());
        }

        if (function_exists('fastcgi_finish_request')) {
            $httpHeaderOnly and $this->obEndFlush(false);
            fastcgi_finish_request();

        }
        elseif ('cli' !== PHP_SAPI) {
            $this->obEndFlush(!$httpHeaderOnly);

        }
    }

    /**
     * 发送：HTTP状态、headers
     *
     * @return bool
     */
    public function sendHeaders() {
        if (headers_sent()) {
            return false;
        }

        $status = 'HTTP/1.1 ' . $this->statusCode . ' ' . $this->statusText;
        header($status);

        if ($this->statusCode > 399) {
            return false;
        }

        if ($this->redirectUrl) {
            header('Location:' . $this->redirectUrl, true, 302);
        }
        else {
            foreach ($this->getAllHeaders() as $name => $value) {
                header($name . ': ' . $value);
            }
        }

        $cookies = $this->newCookies;

        foreach ($cookies as $name => $ck) {
            setrawcookie(
                $name,
                $ck['value'],
                $ck['expire'],
                $ck['path'],
                $ck['domain'],
                $ck['secure'],
                $ck['httpOnly']
            );
        }

        return true;
    }

    private function obEndFlush($flush = true) {
        // ob_get_level() never returns 0 on some Windows configurations, so if
        // the level is the same two times in a row, the loop should be stopped.
        $previous = null;
        $obStatus = ob_get_status(1);

        while (($level = ob_get_level()) > 0 && $level !== $previous) {
            $previous = $level;

            if ($obStatus[$level - 1]) {

                if (version_compare(PHP_VERSION, '5.4', '>=')) {
                    if (isset($obStatus[$level - 1]['flags']) &&
                        ($obStatus[$level - 1]['flags'] & PHP_OUTPUT_HANDLER_REMOVABLE)
                    ) {
                        $flush ? ob_end_flush() : ob_end_clean();
                    }

                }
                else {
                    if (isset($obStatus[$level - 1]['del']) && $obStatus[$level - 1]['del']) {
                        $flush ? ob_end_flush() : ob_end_clean();
                    }
                }
            }
        }

        $flush ? ob_clean() : flush();
    }

    /**
     * 设置响应头状态码
     *
     * @param $code
     * @param null $note 状态码说明
     */
    public function setStatus($code, $note = null) {
        $this->statusCode = $code;
        $this->statusText =
            null !== $note ? $note : isset($this->statusTexts[$code]) ? $this->statusTexts[$code] : 'unknow';
    }

    /**
     * 将响应信息设置为仅仅只有头部
     * 注意：这将会清除已经添加的响应内容
     *
     * @param bool $isHeaderOnly
     */
    public function setHttpHeaderOnly($isHeaderOnly = true) {
        $this->httpHeaderOnly = (boolean)$isHeaderOnly;
    }

    /**
     * 判断是否仅需返回响应头
     *
     * @return bool
     */
    public function isHttpHeaderOnly() {
        return $this->httpHeaderOnly;
    }

    /**
     * 设置强制浏览器不缓存
     */
    public function setNoCache() {
        $this->headers['Expires'] = gmdate('r', $this->app->TIME);
        $this->setCacheControl('no-cache');
        $this->setCacheControl('max-age', 0);
        $this->headers['Pragma'] = 'no-cache';
    }

    /**
     * 强制浏览器缓存一段时间
     *
     * @param string $eTag 当前请求的缓存标志
     * @param int $expiresTime 时间戳 缓存过期时间点
     * @param int $lastModified 时间戳 最后修改的时间点，默认使用缓存过期时间点
     */
    public function setCache($eTag, $expiresTime, $lastModified = null) {
        $expires = gmdate('r', $expiresTime + $this->app->TIME);
        $this->headers['Expires'] = $expires; //将响应内容缓存到浏览器中
        $this->headers['Last-Modified'] = $lastModified ? gmdate('r', $lastModified) : $expires; //防止浏览器自动刷新
        $this->headers['Etag'] = $eTag; //防止浏览器人为刷新
        $this->headers['Pragma'] = 'max-age';
        $this->setCacheControl('max-age', $expiresTime);
        $this->setConnection(false);
    }

    /**
     * 设置文件下载
     *
     * @param string $mimeType 文件的mime类型，请查表设置
     * @param string $filename 文件名，含扩展名
     * @param string $charset 文件字符集，一般在Response里配置了，这里就不再配置了。
     */
    public function setDownload($mimeType, $filename, $charset = '') {
        ob_clean();
        $this->setContentType($mimeType);
        $charset and $charset = '; charset=' . $charset;
        $this->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '" ' . $charset);
        //header('Content-Disposition: attachment; filename="' . $filename . '.xls"; charset=utf-8');
    }

    /**
     * 通用的响应头信息设置方法
     *
     * @param string $name 响应头的字段名称
     * @param string $value 响应头的字段取值
     * @param boolean $replace 是否是替换方式设置
     */
    public function setHeader($name, $value, $replace = true) {
        $name = $this->formatName($name);
        $this->actionSetHeader($name, $value, $replace);
    }

    /**
     * 获取已设置的指定的响应头信息
     *
     * @param string $name 响应头的字段名称
     * @param null|string $default 默认的字段取值
     *
     * @return mixed
     */
    public function getHeader($name, $default = null) {
        return $this->actionGetHeader($this->formatName($name), $default);
    }

    /**
     * 获取已设置的全部headers响应头信息
     *
     * @return array HTTP headers
     */
    public function getAllHeaders() {
        !isset($this->headers['Content-Type']) and $this->setContentType();
        return $this->headers;
    }

    /**
     * 清除全部headers响应头信息
     */
    public function clearHeaders() {
        $this->headers = null;
        $this->headers = array();
    }

    /**
     * 判断是否设置了指定的响应头
     *
     * @param string $name 响应头的名称
     *
     * @return boolean
     */
    public function hasHeader($name) {
        return $this->actionHasHeader($this->formatName($name));
    }

    /**
     * 设置响应内容的字符集
     *
     * @param string $charset
     */
    public function setCharset($charset = 'UTF-8') {
        $this->charset = $charset;
    }

    /**
     * 添加响应头header的缓存控制部分.
     *
     * @param string $name
     * @param string $value
     */
    public function setCacheControl($name, $value = null) {
        $cacheControl = $this->actionGetHeader('Cache-Control');
        $currentHeaders = array();

        if ($cacheControl) {
            foreach (preg_split('/\s*,\s*/', $cacheControl) as $tmp) {
                $tmp = explode('=', $tmp);
                $currentHeaders[$tmp[0]] = isset($tmp[1]) ? $tmp[1] : null;
            }
        }

        $currentHeaders[strtr(strtolower($name), '_', '-')] = $value;
        $headers = array();

        foreach ($currentHeaders as $key => $value) {
            $value = $value !== null ? '=' . $value : '';
            $headers[] = $key . $value;
        }

        $this->setHeader('Cache-Control', implode(', ', $headers));
    }

    /**
     * 设置连接控制属性
     *
     * @param bool $isKeepAlive 保持连接为TRUE，响应结束后断开为FALSE
     */
    public function setConnection($isKeepAlive = true) {
        $this->headers['Connection'] = $isKeepAlive ? 'Keep-Alive' : 'close';
    }

    /**
     * 设置消息发送的时间
     *
     * @param $unixTime
     */
    public function setDate($unixTime) {
        $this->headers['Date'] = gmdate('r', $unixTime);
    }

    /**
     * 设置HTTP/1.1协议中的Pragma（缓存控制指令）
     *
     * @param string $value
     */
    public function setPragma($value = 'no-cache') {
        $this->headers['Pragma'] = $value;
    }

    /**
     * 设置立即跳转网址
     * 注意，本设置将清除已经设置的其它响应头！
     *
     * @param string $url
     */
    public function setLocation($url) {
        $this->redirect($url, false);
        $this->headers = null;
        //$this->headers['Location']=$url;
    }

    /**
     * 设置服务器的软件信息
     *
     * @param $value
     */
    public function setServer($value) {
        $this->setHeader('Server', $value);
    }

    /**
     * 设置文档的压缩编码的格式
     *
     * @param string $encoding 如 gzip | none
     */
    public function setContentEncoding($encoding = 'none') {
        $this->headers['Content-Encoding'] = $encoding;
    }

    /**
     * 设置响应文档的自然语言
     *
     * @param string $language en 或 zh-cn 等
     */
    public function setContentLanguage($language) {
        $this->headers['Content-Language'] = $language;
    }

    /**
     * 设置实体内容的长度。只有当浏览器使用持久HTTP连接时才需要这个数据。
     * 注意会同时设置连接控制为持久连接
     *
     * @param int $length
     */
    public function setContentLength($length) {
        $this->headers['Content-Length'] = $length;
        $this->setConnection(true);
    }

    /**
     * 设置响应内容的MD5值
     * 一般不用
     *
     * @param $md5
     */
    public function setContentMD5($md5) {
        $this->headers['Content-MD5'] = $md5;
    }

    /**
     * 设置响应内容的范围
     * 如多点下载的片段
     *
     * @param string $unit 计量单位
     * @param int $begin 开始字节处
     * @param null $length 发送长度
     * @param int $total 文档总字节数
     */
    public function setContentRange($unit = 'bytes', $begin = 0, $length = null, $total = 0) {
        $this->setHeader('Content-Range', $unit . ' ' . (string)$begin . '-' . ($begin + $length) . '/' . $total);
    }

    /**
     * 设置响应头的Content-Type段
     *
     * @param string
     */
    public function setContentType($value = 'text/html') {
        // add charset if needed (only on text content)
        if (false === stripos($value, 'charset') &&
            (0 === stripos($value, 'text/') || strlen($value) - 3 === strripos($value, 'xml'))
        ) {
            $value .= '; charset=' . $this->charset;
        }

        $this->headers['Content-Type'] = $value;
    }

    /**
     * 指示浏览器如何显示文档内容，是 MIME 协议的扩展。
     * 一般用与下载文件，或在线显示文件（非html文档，html文档会自动在线显示）
     *
     * @param string $filename 文件名
     * @param string|null $type 类型 attachment | inline ，为空时自动填充为 attachment
     * @param string|null $charset 文档的字符集
     */
    public function setContentDisposition($filename, $type = null, $charset = null) {
        $charset and $charset = '; charset=' . $charset;
        !$type and $type = 'attachment';
        $this->headers['Content-Disposition'] = $type . '; filename="' . $filename . '"' . $charset;
    }

    /**
     * 设置响应内容的标志，以便在重新请求而标志的内容不变时直接回绝浏览器
     * 告诉浏览器，请求的内容没变，不要老是刷新了！
     *
     * @param string $etag 标志
     */
    public function setEtag($etag) {
        $this->headers['Etag'] = $etag;
    }

    /**
     * 设置响应内容的过期时间，这段时间之内浏览器不会向服务器刷新请求，除非人为刷新操作
     *
     * @param int $unixTime
     */
    public function setExpires($unixTime) {
        $this->headers['Expires'] = gmdate('r', $unixTime);
    }

    /**
     * 文档的最后改动时间
     *
     * @param $unixTime
     *
     * 在这个时间到期时会向服务器询问是否有新版的文档，如果有则提出刷新请求；
     * 到期刷新时，如果服务器响应状态为304（Not Modified）则不会发生真的刷新请求。
     * 即刷新前可以用这个时间做一个判断刷新是否有必要。
     */
    public function setLastModified($unixTime) {
        $this->headers['Last-Modified'] = gmdate('r', $unixTime);
    }

    /**
     * 定义新的实体头，但是这些域可能无法未接受方识别。
     * 开发者只有在请求头中自己编程使用
     *
     * @param $extension
     */
    public function setExtensionHeader($extension) {
        $this->headers['Extension-Header'] = $extension;
    }

    /**
     * 设置服务器支持哪些请求方法（如GET、POST等）
     *
     * @param string $method 单个设置 如 "GET"； 多个设置 如 "GET, POST" 等中间用逗号隔开即可
     */
    public function setAllow($method) {
        $allow = $this->headers['Allow'];
        $this->headers['Allow'] = $allow ? $allow . ', ' . $method : $method;
    }

    /**
     * 向浏览器申明是否支持发送文档的一部分（片段）
     *
     * @param string $type bytes：表示支持发送以字节为单位的片段，none：表示不支持片段发送。
     */
    public function setAcceptRanges($type = 'bytes') {
        $this->headers['Accept-Ranges'] = $type; //none表示不支持发送片段
    }

    /**
     * 表明浏览器多长时间后请求最新的页面
     * 即延时跳转或刷新
     * 一般不在这里使用，在HtmlHead里使用更方便（可以给出一些提示，如未登录不可以下载，5秒后跳转到登录页之类）
     *
     * @param int $time 秒数
     * @param string|null $url 为空时表示刷新本页，非空时表示跳转到该页面。
     */
    public function setRefresh($time, $url = null) {
        $url and $url = '; URL=' . $url;
        $this->headers['Refresh'] = $time . $url;
    }

    /**
     * 添加响应头header的vary部分.
     * 用于表示使用服务器驱动的协商从可用的响应表示中选择响应实体。
     *
     * @param string $value
     */
    public function setVary($value) {
        $vary = $this->actionGetHeader('Vary');
        $currentHeaders = array();

        if ($vary) {
            $currentHeaders = preg_split('/\s*,\s*/', $vary);
        }

        $value = $this->formatName($value);

        if (!in_array($value, $currentHeaders)) {
            $currentHeaders[] = $value;
            $this->setHeader('Vary', implode(', ', $currentHeaders));
        }
    }

    /**
     * 设置Cookies的过期时间
     *
     * @param int $unixTime
     */
    public function setCookieExpires($unixTime) {
        $this->headers['Set-Cookie'] = gmdate('r', $unixTime);
    }

    /**
     * 设置需要浏览器在请求头Authorization中提供什么类型的授权信息。
     *
     * @param string $need
     */
    public function setAuthenticate($need) {
        $this->headers['WWW-Authenticate'] = $need; // 如 BASIC realm=＼"executives＼"
    }

    /**
     * 告诉浏览器多久后自动刷新
     *
     * @param int $time 当小于 2592000（一个月） 表示多少秒后刷新，当大于或等于 2592000 表示精确的格林威治时间之时刷新
     */
    public function setRetryAfter($time) {
        $this->headers['Retry-After'] = $time < 2592000 ? $time : gmdate('r', $time);
    }

    /**
     * 设置关于响应状态的补充信息。
     *
     * @param $msg
     */
    public function setWarning($msg) {
        $this->headers['Warning'] = $msg;
    }

    /**
     * 设置用户选择的语言
     *
     * @param string $language 设置用户选择的语言，为空则表示读取用户选择的语言
     * @param int|null $expire 设置持续的有效时间；设置为0相当于删除它
     */
    public function setLanguage($language, $expire = 2592000) {
        $this->setCookie('language', $language, $expire);
    }


    /**
     * 设置一个Cookie
     *
     * @param string $name
     * @param string $value
     * @param int|null $expire 持续的有效时间,默认为见配置.设置为0或负数相当于删除它 2592000==30天
     * @param string $path 起作用的访问目录
     * @param string $domain 起作用的主域名
     * @param bool $secure 是否是https协议才能使用
     * @param bool $httpOnly 是否只允许服务器使用这个Cookie值，为真时浏览器中的js不能访问
     */
    public function setCookie(
        $name, $value, $expire = 2592000, $path = '/', $domain = '', $secure = false, $httpOnly = false
    ) {
        if (isset($this->newCookies[$name]) && $expire < 1) {
            unset($this->newCookies[$name]);

        }
        else {
            $this->newCookies[$name] = array(
                'name' => $name,
                'value' => $value,
                'expire' => $expire ? $expire + time() : 0,
                'path' => $path,
                'domain' => $domain ? $domain : $this->domain,
                'secure' => $secure ? true : false,
                'httpOnly' => $httpOnly,
            );
        }
    }

    /**
     * 删除Cookie值
     *
     * @param string $name cookie的变量值，为空时清除整个cookies空间
     *
     * @return void
     */
    public function deleteCookie($name = null) {
        if (!$name) {
            $this->newCookies = null;
            $this->newCookies = array();
            unset($_COOKIE);
        }
        else {
            $this->setCookie($name, '', 0);
            unset($_COOKIE[$name]);
        }
    }

    /**
     * 格式化响应头的字段名
     *
     * @param string $name
     *
     * @return string
     */
    private function formatName($name) {
        return preg_replace('/\-(.)/e', "'-'.strtoupper('\\1')", strtr(ucfirst(strtolower($name)), '_', '-'));
    }

    private function actionSetHeader($name, $value, $replace = true) {
        if ('Content-Type' == $name) {

            if ($replace || !$this->actionGetHeader('Content-Type', null)) {
                $this->setContentType($value);
            }

            return;
        }

        if (!$replace) {
            $current = $this->actionGetHeader($name, '');
            $value = ($current ? $current . ', ' : '') . $value;
        }

        $this->headers[$name] = $value;
    }

    private function actionGetHeader($name, $default = null) {
        return $this->actionHasHeader($name) ? $this->headers[$name] : $default;
    }

    private function actionHasHeader($name) {
        return array_key_exists($name, $this->headers);
    }
}
