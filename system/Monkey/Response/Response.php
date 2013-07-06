<?php
namespace Monkey\Response;

use Monkey\_Interface\Component;

/**
 * 响应类\Monkey\Response\Response
 * @package    \Monkey\Response
 * @author     HuangYi
 * @copyright  Copyright (c) 2011-07-01——2013-03-30
 * @license    New BSD License
 * @version    $Id: \Monkey\Response\Response.php 版本号 2013-03-30 $
 *
 * 相应状态码信息描述：
 * 1xx：信息，请求收到，继续处理
 * 2xx：成功，行为被成功地接受、理解和采纳
 * 3xx：重定向，为了完成请求，必须进一步执行的动作
 * 4xx：客户端错误，请求包含语法错误或者请求无法实现
 * 5xx：服务器错误，服务器不能实现一种明显无效的请求
 */
class Response implements Component
{

    /**
     * @var \Monkey\Response\Status
     */
    private $oStatus=null;

    /**
     * @var \Monkey\Response\HttpHeader
     */
    private $oHttpHeader=null;

    private
        $config,
        $charset,//输出的编码
        $content,//内容
        $isGzip,
        $headerOnly  = false,
        $redirectUrl=null//立即跳转网址
    ;
    /**
     * @var \Monkey\Monkey
     */
    private $oMonkey;

    private function __construct(){ }

    /**
     * 获取Response实例
     * @return \Monkey\Response\Response
     */
    public static function _instance()
    {
        static $self;
        !$self and $self=new self();
        return $self;
    }

    /**
     * 组件初始化注入
     * @param \Monkey\Monkey|null $monkey 依赖类
     * @param mixed|null $config 配置
     * @return mixed
     */
    public function _initialize($monkey = null, $config = null)
    {
        $this->oMonkey=$monkey;
        if( 'HEAD' == $monkey->getRequest()->getMethod() )
        {
            $this->setHeaderOnly(true);
        }
        $this->config=$config;
        $this->charset= $this->getConfig('charset', 'UTF-8');
        $this->oHttpHeader->setCharset($this->charset);
        if($this->getConfig('gzip_enable', false))
        {
            $this->setGzip();
        }
    }

    /**
     * 获取响应状态对象
     * @return \Monkey\Response\Status
     */
    public function Status()
    {
        if($this->oStatus===null)
        {
            $this->oStatus= new Status();
        }
        return $this->oStatus;
    }

    /**
     * 是否设置了响应状态
     * @return bool
     */
    public function hasStatus()
    {
        return $this->oStatus!==null;
    }

    /**
     * 获取响应协议头对象
     * @return \Monkey\Response\HttpHeader
     */
    public function HttpHeader()
    {
        if($this->oHttpHeader===null)
        {
            $this->oHttpHeader= new HttpHeader();
        }
        return $this->oHttpHeader;
    }

    /**
     * 是否设置了Http协议响应头
     * @return bool
     */
    public function hasHttpHeader()
    {
        return $this->oHttpHeader!==null;
    }

    /**
     * 将响应信息设置为仅仅只有头部
     * 注意：这将会清除已经添加的响应内容
     * @param bool $isHeaderOnly
     */
    public function setHeaderOnly($isHeaderOnly = true)
    {
        $this->headerOnly = (boolean) $isHeaderOnly;
        $this->clearContent();
    }

    /**
     * 判断是否仅需返回响应头
     * @return bool
     */
    public function isHeaderOnly()
    {
        return $this->headerOnly;
    }

    /**
     * 设置立即重定向地址
     * @param string $url 重定向的地址
     * @return void
     */
    public function setRedirectUrl( $url )
    {
        $this->redirectUrl=$url;
        //header('Location:'.$url,true,302);
    }

    /**
     * 设置响应正文内容
     * @param $content
     */
    public function setContent($content)
    {
        $this->content=$content;
    }

    /**
     * 获取响应正文内容
     * @return string
     */
    public function getContent()
    {
        return  $this->content;
    }

    /**
     * 清除响应正文内容
     */
    public function clearContent()
    {
        $this->content='';
    }

    /**
     * 发送响应正文内容
     */
    public function sendContent()
    {
        if($this->headerOnly) return;
        if(!headers_sent())
        {
            $this->sendHttpHeaders();
        }
        if($this->isGzip)
            echo gzencode($this->content,$this->getConfig('gzip_level', 6));
        else
            echo $this->content;
        $this->content='';
    }

    /**
     * 发送：HTTP状态、headers
     */
    public function sendHttpHeaders()
    {
        if(headers_sent()) return false;

        if($this->redirectUrl)
        {
            header('Location:'.$this->redirectUrl,true,302);
        }
        else
        {
            $status= $this->oStatus ? $this->oStatus->get() : 'HTTP/1.0 200 ok';
            header($status);
            if($this->oHttpHeader!==null and $headers=$this->oHttpHeader->getAll())
            {
                foreach ($headers as $name => $value)
                {
                    header($name.': '.$value);
                }
            }
        }

        $cookies= $this->oMonkey->getCookie()->getNewCookies();
        // cookies
        foreach ($cookies as $name => $ck)
        {
            setrawcookie($name, $ck['value'], $ck['expire'], $ck['path'], $ck['domain'], $ck['secure'], $ck['httpOnly']);
        }
    }

    private function getConfig($name, $defaultValue=null)
    {
        return isset($this->config[$name]) ? $this->config[$name] : $defaultValue;
    }

    /**
     * 启用gzip压缩输出内容。
     */
    private function setGzip() {
        if(extension_loaded("zlib") && !ini_get('zlib.output_compression')
            && strstr($_SERVER['HTTP_ACCEPT_ENCODING'],'gzip')!== FALSE)
        {
            $this->isGzip=true;
            $this->HttpHeader()->setContentEncoding('gzip');
        }
    }
}
