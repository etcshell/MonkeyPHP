<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Administrator
 * Date: 13-5-19
 * Time: 上午8:58
 * To change this template use File | Settings | File Templates.
 */

namespace Monkey\Html;

class Head
{
    private
        $heads=null,
        $charset,
        $keywords=null,
        $description=null
    ;

    public function __construct($charset='UTF-8')
    {
        $this->charset=$charset;
    }

    /**
     * 获取响应HtmlHead头
     * @return array
     */
    public function getAll()
    {
        $this->getKeywords();
        $this->getDescription();
        ksort($this->heads);
        return $this->heads;
    }

    /**
     * 设置网页关键字
     * @param $keywords
     */
    public function setMetaNameKeywords($keywords)
    {
        $this->keywords=$this->htmlspecialchars($keywords);
    }

    /**
     * 添加网页关键字
     * @param $keywords
     */
    public function addMetaNameKeywords($keywords)
    {
        if($this->keywords!==null && !is_array($this->keywords))
        {
            $this->keywords=array($this->keywords);
        }
        $this->keywords[]=$this->htmlspecialchars($keywords);
    }

    private function getKeywords()
    {
        if(!$this->keywords) return;
        if(is_array($this->keywords))
        {
            $this->keywords=implode(',', $this->keywords);
        }
        $this->heads[1]= '<meta name="Keywords" contect="'.$this->keywords.'">';
    }

    /**
     * 设置网页简介
     * @param $description
     */
    public function setMetaNameDescription($description)
    {
        $this->description= $this->htmlspecialchars($description);
    }

    /**
     * 添加网页简介
     * @param $description
     */
    public function addMetaNameDescription($description)
    {
        if($this->description!==null && !is_array($this->description))
        {
            $this->description=array($this->description);
        }
        $this->description[]=$this->htmlspecialchars($description);
    }

    private function getDescription()
    {
        if(!$this->description) return;
        if(is_array($this->description))
        {
            $this->description=implode(',', $this->description);
        }
        $this->heads[2]= '<meta name="Description" contect="'.$this->description.'">';
    }

    /**
     * 设置网页作者
     * @param $author
     */
    public function setMetaNameAuthor($author)
    {
        $author=$this->htmlspecialchars($author);
        $this->heads[3]= '<meta name="Author" contect="'.$author.'">';
    }

    /**
     * 设置网页版权信息
     * @param $copyright
     */
    public function setMetaNameCopyright( $copyright )
    {
        $copyright=$this->htmlspecialchars($copyright);
        $this->heads[4]= '<meta name="Copyright" contect="'.$copyright.'">';
    }

    /**
     * 设置搜索机器人向导
     * @param string $robots
     */
    public function setMetaNameRobots($robots='all')
    {
        $robots=strtolower($robots);
        if(!preg_match('/^all|none|index|noindex|follow|nofollow$/',$robots))
        {
            $robots='all';
        }
        $this->heads[5]='<meta name="Robots" contect="'.$robots.'">';
    }

    /**
     * 设置网页文档类型
     * @param $contentType
     * @param null $charset
     */
    public function setMetaHttpEquivContentType($contentType, $charset=null)
    {
        $charset===null and $charset=$this->charset;
        $this->heads[6]= '<meta http-equiv="Content-Type" contect="'.$contentType.'; charset='.$charset.'">';
    }

    /**
     * 设置网页文档的自然语言
     * @param $contentLanguage
     */
    public function setMetaHttpEquivContentLanguage($contentLanguage)
    {
        $this->heads[7]= '<meta http-equiv="Content-Language" contect="'.$contentLanguage.'">';
    }

    /**
     * 设置网页刷新时间（及跳转网址）
     * @param $n
     * @param null $url
     */
    public function setMetaHttpEquivRefresh($n, $url=null)
    {
        $url!==null and $url='; url='.$url;
        $this->heads[8]= '<meta http-equiv="Refresh" contect="'.$n.$url.'">';
    }

    /**
     * 设置网页过期时间
     * @param $unixTime
     */
    public function setMetaHttpEquivExpires($unixTime)
    {
        $this->heads[9]= '<meta http-equiv="Expires" contect="'.gmdate('r',$unixTime).'">';
    }

    /**
     * 设置网页缓存控制
     * @param $pragma
     */
    public function setMetaHttpEquivPragma($pragma)
    {
        $this->heads[10]= '<meta http-equiv="Pragma" contect="'.$pragma.'">';
    }

    /**
     * 设置网页显示方式
     * @param $target 有_blank|_top|_self|_parent四种
     */
    public function setMetaHttpEquivWindowTarget($target)
    {
        $target=strtolower($target);
        if(!preg_match('/^_blank|_top|_self|_parent$/',$target))
        {
            return;
        }
        $this->heads[11]= '<meta http-equiv="Window-target" contect="'.$target.'">';
    }

    /**
     * 设置网页进入效果（仅IE原生支持）
     * @param $duration
     * @param $transition
     */
    public function setMetaHttpEquivPageEnter($duration, $transition)
    {
        $this->heads[12]= '<meta http-equiv="Page-Enter" contect="revealTrans(duration='.$duration.', transition='.$transition.')">';
    }

    /**
     * 设置网页退出效果（仅IE原生支持）
     * @param $duration
     * @param $transition
     */
    public function setMetaHttpEquivPageExit($duration, $transition)
    {
        $this->heads[13]= '<meta http-equiv="Page-Exit" contect="revealTrans(duration='.$duration.', transition='.$transition.')">';
    }

    /**
     * 设置网页中的脚本语言类型
     * @param string $type
     */
    public function setMetaHttpEquivContentScriptType($type='text/javascript')
    {
        $this->heads[14]= '<meta http-equiv="Content-Script-Type" contect="'.$type.'">';
    }

    /**
     * 设置网页标题
     * @param string $title
     */
    public function setTitle($title)
    {
        $title=$this->htmlspecialchars($title);
        $this->heads[15]= '<title>'.$title.'</title>';
    }

    /**
     * 设置网页中所有网址的基链接
     * @param $url
     * @param $target
     */
    public function setBase($url, $target)
    {
        $this->heads[16]='<Base href="'.$url.'" target="'.$target.'">';
    }

    /**
     * 设置网页在地址栏显示的图标
     * @param $icoFile
     */
    public function setFaviconIco($icoFile)
    {
        $this->heads[17][]='<Link href="'.$icoFile.'" rel="Shortcut Icon">';
    }

    /**
     * 添加css文件
     * @param $cssFile
     */
    public function addLink($cssFile)
    {
        $this->heads[18][]= '<LINK href="'.$cssFile.'" rel="stylesheet" type="text/css">';
    }

    /**
     * 添加css文件
     * @param $cssFile
     */
    public function addStylesheet($cssFile)
    {
        $this->addLink($cssFile);
    }

    /**
     * 添加js文件
     * @param $jsFile
     */
    public function addScriptFile($jsFile)
    {
        $this->heads[19][]= '<script src="'.$jsFile.'" type="text/javascript"></script>';
    }

    /**
     * 添加js文本
     * @param $jsText
     */
    public function addScriptText($jsText)
    {
        $this->heads[20][]= '<script type="text/javascript" >'.PHP_EOL.$jsText.PHP_EOL.'</script>';
    }

    private function htmlspecialchars($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, $this->charset);
    }

}