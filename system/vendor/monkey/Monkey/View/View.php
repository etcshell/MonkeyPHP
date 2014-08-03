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

/**
 * Class View
 *
 * 视图组件
 *
 * @package Monkey\View
 */
class View {

    /**
     * 应用对象
     *
     * @var Monkey\App
     */
    public $app;

    /**
     * html头部帮助对象
     *
     * @var Header
     */
    protected $header;

    /**
     * 缓存对象
     *
     * @var Cache
     */
    protected $cache;

    /**
     * htmlBody帮助对象
     *
     * @var Document
     */
    protected $document;

    /**
     * 模板对象
     *
     * @var Template
     */
    protected $template;

    /**
     * 分页帮助对象
     *
     * @var Page
     */
    protected $page;

    /**
     * 配置
     *
     * @var array
     */
    protected $config;

    /**
     * 挂件列表
     *
     * @var array
     */
    protected $widgets = array();

    /**
     * 主题的基础url,空表示index.php所在目录
     * @var string
     */
    public $themeUrlBase;

    /**
     * 主题目录名
     *
     * @var string
     */
    public $themeDirName;

    /**
     * 构造方法
     *
     * @param Monkey\App $app
     */
    public function __construct($app) {
        $this->app = $app;
        $this->config = $app->config()->getComponentConfig('view', 'default');
        $this->themeUrlBase = $app->FRONT_ROOT_URL . $this->config['theme_url_base'];
        $this->themeDirName = $this->config['theme_dir'];
        $this->template = new Template($app, $this->config);
    }

    /**
     * 模板赋值变量整体设置
     *
     * @param array $data
     *
     * @return $this
     */
    public function setVariable(array $data = array()) {
        $this->template->variable = $data;
        return $this;
    }

    /**
     * 模板赋值
     *
     * @param string $name 变量名
     * @param mixed $value 变量值
     *
     * @return $this
     */
    public function assign($name, $value) {
        $this->template->assign($name, $value);
        return $this;
    }

    /**
     * 清空已赋值变量
     */
    public function clearAssigned() {
        $this->template->clearAssigned();
    }

    /**
     * 添加挂件
     *
     * @param $name
     * @param Widget $widget
     */
    public function addWidget($name, Widget $widget) {
        $this->widgets['widget_' . $name] = $widget;
    }

    /**
     * 渲染
     *
     * @param string $tplFilename 模板文件名，相对于模板根目录。
     * @param bool $outputNow 是否直接输出显示，默认为否
     *
     * @return string 直接输出后返回空字符串
     */
    public function render($tplFilename, $outputNow = true) {
        if (isset($this->widgets)) {
            foreach ($this->widgets as $name => $widget) {
                $this->template->assign($name, $widget->getHtml());
            }
        }

        return $this->template->loading($tplFilename, $outputNow);
    }

    /**
     * 渲染
     *
     * @param string $tplFilename 模板文件名，相对于模板根目录。
     * @param int $expire 缓存时间，默认保存时间为0（永久保存），24小时为86400*1
     *
     * @return string 始终返回渲染结果
     */
    public function renderWithCache($tplFilename, $expire = 0) {
        $cache = $this->cache()->setFile('/template' . $tplFilename);

        if ($html = $cache->fetch()) {
            return $html;
        }

        $html = $this->render($tplFilename, false);
        $cache->store($html, $expire);

        return $html;
    }

    /**
     * 获取主题目录的路径
     *
     * @return string
     */
    public function getThemePath() {
        return $this->themeUrlBase . $this->themeDirName;
    }

    /**
     * 获取模板助手
     *
     * @return Template
     */
    public function template() {
        return $this->template;
    }

    /**
     * 清空前模板的编译缓存
     */
    public function clearTemplateCompiled() {
        $this->template->clearCompiled();
    }

    /**
     * 获取Html文档头助手
     *
     * @return Header
     */
    public function header() {
        if ($this->header === null) {
            $this->header = Header::getInstance()->setCharset($this->config['charset']);
        }

        return $this->header;
    }

    /**
     * 获取静态缓存助手
     *
     * @return Cache
     */
    public function cache() {
        if (!$this->cache) {
            $this->cache = new Cache($this->app);
        }
        return $this->cache;
    }

    /**
     * 获取Html文档元素助手
     *
     * @return Document
     */
    public function document() {
        if (!$this->document) {
            $this->document = new Document();
        }
        return $this->document;
    }

    /**
     * 获取分页助手
     *
     * @return Page
     */
    public function page() {
        if (!$this->page) {
            $this->page = new Page($this->app, $this->config);
        }
        return $this->page;
    }

    /**
     * 创建Html文档
     *
     * @param null $body 正文内容
     * @param null $head Html文件头，默认使用HtmlHeader类创建的文件头
     *
     * @return string
     */
    public function buildHtml($body = null, $head = null) {
        empty($head) and $head = $this->header()->get();
        //empty($body) and $body= $this->get();
        return
            "<!DOCTYPE html>\n<html>" .
            "\n<head>\n" .
            $head .
            "\n</head>" .
            "\n<body>\n" .
            $body .
            "\n</body>" .
            "\n</html>";
    }
} 