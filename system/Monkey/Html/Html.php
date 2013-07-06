<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Administrator
 * Date: 13-5-26
 * Time: 上午10:28
 * To change this template use File | Settings | File Templates.
 */

namespace Monkey\Html;


use Monkey\_Interface\Component;

class Html implements Component
{

    /**
     * @var \Monkey\Monkey
     */
    private $oMonkey;

    /**
     * @var \Monkey\Html\Head
     */
    private $oHead=null;

    /**
     * @var \Monkey\Html\Form
     */
    private $oForm=null;

    private
        $config,
        $charset,//输出的编码
        $bodys=array()//内容
    ;

    private function __construct()
    {
        // TODO: Implement __construct() method.
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
        $this->config=$config;
    }

    /**
     * 获取组件实例
     * @return \Monkey\Html\Html
     */
    public static function _instance()
    {
        static $self;
        !$self and $self=new self();
        return $self;
    }

    /**
     * 获取Html文档头生成器对象
     * @return \Monkey\Response\HtmlHead
     */
    public function Head()
    {
        if($this->oHead===null)
        {
            $this->oHead= new Head($this->charset);
        }
        return $this->oHead;
    }

    /**
     * 是否设置了Html文档头
     * @return bool
     */
    public function hasHead()
    {
        return $this->oHead!==null;
    }

    /**
     * 获取表单生成器对象
     * @return Form|null
     */
    public function Form()
    {
        if($this->oForm===null)
        {
            $this->oForm= new Form();
        }
        return $this->oForm;
    }

    /**
     * 添加文档正文
     * @param string $content 内容
     * @param int $sendIndex 发送顺序索引
     */
    public function addBody($content, $sendIndex=null)
    {
        if($this->headerOnly) return;

        $sendIndex===null ?
            $this->bodys[] = $content
            :
            $this->bodys[$sendIndex] = $content;
    }

    /**
     * 清除文档正文
     */
    public function clearBody()
    {
        $this->bodys=null;
        $this->bodys=array();
    }


}