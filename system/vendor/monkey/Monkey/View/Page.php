<?php
/**
 * Project MonkeyPHP
 *
 * PHP Version 5.3.9
 *
 * @package   Monkey\View
 * @author    黄易 <582836313@qq.com>
 * @version   GIT:<git_id>
 */
namespace Monkey\View;

use Monkey;

/*
 * 分页栏配置在view.ini.php中：
    'page_style_name'       =>'def',
    'def_link'              =>'<a href="{urlPre}{number}">{text}</a>',
    'def_link_ajax'         =>'<a href="javascript:ajaxActionName(\'{urlPre}{number}\')">{text}</a>',
    'def_span_current'      =>'<span class="current_style">{number}</span>',
    'def_span_total'        =>'<span class="total_style">共{number}页</span>',
    'def_input_jump'        =>'转到<input type="text" class="jump_style" size="2" title="输入页码，按回车快速跳转" value="1" onkeydown="if(event.keyCode==13) {window.location=\'{urlPre}\'+this.value; doane(event);}" />',
    'def_text_first'        =>'首页',//另外，图片可以设置为：'<img src="..." width="16" height="11" />'，下同
    'def_text_pre'          =>'上一页',
    'def_text_next'         =>'下一页',
    'def_text_last'         =>'尾页',
    'def_layout'            =>'pre-current-next',//'first-pre-current-next-last'， 'first-pre-list-next-last'（list包含current）
                                                 //支持 first、pre、current、list（包含current）、next、last、total、jump
 * 分页栏使用方法：
    $page=$this->app->view()->page();
    $page->setStyle('def');//样式是配置中的默认样式，则这步可以省略
    $page->setCurrentLayout('first-pre-current-next-last');//使用上一步样式中的默认布局，则这步可以省略
    $currentPage=5;
    $totalPage=92;
    $barLimit=10;
    $pageHtml=$page->getByList($currentPage,$totalPage,$barLimit);
    简化下来就是：
    $pageHtml=$this->app->view()->page()->getByList(5,92,10);
 */

/**
 * Class Page
 *
 * 分页栏生成器
 * 支持ajax（可选）
 *
 * @package Monkey\View
 */
class Page {

    /**
     * 应用对象
     *
     * @var Monkey\App
     */
    public $app;

    /**
     * 配置
     *
     * @var array
     */
    private $config;

    /**
     * 链接前缀标签
     *
     * @var string
     */
    private $tagUrlPre = '{urlPre}';

    /**
     * 数字标签
     *
     * @var string
     */
    private $tagNumber = '{number}';

    /**
     * 文本标签
     *
     * @var string
     */
    private $tagText = '{text}';

    /**
     * 样式名
     *
     * @var string
     */
    private $style;

    /**
     * 连接模板
     *
     * @var string
     */
    private $linkTpl;

    /**
     * 连接字段名后缀
     *
     * @var string
     */
    private $link = '_link';

    /**
     * ajax连接字段名后缀
     *
     * @var string
     */
    private $linkAjax = '_link_ajax';

    /**
     * 当前页标签字段名后缀
     *
     * @var string
     */
    private $spanCurrent = '_span_current';

    /**
     * 总页码标签字段名后缀
     *
     * @var string
     */
    private $spanTotal = '_span_total';

    /**
     * 跳转标签字段名后缀
     *
     * @var string
     */
    private $inputJump = '_input_jump';

    /**
     * 首页标签字段名后缀
     *
     * @var string
     */
    private $first = '_text_first';

    /**
     * 前一页标签字段名后缀
     *
     * @var string
     */
    private $pre = '_text_pre';

    /**
     * 后一页标签字段名后缀
     *
     * @var string
     */
    private $next = '_text_next';

    /**
     * 末页标签字段名后缀
     *
     * @var string
     */
    private $last = '_text_last';

    /**
     * 分页栏布局标签字段名后缀
     *
     * @var string
     */
    private $layout = '_layout';

    /**
     * 链接前缀
     *
     * @var string
     */
    private $urlPre;

    /**
     * 当前页码
     *
     * @var int
     */
    private $currentPage;

    /**
     * 分页栏页码数量容量
     *
     * @var string
     */
    private $barLimit;

    /**
     * 总页数
     *
     * @var string
     */
    private $totalPage;

    /**
     * 是否输出ajax样式连接
     *
     * @var bool
     */
    private $isAjax = false;

    /**
     * 当前分页栏布局
     *
     * @var string
     */
    private $currentLayout;

    /**
     * 构造方法
     *
     * @param Monkey\App $app
     * @param array $config
     */
    public function __construct($app, $config) {
        $this->app = $app;
        $this->config = $config;
        $this->style = $config['page_style_name'];
    }

    /**
     * 设置链接前缀
     *
     * @param $urlPre
     *
     * @return $this
     */
    public function setUrlPre($urlPre) {
        $this->urlPre = $urlPre;
        return $this;
    }

    /**
     * 设置AJAX模式’
     * @param bool $isAjax 默认ajax触发的动作。
     *
     * @return $this
     */
    public function setAjax($isAjax = false) {
        $this->isAjax = (bool)$isAjax;
        return $this;
    }

    /**
     * 设置分页栏样式
     * @param string | null $style
     * @return $this
     */
    public function setStyle($style = null) {
        $this->style = $style ? $style : $this->config['page_style_name'];
        $this->currentLayout = '';
        return $this;
    }

    /**
     * 设置分页栏布局
     * @param string | null $currentLayout 当前布局序列，为空相当于使用配置中对应样式的分页布局
     * 支持 first、pre、current、list（包含current）、next、last、total、jump 及其任意组合，
     * 如'pre-current-next'
     * @return $this
     */
    public function setCurrentLayout($currentLayout = null) {
        $this->currentLayout = $currentLayout ? $currentLayout : $this->config[$this->style . $this->layout];
        return $this;
    }

    /**
     * 通过列表条目来获取分页栏
     * @param int $currentPage 当前页码
     * @param int $totalItem 条目总数
     * @param int $listLimit 每页列表中的条目容量
     * @param int $barLimit 分页栏上显示的页码数量
     * @return string
     */
    public function getByList($currentPage, $totalItem, $listLimit, $barLimit = 10) {
        $totalPage = ceil($totalItem / $listLimit);
        return $this->getByPage($currentPage, $totalPage, $barLimit);
    }

    /**
     * 通过总页数来获取分页栏
     * @param int $currentPage 当前页码
     * @param int $totalPage 总页数
     * @param int $barLimit 分页栏上显示的页码数量
     * @return string
     */
    public function getByPage($currentPage, $totalPage, $barLimit = 10) {
        $currentPage == 0 and $currentPage = 1;
        $totalPage == 0 and $totalPage = 1;
        if ($currentPage > $totalPage or $currentPage < 1 or $barLimit < 1) {
            return '';
        }
        $this->currentPage = $currentPage;
        $this->barLimit = $barLimit;
        $this->totalPage = $totalPage;
        $this->linkTpl = $this->config[$this->style . ($this->isAjax ? $this->linkAjax : $this->link)];
        $this->linkTpl = str_replace($this->tagUrlPre, $this->urlPre, $this->linkTpl);
        $result = '';
        $layout = $this->currentLayout ? $this->currentLayout : $this->config[$this->style . $this->layout];
        $layout = explode('-', $layout);
        foreach ($layout as $method) {
            $method = 'get_' . $method;
            if (method_exists($this, $method)) {
                $result .= $this->$method();
            }
        }
        return $result;
    }

    private function get_first() {
        if ($this->currentPage == 1) {
            return '';
        }
        $s = str_replace($this->tagNumber, '1', $this->linkTpl);
        return str_replace($this->tagText, $this->config[$this->style . $this->first], $s);
    }

    private function get_pre() {
        if ($this->currentPage < 2) {
            return '';
        }
        $s = str_replace($this->tagNumber, $this->currentPage - 1, $this->linkTpl);
        return str_replace($this->tagText, $this->config[$this->style . $this->pre], $s);
    }

    private function get_current() {
        return str_replace($this->tagNumber, $this->currentPage, $this->config[$this->style . $this->spanCurrent]);
    }

    private function get_next() {
        if ($this->currentPage >= $this->totalPage) {
            return '';
        }
        $s = str_replace($this->tagNumber, $this->currentPage + 1, $this->linkTpl);
        return str_replace($this->tagText, $this->config[$this->style . $this->next], $s);
    }

    private function get_last() {
        if ($this->currentPage == $this->totalPage) {
            return '';
        }
        $s = str_replace($this->tagNumber, $this->totalPage, $this->linkTpl);
        return str_replace($this->tagText, $this->config[$this->style . $this->last], $s);
    }

    private function get_total() {
        return str_replace($this->tagNumber, $this->totalPage, $this->config[$this->style . $this->spanTotal]);
    }

    private function get_jump() {
        return str_replace($this->tagUrlPre, $this->urlPre, $this->config[$this->style . $this->inputJump]);
    }

    private function get_list() {
        $half = ceil($this->barLimit / 2);
        $end = ($this->currentPage + $half > $this->totalPage ? $this->totalPage : $this->currentPage + $half);
        $begin = $end - $this->barLimit;
        if ($begin < 1) {
            $end = $this->barLimit > $this->totalPage ? $this->totalPage : $this->barLimit;
            $begin = 1;
        }
        $end++;
        $result = '';
        for ($i = $begin; $i < $end; $i++) {
            if ($i == $this->currentPage) {
                $result .= str_replace(
                    $this->tagNumber,
                    $this->currentPage,
                    $this->config[$this->style . $this->spanCurrent]
                );
            }
            else {
                $result .= str_replace(array($this->tagNumber, $this->tagText), $i, $this->linkTpl);
            }
        }

        return $result;
    }
}