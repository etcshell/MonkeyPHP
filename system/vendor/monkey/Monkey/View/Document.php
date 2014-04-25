<?php
namespace Monkey\View;

/**
 * html文档素生成工具
 */
class Document {
    /**
     * 处理超级连接代码
     * @param string $text           显示文本
     * @param string $href           连接URL
     * @param array  $options        其它内容
     * @return string
     */
    public function a($text, $href='#', $options = array()) {
        if(!empty($href) ) $options['href'] = $href;
        if(empty($options['title']) && empty($options['TITLE'])){
            $options['title'] = $text;//为了SEO效果,link的title处理.
        }
        return $this->tag('a', $options, $text);
    }
    /**
     * 用于完成email的html代码的处理
     * @param string $text 显示文本
     * @param string $email 邮件地址
     * @param array  $options
     * @return string
     */
    public function email($text, $email = null, $options = array()) {
        $options['href'] =  'mailto:' . (is_null($email) ? $text : $email);
        return $this->tag('a', $options, $text);
    }
    /**
     * 处理图片代码
     * @param string $src        图片网址
     * @param string $alt        提示内容
     * @param array   $options   项目内容
     * @return string
     */
    public function image($src, $alt = null, $options = array()) {
        if (!$src)  return false;
        $options['src'] = $src;
        if ($alt) {
            $options['alt'] = $alt;
            if(empty($options['title'])) $options['title'] = $alt;//为了SEO效果,加入title.
        }
        return $this->tag('img', $options);
    }
    /**
     * 加载css文件
     * @param string $url        CSS网址
     * @param string $media      media属性
     * @return string
     */
    public function cssFile($url, $media = null) {
        if(!empty($media)) $media = ' media="' . $media . '"';
        return '<link rel="stylesheet" type="text/css" href="'
                . $this->toText($url)
                . '"' . $media . ' />\r';
    }
    /**
     * 加载JavaScript文件
     * @param string $url    js网址
     * @return string
     */
    public function jsFile($url) {
        return '<script type="text/javascript" src="'
                . $this->toText($url)
                . '"></script>\r';
    }
    /**
     * 生成表格开始的HTML代码
     * @param array  $options 设置整个表格的属性
     * @return string
     */
    public function tableBegin($options = array()) {
        return $this->tag('table', $options, false, false);
    }
    /**
     * 生成表格一行的HTML代码
     * @param array $row 表格一行的内容
     * @return string
     */
    public function tableTR($row=array()) {
        $html = '';
        foreach ($row as $col) $html .= '<td>'.$col.'</td>';
        return '<tr>'.$html.'</tr>';
    }
    /**
     * 生成表格结束的HTML代码
     * @return string
     */
    public function tableEnd() {
        return '</table>';
    }
    /**
     * 生成表格的HTML代码
     * @param array $content 表格内容的二维表
     * @param array  $options 设置整个表格的属性
     * @return string
     */
    public function table($content=array(), $options = array()) {
        if (!$content)  return false;
        $html = $this->tag('table', $options, false, false);
        foreach ($content as $lines) {
            if (is_array($lines)) {
                $html .= '<tr>';
                foreach ($lines as $col) {
                    $html .= '<td>'.$col.'</td>';
                }
                $html .= '</tr>';
            }
        }
        return $html . '</table>';
    }
    /**
     * 表单开始代码
     * form开始HTML代码,即:将<form>代码内容补充完整.
     * @param string $action 提交的目的网址
     * @param array  $options 属性设置
     * @param string $method 提交的方式，默认为post
     * @param boolean $enctype_item 是否直接提交数据，如文件上传（TRUE），其它情况为FALSE
     * @return string
     */
    public function begin($action, $options = array(), $method = null, $enctype_item = false) {
        if (!$action) return false;
        $options['action'] = $action;
        $options['method'] = empty($method) ? 'post' : $method;
        if($enctype_item === true) $options['enctype'] = 'multipart/form-data';
        return $this->tag('form', $options, false, false);
    }
    /**
     * 表单结束代码
     * form的HTML的结束代码
     * @return string
     */
    public function end() {
        return '</form>';
    }
    /**
    * 处理input代码
    * @param string $type
    * @param array $options
    * @return string
    */
    public function input($type, $options = array()) {
        if (!$type) return false;
        $options['type'] = $type;
        return $this->tag('input', $options);
    }
    /**
     * 文本输入框text表单代码
     * @param string $default_value 默认值，这个属性也可以直接在$options设置
     * @param array $options
     * @return string
     */
    public function inputText($default_value=null, $options = array()) {
        !$default_value and $options['value']=$default_value;
        return $this->input('text', $options);
    }
    /**
     * 密码输入框password代码
     * @param string $name
     * @param string $value
     * @param array $options
     * @return string
     */
    public function inputPassword($name, $value='', $options = array()) {
        $options['name']=$name;
        $options['value']=$value;
        return $this->input('password', $options);
    }
    /**
     * 提交按钮submit代码
     * @param string $value 按钮名称
     * @param array $options
     * @return string
     */
    public function inputSubmit($value='重置', $options = array()) {
        $options['value']=$value;
        return $this->input('submit', $options);
    }
    /**
     * 重置按钮reset代码
     * @param string $value 按钮名称
     * @param array $options
     * @return string
     */
    public function inputReset($value='重置', $options = array()) {
        $options['value']=$value;
        return $this->input('reset', $options);
    }
    /**
     * 按钮button代码
     * @param string $value 按钮名称
     * @param array $options
     * @return string
     */
    public function inputButton($value, $options = array()) {
        $options['value']=$value;
        return $this->input('button', $options);
    }
    /**
     * 复选框HTML代码（单个）
     * @param string $label 标题
     * @param string $value 值
     * @param boolean $selected 是否选中
     * @param array $options 其它属性
     * @return string
     */
    public function inputCheckbox($label, $value, $selected = false, $options=array()) {
        $options['value']=$value;
        $selected and $options['checked'] = 'checked';
        return '<label>'.$this->input('checkbox', $options).$label.'</label>';
    }
    /**
     * 单选框HTML代码（单个）
     * @param string $label 标题
     * @param string $value 值
     * @param boolean $selected 是否选中
     * @param array $options 其它属性
     * @return string
     */
    public function inputRadio($label,$value,$selected=false, $options = array() ) {
        $options['value']=$value;
        $selected and $options['checked'] = 'checked';
        return '<label>'.$this->input('radio', $options).$label.'</label>';
    }
    /**
     * 复选框HTML代码
     * @param array $content_array 二维数组，array( array(item,value,is_checked),array(item,value),...),有is_checked则该项选中
     * @param array $options 设置统一的属性，如一组同名的复选框
     * @param boolean $selected
     * @return string
     */
    public function inputCheckboxArray($content_array, $options = array(), $selected = false) {
        if (!$content_array || !is_array($content_array))  return false;
        $html = '';
        foreach ($content_array as $item) {
            $options['value'] = $item[1];
            if(isset($item[2])){
                $options['checked'] = 'checked';
            } else{
                if(isset($options['checked']))unset($options['checked']);
            }
            $html .= '<label>'
                .$this->input('checkbox', $options).$item[0]
                .'</label>';
        }
        return $html;
    }
    /**
     * 单选框HTML代码
     * @param array $content_array 二维数组，array( array(item,value,is_checked),array(item,value),...),有is_checked则该项选中
     * @param array $options 设置统一的属性，如一组同名的单选框
     * @return string
     */
    public function inputRadioArray($content_array, $options = array()) {
        if (!$content_array || !is_array($content_array))  return false;
        $html = '';
        foreach ($content_array as $item) {
            $options['value'] = $item[1];
            if(isset($item[2])){
                $options['checked'] = 'checked';
            } else{
                if(isset($options['checked']))unset($options['checked']);
            }
            $html .= '<label>'
                .$this->input('radio', $options).$item[0]
                .'</label>';
        }
        return $html;
    }
    /**
     * 多行文字输入区域框TextArea的HTML代码处理
     * @param string $content    默认的文字内容
     * @param array  $options    属性
     * @return string
     */
    public function textArea($content = null, $options = array()) {
        $option_str = '';
        //当$options不为空或类型不为数组时
        if (!empty($options) && is_array($options)) {
            foreach ($options as $name=>$value) {
                $option_str .= ' ' . $name . '="' . $value . '"';
            }
        }
        $html = '<textarea' . $option_str . '>';
        return ($content==true)? $html . $content . '</textarea>' :  $html . '</textarea>';
    }
    /**
     * 下拉框SELECT开始的HTML代码
     * @param array $options 整个菜单的属性
     * @return string
     */
    public function selectBegin($options = array()) {
        return $this->tag('select', $options, false, false);
    }
    /**
     * 下拉框SELECT一项的HTML代码
     * @param $name 菜单名
     * @param $value 菜单值
     * @param bool $selected 是否选中
     * @return string
     */
    public function selectOption($name,$value,$selected=false) {
        return '<option value="'. $value. ($selected?'" selected="selected">':'">')
            . $name
            . '</option>';
    }
    /**
     * 下拉框SELECT结束的HTML代码
     * @return string
     */
    public function selectEnd() {
        return '</select>';
    }
    /**
    * 下拉框SELECT的HTML代码
    * @param array $content_array 菜单二维数组，array( array(item,value,is_selected),array(item,value),...),有is_selected则该项选中
    * @param array $options 整个菜单的属性
    * @return string
    */
    public function select($content_array, $options = array()) {
        if (!$content_array || !is_array($content_array))  return false;
        $option_str = '';
        foreach ($content_array as $item) {
            $option_str .= '<option value="'. $item[1]
                . (isset($item[2])?'" selected="selected">':'">')
                . $item[0]
                . '</option>';
        }
        return $this->tag('select', $options, $option_str);
    }
    /**
     * 处理标签代码
     * @param string    $tag
     * @param array     $options
     * @param  string   $content
     * @param boolean   $close_tag
     * @return string
     */
    public function tag($tag, $options = array(), $content = null, $close_tag = true) {
        $option_str = '';
        //当$options不为空或类型不为数组时
        if (!empty($options) && is_array($options)) {
            foreach ($options as $name=>$value) {
                $option_str .= ' ' . $name . '="' . $value . '"';
            }
        }
        $html = '<' . $tag . $option_str;
        if (!is_null($content)) {
            return $close_tag
                ? $html .'>' . $content . '</' . $tag . '>'
                : $html . '>' . $content;
        } else {
            return $close_tag ? $html . '/>' : $html . '>';
        }
    }
    /**
     * 过滤标签
     * @param string|array $str 内容字符串或数组
     * @param string $tags 标签名，为空时过滤所有标签
     * @return string
     */
    public function filterTag($str, $tags=NULL){
        if(is_null($tags)) return strip_tags($str);
        if(is_string($tags))
            return preg_replace("/(<(?:\/".$tags."|".$tags.")[^>]*>)/i",'',$str);
        foreach ($tags as $tag) {
            $p[] = "/(<(?:\/".$tag."|".$tag.")[^>]*>)/i";
        }
        return $return = preg_replace($p,'',$str);
    }
    /**
     * 防止XSS攻击代码
     * 删除html和php标签，使得结果只剩下文本
     * 注意<br>不会被删除，同时\n等换行符号也会转换为<br>
     * @param array|string $data
     * @return string
     */
    public function toText($data){
        if(is_array($data)) return array_map(__METHOD__ , $data);
        $data=trim(strip_tags($data,'<br>'));
        $data=$this->delRedundancy($data);
        $data = str_replace("\n",'<br>',   $data);
        $data = addslashes($data);
        return $data;
    }
    //过滤恶意重复空白
    private function delRedundancy($data){
        $data = preg_replace ( '/\s+(\r?\n)/','$1', $data );//去除恶意空格
        $data = preg_replace ( '/\s{8,}/',' ', $data );//去除恶意空格
        $data = preg_replace ( '/(\r?\n){3,}/','$1', $data );//去除恶意换行
        return $data;
    }

}