<?php
namespace Library;

/**
 * page
 * @category   分页栏生成器，支持ajax（可选）
 * @package    核心扩展包
 * @author     HuangYi
 * @copyright  Copyright (c) 2012-4-1——至今
 * @license    New BSD License
 * @version    $Id: page.class.php 版本号 2013-1-1 $
 *
 */
class Page {
    private $_current_page=0;//当前页
    private $_url_label='';//url标签，用来控制url页。比如说xxx.php?page={page}
    private $_pageTag='page';//page标记，用来控制url页。比如说xxx.php?page={page}中的{page}
    private $_pageTag_fix;
    private $_style=array();
    private $_isAjax=false;//是否支持AJAX分页模式
    private $_ajaxActionName='';//AJAX动作名
    private $_pageBarNum_limit=10;//控制记录条的个数。
    private $_totalPage=0;//总页数
    private $_nextPage_tag='下一页';//下一页
    private $_prePage_tag='上一页';//上一页
    private $_firstPage_tag='首页';//首页
    private $_lastPage_tag='尾页';//尾页
//    public $preBar_tag='<<';//上一分页条
//    public $nextBar_tag='>>';//下一分页条
    /**
     * 分页类
     * @param int $current_page         当前页码
     * @param string $url_label         url标签，用来控制url页。比如说xxx.php?page={page}
     * @param string $page_tag          page标记，用来控制url页。比如说xxx.php?page={page}中的{page}
     * @param string $page_tag_prefix   page标记的前缀，第一页时，用来省略page标记前缀的：比如说xxx.php?page={page}中的page=
     * @param array $link_style         分页导航条中各元素的链接样式列表array('firstPage','prePage','currentPage','nextPage','lastPage','pageItem');
     * @param string $ajax_action       js的方法名，为空时即不使用ajax
     */
    public function __construct($current_page,$url_label,$page_tag,$page_tag_prefix=null,$link_style=null,$ajax_action=null){
        $this->_current_page=$current_page;
        $this->_url_label=$url_label;
        $this->_pageTag=$page_tag;//设置pageTag
        if(!empty($page_tag_prefix) && is_string($page_tag_prefix)){
            $this->_pageTag_fix=$page_tag_prefix;
        }  else {
            $this->_pageTag_fix='';
        }
        $this->_style['firstPage']=empty($link_style['firstPage'])?'firstPage':$link_style['firstPage'];
        $this->_style['prePage']=empty($link_style['prePage'])?'prePage':$link_style['prePage'];
        $this->_style['currentPage']=empty($link_style['currentPage'])?'currentPage':$link_style['currentPage'];
        $this->_style['nextPage']=empty($link_style['nextPage'])?'nextPage':$link_style['nextPage'];
        $this->_style['lastPage']=empty($link_style['lastPage'])?'lastPage':$link_style['lastPage'];
        $this->_style['pageItem']=empty($link_style['pageItem'])?'pageItem':$link_style['pageItem'];
        $this->setAjax($ajax_action);//设置AJAX模式
    }
    /**
     * 设置按钮为文本
     * @param string $first '首页'文本
     * @param string $pre '上一页'文本
     * @param string $next '下一页'文本
     * @param string $last '尾页'文本
     * @return $this 
     */
    public function setButtonAsText($first,$pre,$next,$last){
        $this->_firstPage_tag= (!empty($first) && is_string($first)) ? $first : '首页';
        $this->_prePage_tag= (!empty($pre) && is_string($pre)) ? $pre : '上一页';
        $this->_nextPage_tag= (!empty($next) && is_string($next)) ? $next : '下一页';
        $this->_lastPage_tag= (!empty($last) && is_string($last)) ? $last : '尾页';
        return $this;
    }
    /**
     * 设置按钮为图像
     * @param string $first '首页'图像地址
     * @param string $pre '上一页'图像地址
     * @param string $next '下一页'图像地址
     * @param string $last '尾页'图像地址
     * @return $this 
     */
    public function setButtonAsImage($first,$pre,$next,$last){
        $this->_firstPage_tag= (!empty($first) && is_string($first)) ?
                '<img src="'.$first.'" width="17" height="11" />' : '首页';
        $this->_prePage_tag= (!empty($pre) && is_string($pre)) ?
                '<img src="'.$pre.'" width="16" height="11" />' : '上一页';
        $this->_nextPage_tag= (!empty($next) && is_string($next)) ?
                '<img src="'.$next.'" width="14" height="11" />' : '下一页';
        $this->_lastPage_tag= (!empty($last) && is_string($last)) ?
                '<img src="'.$last.'" width="15" height="11" />' : '尾页';
        return $this;
    }

    /**
    * 设置AJAX模式
    * @param string $action 默认ajax触发的动作。
    * @return $this 
    */
    public function setAjax($action){
        if(is_string($action) && !empty($action)){
            $this->_isAjax=true;
            $this->_ajaxActionName=$action;
        }  else {
            $this->_isAjax=FALSE;
            $this->_ajaxActionName='';
        }
        return $this;
    }
    /**
     * 获取分页列表的导航栏（你可以增加相应的风格）
     * @param int $total_numbers        信息总条数
     * @param int $page_list_numbers    每页显示信息条数
     * @param int $page_bar_item_limit  分页栏每页显示的页码个数
     * @param int $bar_style            导航条显示风格
     * @return string                   分页列表的导航栏
     */
    public function pageOfList( $total_numbers=0, $page_list_numbers=10, $page_bar_item_limit=10, $bar_style=5 ){
        $this->_doPage($total_numbers
                ,$page_list_numbers,$page_bar_item_limit);
        switch ($bar_style){
            case '1':
                return $this->_prePage().'&nbsp;'
                    .'[第'.$this->_current_page.'页]'.'&nbsp;'
                    .$this->_nextPage();
            case '2':
                return $this->_firstPage().'&nbsp;'
                    .$this->_prePage().'&nbsp;'
                    .'[第'.$this->_current_page.'页]'.'&nbsp;'
                    .$this->_nextPage().'&nbsp;'
                    .$this->_lastPage();
            case '3':
                return $this->_firstPage().'&nbsp;'
                    .$this->_prePage().'&nbsp;'
                    .$this->_nowBar().'&nbsp;'
                    .$this->_nextPage().'&nbsp;'
                    .$this->_lastPage();
            case '4':
                return $this->_firstPage().'&nbsp;'
                    .$this->_prePage().'&nbsp;'
                    .'[第'.$this->_current_page.'页]'.'&nbsp;'
                    .$this->_nextPage().'&nbsp;'
                    .$this->_lastPage().'&nbsp;'
                    .'共'.$total_numbers.'条&nbsp;'
                    .$this->_pageJump()
                    .'/'.$this->_totalPage.'页';
            case '5':
                return $this->_firstPage().'&nbsp;'
                    .$this->_prePage().'&nbsp;'
                    .$this->_nowBar().'&nbsp;'
                    .$this->_nextPage().'&nbsp;'
                    .$this->_lastPage().'&nbsp;'
                    .'共'.$total_numbers.'条&nbsp;'
                    .$this->_pageJump()
                    .'/'.$this->_totalPage.'页';
            default:break;
        }
        return '';
    }
    /**
     * 内容分页
     * @param array $content_array 待分页的内容列表
     * @param string $content 返回值：存放当前页内容的变量
     * @param string $page_navigation 返回值：存放当前导航栏的变量
     * @param int $bar_mode 导航栏的样式，详细见私有方法：_show_navigation
     * @param int $pageBarNum_limit 导航栏上的页码数目的最大值
     * @return boolean
     */
    public function pageOfContent( &$content_array, &$content, &$page_navigation, $bar_mode=1, $pageBarNum_limit=10 ){
        $page_navigation='';
        $total=count($content_array);//计算总行数
        $index=$this->_current_page-1;
        $content=isset($content_array[$index])?
                                $content_array[$index]:'';//获取当前内容
        if(empty($content)) return FALSE;
        if($total>1){
            $page_navigation=$this->pageOfList($total,1,$pageBarNum_limit,$bar_mode);//获取分页导航栏
        }
        return TRUE;
    }
    /**
    * 截取多语言列表字符串的前n的字符，支持gb2312,gbk,utf-8,big5
    * @param string $str 主体字符串
    * @param int $length 截取长度
    * @param string $charset utf-8|gb2312|gbk|big5 编码
    * @param bool $suffix 是否加尾缀
    * @param string $suffix_str 尾缀字符串
    * @return string
    */
    public function cutStringByLengthOfList( $str, $length, $charset='utf-8', $suffix=true, $suffix_str='…' ){
        $a=array('&', '"', '<', '>',' ');
        $b=array('&amp;', '&quot;', '&lt;', '&gt;','&nbsp;');
        $str = str_replace($b,$a, $str);
        return str_replace($a,$b,  Str::substr($str, 0, $length, $charset, $suffix, $suffix_str));
    }
    /**
    * 以指定页长方式分割多语言内容，支持gb2312,gbk,utf-8,big5
    * @param string $content 主体字符串
    * @param int $length 截取长度
    * @param string $charset utf-8|gb2312|gbk|big5 编码
    * @param bool $suffix 是否加尾缀
    * @param string $suffix_str 尾缀字符串
    * @return array
    */
    public function splitContentByLength( $content, $length, $charset='utf-8', $suffix=false, $suffix_str='……' ){
        $str=$content;
        $a=array('&', '"', '<', '>',' ');
        $b=array('&amp;', '&quot;', '&lt;', '&gt;','&nbsp;');
        $str = str_replace($b,$a, $str);
        $str_len= Str::length($str, $charset);
        $length-=($suffix?Str::length($suffix_str, $charset):0);
        $n= ceil($str_len/$length);
        if($n==1) return array($content);
        $str_array=array();
        for($i=0;$i<$n;$i++){
            $str_array[$i]=str_replace(
                        $a,$b,  Str::substr(
                                $str,
                                $i*$length,
                                $length,
                                $charset,
                                false)
                        ).(($i!=$n-1 && $suffix)?$suffix_str:'');
        }
        return $str_array;
    }
    /**
     * 以指定分页符分割内容，支持gb2312,gbk,utf-8,big5
     * @param string $content 待分割的文章
     * @param string $splitTag 自定义的分页符标记
     * @return array 分页数组
     */
    public function splitContentByTagOfCustom( $content, $splitTag='{page_spliter}' ){
        return explode($splitTag, $content);
    }
    /**
     * 尽最大努力自动分割多语言内容，支持gb2312,gbk,utf-8,big5
     * @param string $content 待分割的文章
     * @param int $page_max_length 每页的最大字符数
     * @param string $charset utf-8|gb2312|gbk|big5 编码
     * @param array $tag_array 自动分页的标记字符（注意字符的顺序），默认为array('</table>', '</div>', '</p>', '<br/>', '”。', '。', '.', '！', '……', '？',',')
     * @return array 分页数组
     */
    public function splitContentByTag( $content, $page_max_length=500, $tag_array=null, $charset='utf-8' ){
        $a=array('&', '"', '<', '>',' ');
        $b=array('&amp;', '&quot;', '&lt;', '&gt;','&nbsp;');
        $str = str_replace($b,$a, $content);
        $str_len= Str::length($str, $charset);
        if($str_len<$page_max_length)return array($content);
        $str_len= strlen($str);
        if(empty($tag_array) ){
            $tag_array=array('</table>', '</div>', '</p>', '<br/>', '”。', '。', '.', '！', '……', '？',',');
        }
        $content_array=array();
        $cut_start=$temp=0;
        do{
            $i=0;
            $cut_str =  substr($str, $cut_start, $page_max_length);
            if(strlen($cut_str)<$page_max_length){
                $content_array[]=str_replace($a,$b,$cut_str).'<p>——全文结束——</p>';
                break;
            }
            foreach ($tag_array as $tag){
                    $i = strrpos($cut_str, $tag);   //逆向查找第一个分页符的位置
                    if ($i!==FALSE){
                        $content_array[] = str_replace(
                                $a,$b,substr($cut_str, 0, $i).$tag).'<p>——未完待续——</p>';
                        $cut_start += $i + strlen($tag);
                        break;
                    }
            }
            if(!$i){
                if($cut_start+$page_max_length==$str_len-1){
                    $content_array[]=str_replace($a,$b,$cut_str).'<p>——全文结束——</p>';
                    break;
                }  else {
                    $temp=  Str::substr(
                                $cut_str,
                                0,
                                Str::length($cut_str,$charset)-1,
                                $charset,
                                false);
                    $cut_start+=strlen($temp);
                    $content_array[]=str_replace($a,$b,$temp).'<p>——未完待续——</p>';
                }
            }
        }while(1);
        return $content_array;
    }
    /**
     * 获取跳转的代码
     * @return string
     */
    private function _pageJump(){
        return '<input type="text" name="custompage" class="PageJump" size="2" title="输入页码，按回车快速跳转" value="1" onkeydown="if(event.keyCode==13) {window.location=\''.$this->_getUrl().'\'+this.value; doane(event);}" />';
    }
    /**
     * 处理分页参数
     * @param int $total_msg_numbers 信息总条数
     * @param int $page_msg_numbers 每页显示内容块数
     * @param int $pageBarNum_limit 分页栏每页显示的页数
     */
    private function _doPage($total_msg_numbers
            ,$page_msg_numbers,$pageBarNum_limit){
        $this->_totalPage=ceil($total_msg_numbers/$page_msg_numbers);	//计算总页数
        $this->_pageBarNum_limit=$pageBarNum_limit;
    }
    /**
    * 获取显示"下一页"的代码
    * @return string
    */
    private function _nextPage(){
        if($this->_current_page<$this->_totalPage) {
            return $this->_getLink($this->_getUrl($this->_current_page+1)
                    ,$this->_nextPage_tag,$this->_style['nextPage']);
        }
        return '';
    }
    /**
    * 获取显示“上一页”的代码
    * @return string
    */
    private function _prePage(){
        if($this->_current_page>1){
            return $this->_getLink($this->_getUrl($this->_current_page-1)
                    ,$this->_prePage_tag,$this->_style['prePage']);
        }
        return '';
    }
    /**
    * 获取显示“首页”的代码
    * @return string
    */
    private function _firstPage(){
        if($this->_current_page==1)return '';
        return $this->_getLink($this->_getUrl(1)
                ,$this->_firstPage_tag,$this->_style['firstPage']);
    }
    /**
    * 获取显示“尾页”的代码
    * @return string
    */
    private function _lastPage(){
        if($this->_totalPage==0 || $this->_current_page==$this->_totalPage)return '';
        return $this->_getLink($this->_getUrl($this->_totalPage)
                ,$this->_lastPage_tag,$this->_style['lastPage']);
    }
    /**
     * 获取当前分页导航条
     * @return string
     */
    private function _nowBar(){
        $plus=ceil($this->_pageBarNum_limit/2);
        if($this->_pageBarNum_limit-$plus+$this->_current_page>$this->_totalPage)
            $plus=($this->_pageBarNum_limit-$this->_totalPage+$this->_current_page);
        $begin=$this->_current_page-$plus+1;
        $begin=($begin>=1)?$begin:1;
        $return=' ';
        $pageItem_style=$this->_style['pageItem'];
        $currentPage_style=$this->_style['currentPage'];
        for($i=$begin;$i<$begin+$this->_pageBarNum_limit;$i++){
            if($i<=$this->_totalPage){
                if($i!=$this->_current_page)
                    $return.=$this->_getText($this->_getLink($this->_getUrl($i),$i,$pageItem_style));
                else
                    $return.=$this->_getText('<span class="'.$currentPage_style.'">'.$i.'</span>');
            }else{
                break;
            }
            $return.=' ';
        }
        return $return;
    }
    /**
    * 获取显示跳转按钮的代码//废弃，超大数据测试时内存溢出
    * @return string
    */
    private function _select(){
        if($this->_totalPage<=1) return '';
        $count= $this->_totalPage;
        $return='<select onChange="window.location=this.options[this.selectedIndex].value">';
        for($i=1;$i<=$count;$i++){
            if($i==$this->_current_page){
                $return.='<option value="'.$this->_getUrl($i).'" selected>'.$i.'</option>';
            }else{
                $return.='<option value="'.$this->_getUrl($i).'">'.$i.'</option>';
            }
        }
        $return.='</select>';
        return $return;
    }
    /**
    * 为指定的页面返回地址值
    * @param int $pageNum
    * @return string $url
    */
    private function _getUrl($pageNum=1){
        if($pageNum>1 || empty($this->_pageTag_fix)){
            return str_replace($this->_pageTag,$pageNum,$this->_url_label);
        }else{
            return str_replace($this->_pageTag_fix.$this->_pageTag,'',$this->_url_label);
        }
    }
    /**
    * 获取分页显示文字，比如说默认情况下_getText('<a href="">1</a>')将返回[<a href="">1</a>]
    * @param String $str
    * @return string $url
    */
    private function _getText($str){
        return $str;
    }
    /**
    * 获取链接地址
    */
    private function _getLink($url,$button,$style=''){
        $style=empty($style)?'':'class="'.$style.'"';
        if($this->_isAjax){
        //如果是使用AJAX模式
            return '<a '.$style.' href="javascript:'.$this->_ajaxActionName.'(\''.$url.'\')">'.$button.'</a>';
        }else{
            return '<a '.$style.' href="'.$url.'">'.$button.'</a>';
        }
    }
}