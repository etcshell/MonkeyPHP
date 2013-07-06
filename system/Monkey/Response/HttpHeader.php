<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Administrator
 * Date: 13-5-9
 * Time: 下午3:57
 * To change this template use File | Settings | File Templates.
 */

namespace Monkey\Response;


class HttpHeader
{
    private
        $charset,//输出的编码
        $headers = array()//设置输出的头部信息
    ;


    /*************************以下是响应头的集成设置方案*************************/
    /**
     * 设置强制浏览器不缓存
     */
    public function setNoCache()
    {
        $this->headers['Expires']= gmdate('r',TIME);
        $this->setCacheControl('no-cache');
        $this->headers['Pragma']= 'no-cache';
    }

    /**
     * 强制浏览器缓存一段时间
     * @param string $etag 当前请求的缓存标志
     * @param int $expiresTime 时间戳 缓存过期时间点
     * @param int $lastModified 时间戳 最后修改的时间点，默认使用缓存过期时间点
     */
    public function setCache($etag, $expiresTime, $lastModified=null)
    {
        $expiresTime=gmdate('r',$expiresTime);
        $this->headers['Expires']= $expiresTime;//将响应内容缓存到浏览器中
        $this->headers['Last-Modified']= $lastModified ? gmdate('r',$lastModified) : $expiresTime;//防止浏览器自动刷新
        $this->headers['Etag']=$etag;//防止浏览器人为刷新
        $this->setCacheControl('max-age', $expiresTime-TIME);
    }

    /**
     * 设置文件下载
     * @param string $mime_type 文件的mime类型，请查表设置
     * @param string $filename 文件名，含扩展名
     * @param string $charset 文件字符集，一般在Response里配置了，这里就不再配置了。
     */
    public function setDownload($mime_type, $filename, $charset='')
    {
        $this->setContentType($mime_type);
        $charset and $charset= '; charset='.$charset;
        $this->set('Content-Disposition','attachment; filename="' . $filename . '" '.$charset);
        //header('Content-Disposition: attachment; filename="' . $filename . '.xls"; charset=utf-8');
    }


    /*************************以下是响应头的详细设置方法*************************/
    /**
     * 添加响应头header的缓存控制部分.
     * @param string $name
     * @param string $value
     */
    public function setCacheControl($name, $value = null)
    {
        $cacheControl = $this->_get('Cache-Control');
        $currentHeaders = array();
        if ($cacheControl)
        {
            foreach (preg_split('/\s*,\s*/', $cacheControl) as $tmp)
            {
                $tmp = explode('=', $tmp);
                $currentHeaders[$tmp[0]] = isset($tmp[1]) ? $tmp[1] : null;
            }
        }
        $currentHeaders[strtr(strtolower($name), '_', '-')] = $value;
        $headers = array();
        foreach ($currentHeaders as $key => $value)
        {
            $value= $value!==null ? '='.$value : '';
            $headers[] = $key.$value;
        }
        $this->set('Cache-Control', implode(', ', $headers));
    }

    /**
     * 设置连接控制属性
     * @param bool $isKeepAlive 保持连接为TRUE，响应结束后断开为FALSE
     */
    public function setConnection($isKeepAlive=true)
    {
        $this->headers['Connection']= $isKeepAlive ? 'Keep-Alive' : 'close';
    }

    /**
     * 设置消息发送的时间
     * @param $unixTime
     */
    public function setDate($unixTime)
    {
        $this->headers['Date']= gmdate('r',$unixTime);
    }

    /**
     * 设置HTTP/1.1协议中的Pragma（缓存控制指令）
     * @param string $value
     */
    public function setPragma($value= 'no-cache')
    {
        $this->headers['Pragma']= $value;
    }

    /**
     * 设置立即跳转网址
     * 注意，本设置将清除已经设置的其它响应头！
     * @param string $url
     */
    public function setLocation($url)
    {
        $this->headers=null;
        $this->headers['Location']=$url;
    }

    /**
     * 设置服务器的软件信息
     * @param $value
     */
    public function setServer($value)
    {
        $this->set('Server', $value);
    }

    /**
     * 设置文档的压缩编码的格式
     * @param string $encoding 如 gzip | none
     */
    public function setContentEncoding($encoding='none')
    {
        $this->headers['Content-Encoding']=$encoding;
    }

    /**
     * 设置响应文档的自然语言
     * @param string $language en 或 zh-cn 等
     */
    public function setContentLanguage($language)
    {
        $this->headers['Content-Language']=$language;
    }

    /**
     * 设置实体内容的长度。只有当浏览器使用持久HTTP连接时才需要这个数据。
     * 注意会同时设置连接控制为持久连接
     * @param int $length
     */
    public function setContentLength($length)
    {
        $this->headers['Content-Length']=$length;
        $this->setConnection(ture);
    }

    /**
     * 设置响应内容的MD5值
     * 一般不用
     * @param $md5
     */
    public function setContentMD5($md5)
    {
        $this->headers['Content-MD5']=$md5;
    }

    /**
     * 设置响应内容的范围
     * 如多点下载的片段
     * @param string $unit 计量单位
     * @param int $begin 开始字节处
     * @param null $length 发送长度
     * @param int $total 文档总字节数
     */
    public function setContentRange($unit='bytes', $begin=0, $length=null, $total=0)
    {
        $this->set('Content-Range',$unit.' '.(string)$begin.'-'.($begin+$length).'/'.$total);
    }

    /**
     * 设置响应头的Content-Type段
     * @param string
     */
    public function setContentType($value)
    {
        // add charset if needed (only on text content)
        if (false === stripos($value, 'charset') && (0 === stripos($value, 'text/') || strlen($value) - 3 === strripos($value, 'xml')))
        {
            $value .= '; charset='.$this->charset;
        }
        $this->headers['Content-Type']= $value;
    }

    /**
     * 指示浏览器如何显示文档内容，是 MIME 协议的扩展。
     * 一般用与下载文件，或在线显示文件（非html文档，html文档会自动在线显示）
     * @param string $filename 文件名
     * @param string|null $type 类型 attachment | inline ，为空时自动填充为 attachment
     * @param string|null $charset 文档的字符集
     */
    public function setContentDisposition($filename, $type=null, $charset=null)
    {
        $charset and $charset='; charset='.$charset;
        !$type and $type='attachment';
        $this->headers['Content-Disposition']=$type.'; filename="' . $filename . '"'.$charset;
    }

    /**
     * 设置响应内容的标志，以便在重新请求而标志的内容不变时直接回绝浏览器
     * 告诉浏览器，请求的内容没变，不要老是刷新了！
     * @param string $etag 标志
     */
    public function setEtag($etag)
    {
        $this->headers['Etag']=$etag;
    }

    /**
     * 设置响应内容的过期时间，这段时间之内浏览器不会向服务器刷新请求，除非人为刷新操作
     * @param int $unixTime
     */
    public function setExpires($unixTime)
    {
        $this->headers['Expires']=gmdate('r',$unixTime);
    }

    /**
     * 文档的最后改动时间
     * 在这个时间到期时会向服务器询问是否有新版的文档，如果有则提出刷新请求；
     * 到期刷新时，如果服务器响应状态为304（Not Modified）则不会发生真的刷新请求。
     * 即刷新前可以用这个时间做一个判断刷新是否有必要。
     * @param $unixTime
     */
    public function setLastModified($unixTime)
    {
        $this->headers['Last-Modified']=gmdate('r',$unixTime);
    }

    /**
     * 定义新的实体头，但是这些域可能无法未接受方识别。
     * 开发者只有在请求头中自己编程使用
     * @param $extension
     */
    public function setExtensionHeader($extension)
    {
        $this->headers['Extension-Header']=$extension;
    }

    /**
     * 设置服务器支持哪些请求方法（如GET、POST等）
     * @param string $method 单个设置 如 "GET"； 多个设置 如 "GET, POST" 等中间用逗号隔开即可
     */
    public function setAllow($method)
    {
        $allow= $this->headers['Allow'];
        $this->headers['Allow']= $allow ? $allow.', '.$method : $method;
    }

    /**
     * 向浏览器申明是否支持发送文档的一部分（片段）
     * @param string $type bytes：表示支持发送以字节为单位的片段，none：表示不支持片段发送。
     */
    public function setAcceptRanges($type='bytes')
    {
        $this->headers['Accept-Ranges']=$type;//none表示不支持发送片段
    }

    /**
     * 表明浏览器多长时间后请求最新的页面
     * 即延时跳转或刷新
     * 一般不在这里使用，在HtmlHead里使用更方便（可以给出一些提示，如未登录不可以下载，5秒后跳转到登录页之类）
     * @param int $time 秒数
     * @param string|null $url 为空时表示刷新本页，非空时表示跳转到该页面。
     */
    public function setRefresh($time, $url=null)
    {
        $url and $url='; URL='.$url;
        $this->headers['Refresh']=$time.$url;
    }

    /**
     * 添加响应头header的vary部分.
     * 用于表示使用服务器驱动的协商从可用的响应表示中选择响应实体。
     * @param string $value
     */
    public function setVary($value)
    {
        $vary = $this->_get('Vary');
        $currentHeaders = array();
        if ($vary)
        {
            $currentHeaders = preg_split('/\s*,\s*/', $vary);
        }
        $value = $this->formatName($value);

        if (!in_array($value, $currentHeaders))
        {
            $currentHeaders[] = $value;
            $this->set('Vary', implode(', ', $currentHeaders));
        }
    }

    /**
     * 设置Cookies的过期时间
     * @param int $unixTime
     */
    public function setCookieExpires($unixTime)
    {
        $this->headers['Set-Cookie']=gmdate('r',$unixTime);
    }

    /**
     * 设置需要浏览器在请求头Authorization中提供什么类型的授权信息。
     * @param string $need
     */
    public function setWwwAuthenticate($need)
    {
        $this->headers['WWW-Authenticate']=$need;// 如 BASIC realm=＼"executives＼"
    }

    /**
     * 告诉浏览器多久后自动刷新
     * @param int $time 当小于 2592000（一个月） 表示多少秒后刷新，当大于或等于 2592000 表示精确的格林威治时间之时刷新
     */
    public function setRetryAfter($time)
    {
        $this->headers['Retry-After']= $time<2592000? $time : gmdate('r',$time);
    }

    /**
     * 设置关于响应状态的补充信息。
     * @param $msg
     */
    public function setWarning($msg)
    {
        $this->headers['Warning']=$msg;
    }


    /*************************以下是响应头的辅助设置方法*************************/
    /**
     * 通用的响应头信息设置方法
     * @param string $name 响应头的字段名称
     * @param string $value 响应头的字段取值
     * @param boolean $replace 是否是替换方式设置
     */
    public function set($name, $value, $replace = true)
    {
        $name = $this->formatName($name);
        $this->_set($name, $value, $replace);
    }

    /**
     * 获取已设置的指定的响应头信息
     * @param string $name 响应头的字段名称
     * @param null|string $default 默认的字段取值
     * @return mixed
     */
    public function get($name, $default = null)
    {
        return $this->_get($this->formatName($name), $default);
    }

    /**
     * 获取已设置的全部headers响应头信息
     * @return string HTTP headers
     */
    public function getAll()
    {
        return $this->headers;
    }

    /**
     * 清除全部headers响应头信息
     */
    public function clear()
    {
        $this->headers=null;
        $this->headers=array();
    }

    /**
     * 判断是否设置了指定的响应头
     * @param string $name 响应头的名称
     * @return boolean
     */
    public function has($name)
    {
        return $this->_has($this->formatName($name));
    }

    /**
     * 设置响应内容的字符集
     * @param string $charset
     */
    public function setCharset($charset='UTF-8')
    {
        $this->charset=$charset;
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

    private function _set($name, $value, $replace = true)
    {
        if ('Content-Type' == $name)
        {
            if ($replace || !$this->_get('Content-Type', null))
            {
                $this->setContentType($value);
            }
            return;
        }
        if (!$replace)
        {
            $current = $this->_get($name,'');
            $value = ($current ? $current.', ' : '').$value;
        }
        $this->headers[$name]=$value;
    }

    private function _get($name, $default = null)
    {
        return $this->_has($name) ? $this->headers[$name] : $default;
    }

    private function _has($name)
    {
        return array_key_exists($name, $this->headers);
    }

}
