<?php
namespace Library;

/**
 * HttpClient
 * http客户端类
 * @package Library
 */
class HttpClient {
    private $_defaultFlen = 8192; //fread默认读取最大长度
    private $_block = true; //读取网络流 是否阻塞，true|阻塞，false|不阻塞
    private $_defaultPort = 80; //默认端口号
    /**
     * 模拟http协议和别的网站进行通讯
     * @param int $defaultPort //默认端口号
     * @param int $defaultFlen //fread默认读取最大长度
     * @param bool $block //读取网络流 是否阻塞
     */
    public function __construct($defaultPort = 80, $defaultFlen = 8192, $block = true) {
        $this->_defaultPort = (int)$defaultPort;
        $this->_defaultFlen = (int)$defaultFlen;
        $this->_block = ($block) ? TRUE : FALSE;
    }

    /**
     * get方式获取数据
     * @param  string $url 链接地址
     * @param  string $ip ip地址
     * @param  int $timeout 超时时间
     * @param  string $cookie cookie
     * @param  int $freadLen 长度
     * @return string 返回：HTTP获取处理后的信息
     */
    public function get($url, $ip = '', $timeout = 15, $cookie = '', $freadLen = 0) {
        if ($freadLen > $this->_defaultFlen) {
            $freadLen = $this->_defaultFlen;
        }
        $this->_http($url, '', $ip, $timeout, $cookie, $freadLen);
    }

    /**
     * post方式获取数据
     * @param  array $post post内容，为空请改用get方法
     * @param  string $url 链接地址
     * @param  string $ip ip地址
     * @param  int $timeout 超时时间
     * @param  string $cookie cookie
     * @param  int $freadLen 长度
     * @return string 返回：HTTP获取处理后的信息
     */
    public function post($post, $url, $ip = '', $timeout = 15, $cookie = '', $freadLen = 0) {
        if ($freadLen > $this->_defaultFlen)
            $freadLen = $this->_defaultFlen;
        $this->_http($url, $post, $ip, $timeout, $cookie, $freadLen);
    }

    /**
     * 通过http方式获取数据
     * @param  string $url 链接地址
     * @param  array $post post内容，没有则为空
     * @param  string $ip ip地址
     * @param  int $timeout 超时时间
     * @param  string $cookie cookie
     * @param  int $freadLen 长度
     * @return string
     */
    private function _http($url, $post, $ip = '', $timeout = 15, $cookie = '', $freadLen = 0) {
        //处理参数
        $data = $this->_parseParam($url, $post, $ip, $timeout, $cookie, $freadLen);
        list($url, $post, $ip, $timeout, $port, $cookie, $freadLen) = array($data['url'], $data['post'], $data['ip'], $data['timeout'], $data['port'], $data['cookie'], $data['freadLen']);
        unset($data);
        //Header信息
        $httpReturn = $httpHeader = '';
        $httpHeader = ($post !== '') ? $this->_postHeader($url, $post, $ip, $port, $cookie) : $this->_getHeader($url, $post, $ip, $port, $cookie);
        //socket
        $httpFp = @fsockopen($ip, $port, $errno, $errstr, $timeout);
        @stream_set_blocking($httpFp, $this->_block);
        @stream_set_timeout($httpFp, $timeout);
        @fwrite($httpFp, $httpHeader);
        $status = @stream_get_meta_data($httpFp);
        if ($httpFp == false || $status['timed_out']) {
            fclose($httpFp);
            return $httpReturn;
        }
        //fread
        $freadLen = ($freadLen == 0) ? $this->_defaultFlen : $freadLen;
        $isHttpHeader = false;
        $stopFread = false;
        while (!feof($httpFp) && !$stopFread) {
            if ((!$isHttpHeader) && ($tempHttpReturn = @fgets($httpFp)) && ($tempHttpReturn == "\r\n" || $tempHttpReturn == "\n")) {
                $isHttpHeader = true;
            }
            if ($isHttpHeader) {
                $httpReturn = @fread($httpFp, $freadLen);
                if (strlen($httpReturn) > $freadLen)
                    $stopFread = true;
            }
        }
        fclose($httpFp);
        return $httpReturn;
    }

    /**
     * HTTP POST方式的header头部信息封装
     * @param  string $url 链接地址
     * @param  string $post post内容，没有则为空
     * @param  string $ip ip地址
     * @param  int $port 端口号
     * @param  string $cookie cookie
     * @return string
     */
    private function _postHeader($url, $post, $ip, $port = 80, $cookie = '') {
        $httpHeader = '';
        $httpHeader .= 'POST ' . $url . ' HTTP/1.0' . PHP_EOL;
        $httpHeader .= 'Accept: */*' . PHP_EOL;
        $httpHeader .= 'Accept-Language: zh-cn' . PHP_EOL;
        $httpHeader .= 'Content-Type: application/x-www-form-urlencoded' . PHP_EOL;
        $httpHeader .= 'User-Agent: ' . $_SERVER['HTTP_USER_AGENT'] . PHP_EOL;
        $httpHeader .= 'Content-Length: ' . strlen($post) . PHP_EOL;
        $httpHeader .= 'Host: ' . $ip . ':' . (int)$port . PHP_EOL;
        $httpHeader .= 'Connection: Close' . PHP_EOL;
        $httpHeader .= 'Cache-Control: no-cache' . PHP_EOL;
        $httpHeader .= 'Cookie: ' . $cookie . PHP_EOL . PHP_EOL;
        $httpHeader .= $post;
        return $httpHeader;
    }

    /**
     * HTTP GET方式的header头部信息封装
     * @param  string $url 链接地址
     * @param  string $post post内容，没有则为空
     * @param  string $ip ip地址
     * @param  int $port 端口号
     * @param  string $cookie cookie
     * @return string
     */
    private function _getHeader($url, $post, $ip, $port = 80, $cookie = '') {
        $httpHeader = '';
        $httpHeader .= 'GET ' . $url . ' HTTP/1.0' . PHP_EOL;
        $httpHeader .= 'Accept: */*' . PHP_EOL;
        $httpHeader .= 'Accept-Language: zh-cn' . PHP_EOL;
        $httpHeader .= 'Content-Type: application/x-www-form-urlencoded' . PHP_EOL;
        $httpHeader .= 'User-Agent: ' . $_SERVER['HTTP_USER_AGENT'] . PHP_EOL;
        $httpHeader .= 'Host: ' . $ip . ':' . (int)$port . PHP_EOL;
        $httpHeader .= 'Connection: Close' . PHP_EOL;
        $httpHeader .= 'Cookie: ' . $cookie . PHP_EOL . PHP_EOL;
        return $httpHeader;
    }

    /**
     * 外部接收的数据进行内部处理
     * @param  string $url 链接地址
     * @param  array $post post内容，没有则为空
     * @param  string $ip ip地址
     * @param  int $timeout 超时时间
     * @param  string $cookie cookie
     * @param  int $freadLen 长度
     * @return array
     */
    private function _parseParam($url, $post, $ip, $timeout, $cookie, $freadLen) {
        $tempArr = array();
        $tempArr['url'] = (string)$url;
        $tempArr['post'] = $this->_parseArrToUrlstr((array)$post);
        $tempArr['ip'] = (string)$ip;
        $tempArr['timeout'] = (int)$timeout;
        $tempArr['port'] = $this->_parsePort($url, $ip);
        $tempArr['cookie'] = (string)$cookie;
        $tempArr['freadLen'] = (int)$freadLen;
        $tempArr['ip'] = ($tempArr['ip'] == '') ? $this->_parseUrlToHost($tempArr['url']) : $tempArr['ip'];
        return $tempArr;
    }

    /**
     * 将数组参数转化成URL传输的参数
     * 数组：array("age"=>"我是test");
     * 转化：age=10&name=test 多维：a[age]=10&a[name]=test
     * @param  array $arr 数组
     * @return string
     */
    private function _parseArrToUrlstr($arr) {
        if (!is_array($arr))
            return '';
        return http_build_query($arr, 'flags_');
    }

    /**
     * 获取URL中的HOST信息
     * URL：http://www.xxx.com/index.php
     * 返回: www.xxx.com
     * @param  string $url URL链接
     * @return string
     */
    private function _parseUrlToHost($url) {
        $parse = @parse_url($url);
        $reg = '/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/';
        if (empty($parse) || preg_match($reg, trim($url)) == false)
            return '';
        return str_replace(array('http://', 'https://'), array('', 'ssl://'), $parse['scheme'] . '://') . $parse['host'];
    }

    /**
     * 获取端口号,返回INT类型端口，如果端口不存在，为设置的默认端口号
     * @param  string $url URL链接
     * @param  string $ip IP地址
     * @return int
     */
    private function _parsePort($url, $ip) {
        $temp = array();
        if ($ip !== '') {
            if (strpos($ip, ':') === false) {
                $tempPort = $this->_defaultPort;
            }
            else {
                $temp = explode(':', $ip);
                $tempPort = $temp[1];
            }
        }
        else {
            $temp = @parse_url($url);
            $tempPort = $temp['port'];
        }
        return ((int)$tempPort == 0) ? $this->_defaultPort : $tempPort;
    }
}