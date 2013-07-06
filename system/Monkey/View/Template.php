<?php
namespace Monkey;

/**
 * 模板引擎\Framework\Template
 * @package    Framework
 * @author     HuangYi
 * @copyright  Copyright (c) 2011-07-01——2013-03-30
 * @license    New BSD License
 * @version    $Id: \Framework\Template.php 版本号 2013-03-30 $
 */
class Template
{
    private $_tagCallback=array();
    private $_tag=array();
    private $_variable = array(); //存放变量信息
    private $_label;

    private $_tplRoot; //模板根目录
    private $_moduleName; //模块名

    /**
     * 模板解析
     * @param string $moduleName 模块名
     * @param string $type 模板方案，目前支持FrameworkLabel方案，你可以方便的添加
     */
    public function __construct($moduleName, $type=null) {
        $this->_moduleName=$moduleName;
        $this->_tplRoot=SYSTEM.'/Style';
        $this->_label = $type?$type: config()->view_label;
    }
    /**
     * 模板赋值
     * @param string $name 变量名
     * @param mixed $value 变量值
     * @return $this
     */
    public function assign($name, $value) {
        $this->_variable[$name] = $value;
        return $this;
    }
    /**
     * 载入并显示模板
     * @param string $tplFileName 样式模板文件。
     * @param bool $isCommon 是否为公共样式模板。
     */
    public function display($tplFileName, $isCommon=false){
        $this->loading($tplFileName,$isCommon,true);
    }
    /**
     * 载入模板
     * @param string $tplFileName  样式模板文件
     * @param bool $isCommon 是否为公共样式模板。
     * @param bool $display  是否直接显示，默认为否
     * @return void|string 当直接显示时无返回，关闭显示后返回字符串
     */
    public function loading($tplFileName,$isCommon=false,$display=FALSE) {
        if($isCommon)
            $tplFile = o_view_manager()->getTemplateOfCommon($tplFileName);
        else
            $tplFile = o_view_manager()->getTemplateOfStyle($this->_moduleName,$tplFileName);

        $this->_checkTplFile($tplFile);
        $compiledFile=str_replace(SYSTEM.'/View/', config()->dir_temp.'/ViewCompiled/',$tplFile);
        $compiledFile .= '.php';
        if ( DEBUG
                || !file_exists($compiledFile)
                || (filemtime($compiledFile) < filemtime($tplFile))
        ){
            $label='Framework\\Label\\'.$this->_label;
            $this->_tag=call_user_func(array($label,'replaceTag'));
            $this->_tagCallback=call_user_func(array($label,'callbackTag'));
            $compiled = $this->_compile($tplFile); //获取经编译后的内容
            $dir = dirname($compiledFile);
            dir_check($dir);//保证编译后目录存在
            file_put_contents($compiledFile, $compiled, LOCK_EX);
        }
        //解析变量
        extract($this->_variable, EXTR_OVERWRITE);
        //执行模板
        if($display){
            include $compiledFile;
        }  else {
            ob_start();
            include $compiledFile;
            return ob_get_clean();
        }
    }
    /**
     * 清空当前模板根目录下的编译缓存
     * @return $this
     */
    public function clearCompiled() {
        dir_delete(config()->dir_temp.'/StyleCompiled');
        return $this;
    }
    //用来检测模板文件是否存在
    protected function _checkTplFile($tplFile) {
        if (!file_exists($tplFile)){
            error_exit( ':模板文件【' . $tplFile . '】不存在');
        }
    }
    //编译模板
    private function _compile($tplFile) {
        $this->_checkTplFile($tplFile);
        $template = file_get_contents($tplFile); //读取模板内容
        //解析直接替换标签
        foreach ($this->_tag as $tag) {
            $template = preg_replace($tag[0],$tag[1],$template);
        }
        //解析回调替换标签
        foreach ($this->_tagCallback as $tag) {
            $template = preg_replace_callback($tag[0],$tag[1],$template);
        }
        //解析模板包含（两种方式）
        //{include="login.html" type=style}
        $template = preg_replace_callback(
            '/{include=(\'|")(\S+)\1\s+type=(style|common)}/i',
            array($this,'_inc_tpl'),
            $template
        );
        //去掉剩余的注释，提高加载速度
        if(!DEBUG)
            $template = preg_replace("/<!--(.+?)-->/s", '', $template);
        return $template;
    }
    private function _inc_tpl($matches){
        if($matches[3]=='style')
            return $this->loading($matches[2],false,false);
        elseif($matches[3]=='common')
            return $this->loading($matches[2],true,false);
        else
            return '<!-- 此处的模板包含标签：{include='.
                $matches[1].$matches[2].$matches[1].
                ' type='.$matches[3].'} 没有被正确解析 -->';
    }
}