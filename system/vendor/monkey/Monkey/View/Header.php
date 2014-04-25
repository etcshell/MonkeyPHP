<?php
namespace Monkey\View;

class Header
{
    private
        $heads=null,
        $charset='UTF-8',
        $keywords=null,
        $description=null
    ;

    private function __construct(){}

    /**
     * @return Header
     */
    public static function getInstance()
    {
        static $self;
        !$self and $self=new self();
        return $self;
    }

    public function setCharset($charset='UTF-8')
    {
        $this->charset=$charset;
        return $this;
    }

    /**
     * 获取响应HtmlHead完整头部
     * @return string
     */
    public function get()
    {
        $this->getKeywords();
        $this->getDescription();
        if(empty($this->heads))return null;
        ksort($this->heads);
        $heads=implode("\n", $this->heads);
        //$this->heads=null;
        return $heads;
    }

    /**
     * 添加网页关键字
     * @param $keywords
     */
    public function keywords($keywords)
    {
        $this->keywords[]=$this->htmlspecialchars($keywords);
    }

    private function getKeywords()
    {
        if(!$this->keywords) return;
        if(is_array($this->keywords)){
            $this->keywords=implode(',', $this->keywords);
        }
        $this->heads[1]= '<meta name="Keywords" content="'.$this->keywords.'" />';
        $this->keywords=null;
    }

    /**
     * 添加网页简介
     * @param $description
     */
    public function description($description)
    {
        $this->description[]=$this->htmlspecialchars($description);
    }

    private function getDescription()
    {
        if(!$this->description) return;
        if(is_array($this->description)){
            $this->description=implode(',', $this->description);
        }
        $this->heads[2]= '<meta name="Description" content="'.$this->description.'" />';
        $this->description=null;
    }

    /**
     * 设置网页作者
     * @param $author
     */
    public function author($author)
    {
        $this->heads[3]= '<meta name="Author" content="'.$this->htmlspecialchars($author).'" />';
    }

    /**
     * 设置网页版权信息
     * @param $copyright
     */
    public function copyright( $copyright )
    {
        $copyright=$this->htmlspecialchars($copyright);
        $this->heads[4]= '<meta name="Copyright" content="'.$copyright.'" />';
    }

    /**
     * 设置搜索机器人向导
     * @param string $robots
     */
    public function robots($robots='all')
    {
        $robots=strtolower($robots);
        if(!preg_match('/^all|none|index|noindex|follow|nofollow$/',$robots)){
            $robots='all';
        }
        $this->heads[5]='<meta name="Robots" content="'.$robots.'" />';
    }

    /**
     * 设置网页文档类型
     * @param $contentType 内容属性，默认为 'text/html' 类型
     * @param string $charset 页面编码，默认使用配置中的设置
     */
    public function contentType($contentType='text/html', $charset=null)
    {
        $charset===null and $charset=$this->charset;
        $this->heads[6]= '<meta http-equiv="Content-Type" content="'.$contentType.'; charset='.$charset.'" />';
    }

    /**
     * 设置网页文档的自然语言
     * @param $contentLanguage
     */
    public function contentLanguage($contentLanguage)
    {
        $this->heads[7]= '<meta http-equiv="Content-Language" content="'.$contentLanguage.'" />';
    }

    /**
     * 设置网页刷新时间（及跳转网址）
     * @param $n
     * @param null $url
     */
    public function refresh($n, $url=null)
    {
        $url!==null and $url='; url='.$url;
        $this->heads[8]= '<meta http-equiv="Refresh" content="'.$n.$url.'" />';
    }

    /**
     * 设置网页过期时间
     * @param $unixTime
     */
    public function expires($unixTime)
    {
        $this->heads[9]= '<meta http-equiv="Expires" content="'.gmdate('r',$unixTime).'" />';
    }

    /**
     * 设置网页缓存控制
     * @param $pragma
     */
    public function pragma($pragma)
    {
        $this->heads[10]= '<meta http-equiv="Pragma" content="'.$pragma.'" />';
    }

    /**
     * 设置网页显示方式
     * @param $target 有_blank|_top|_self|_parent四种
     */
    public function windowTarget($target)
    {
        $target=strtolower($target);
        if(!preg_match('/^_blank|_top|_self|_parent$/',$target)){
            return;
        }
        $this->heads[11]= '<meta http-equiv="Window-target" content="'.$target.'" />';
    }

    /**
     * 设置网页进入效果（仅IE原生支持）
     * @param $duration
     * @param $transition
     */
    public function pageEnter($duration, $transition)
    {
        $this->heads[12]= '<meta http-equiv="Page-Enter" content="revealTrans(duration='.$duration.', transition='.$transition.')" />';
    }

    /**
     * 设置网页退出效果（仅IE原生支持）
     * @param $duration
     * @param $transition
     */
    public function pageExit($duration, $transition)
    {
        $this->heads[13]= '<meta http-equiv="Page-Exit" content="revealTrans(duration='.$duration.', transition='.$transition.')" />';
    }

    /**
     * 设置网页中的脚本语言类型
     * @param string $type
     */
    public function scriptType($type='text/javascript')
    {
        $this->heads[14]= '<meta http-equiv="Content-Script-Type" content="'.$type.'" />';
    }

    /**
     * 设置网页标题
     * @param string $title
     */
    public function title($title)
    {
        $title=$this->htmlspecialchars($title);
        $this->heads[15]= '<title>'.$title.'</title>';
    }

    /**
     * 设置网页中所有网址的基链接
     * @param $url
     * @param $target
     */
    public function base($url, $target)
    {
        $this->heads[16]='<Base href="'.$url.'" target="'.$target.'" />';
    }

    /**
     * 设置网页在地址栏显示的图标
     * @param $icoFile
     */
    public function faviconIco($icoFile)
    {
        $this->heads[17]='<Link href="'.$icoFile.'" rel="Shortcut Icon">';
    }

    /**
     * 添加css文件
     * @param $cssFile
     */
    public function link($cssFile)
    {
        $this->heads[18][]= '<LINK href="'.$cssFile.'" rel="stylesheet" type="text/css">';
    }

    /**
     * 添加css文件
     * @param $cssFile
     */
    public function cssFile($cssFile)
    {
        $this->link($cssFile);
    }

    /**
     * 添加js文件
     * @param $jsFile
     */
    public function jsFile($jsFile)
    {
        $this->heads[19][]= '<script src="'.$jsFile.'" type="text/javascript"></script>';
    }

    /**
     * 添加js文本
     * @param $jsText
     */
    public function jsText($jsText)
    {
        $this->heads[20][]= '<script type="text/javascript" >'.PHP_EOL.$jsText.PHP_EOL.'</script>';
    }

    private function htmlspecialchars($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, $this->charset);
    }

}