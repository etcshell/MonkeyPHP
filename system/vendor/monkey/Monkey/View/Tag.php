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

/**
 * Class Tag
 *
 * MonkeyPHP的标签方案
 *
 * @package Monkey\View
 */
class Tag {
    /**
     * 应用名
     *
     * @var string
     */
    public static $appName = '';

    /**
     * 直接替换型标签
     *
     * @return array
     */
    public static function getReplaceTag() {
        return array(//添加常量解析标签：
            array('/__[A-Z_]+__/', '<?php if(defined("$0")) echo $0; else echo "$0"; ?>'), /*添加变量解析标签：将变量{$name}替换成<?php  echo $name;?> */
            array('/\{(\$[a-z_]\w*(?:\[[\w\.\"\'\[\]\$]+\])*)\}/i', '<?php echo $1; ?>'),

            //函数调用，非php自带的函数，需要在模板执行前手动加载
            /*{f:code}替换成 <?php echo code; ?>*/
            array('/\{f:(?!:)(.*?)\s*\}/i', '<?php echo $1; ?>'),

            //语言翻译
            /*{t:string}替换成 <?php echo t("string"); ?>*/
            /*array('/\{t\/\s*(.*?)\}/i', '<?php echo t("$1"); ?>'),*/

            //添加判断解析标签：
            /*<!--if/$name==1-->替换成<?php if ($name==1){ ?>*/
            array('/<!--if\/\s*(.+?)-->/i', '<?php if($1) { ?>'), /*<!--elseif/$name==2-->替换成 <?php } elseif ($name==2){ ?>*/
            array('/<!--elseif\/\s*(.+?)-->/i', '<?php } elseif ($1) { ?>'), /*<!--else/-->替换成 <?php } else { ?>*/
            array('/<!--else\/\s*-->/i', '<?php } else { ?>'),

            //添加循环解析标签：
            /*<!--as/$arr $vo-->替换成 <?php if (is_array($arr)) foreach($arr as $vo){ ?>*/
            array('/<!--as\/\s*(\S+)\s+(\S+)\s*-->/i', '<?php if(is_array($1)) foreach($1 AS $2) { ?>'), /*<!--as/$arr $key $vo-->替换成 <?php if (is_array($array) foreach($arr as $key => $vo){ ?>*/
            array('/<!--as\/\s*(\S+)\s+(\S+)\s+(\S+)\s*-->/i', '<?php if(is_array($1)) foreach($1 AS $2 => $3) { ?>'), /*<!--foreach/$arr $vo-->替换成 <?php if (is_array($arr)) foreach($arr as $vo){ ?>*/
            array('/<!--foreach\/\s*(\S+)\s+(\S+)\s*-->/i', '<?php if(is_array($1)) foreach($1 AS $2) { ?>'), /*<!--foreach/$arr $key $vo-->替换成 <?php if (is_array($array) foreach($arr as $key => $vo){ ?>*/
            array('/<!--foreach\/\s*(\S+)\s+(\S+)\s+(\S+)\s*-->/i', '<?php if(is_array($1)) foreach($1 AS $2 => $3) { ?>'), /*<!--for/$i=0;$i<10;$i++ -->替换成 <?php for($i=0;$i<10;$i++) { ?>*/
            array('/<!--for\/\s*(.+?)-->/i', '<?php for($1) { ?> '), /*<!--while/condition-->替换成 <?php while(condition){ ?>*/
            array('/<!--while\/\s*(.+?)-->/i', '<?php while($1) { ?> '),

            //循环、判断、列表的结束标签
            /*<!--/-->替换成 <?php } ?>*/
            array('/<!--\/-->/', '<?php } ?>'),);
    }

    /**
     * 回调替换型标签
     *
     * @return array
     */
    public static function getCallbackTag() {
        return array(//直接输出型API调用标签，返回字符串或数字等标量 不需要结束标签
            /*{myapi::method pname1=pvalue1 pname2=pvalue2 ...}替换成 <?php echo call_user_func(array('appName\\LabelApi\\myapi', 'method'),array("pname1"=>pvalue1,"pname2"=>pvalue2, ... )); ?>*/ /*<?php echo Label\$1::$2( template::reg_param("$3") ); ?>*/
            array('/\{([a-z_][a-z0-9_]*)::([a-z_][a-z0-9_]*)((\s+[a-z_][a-z0-9_]*=\S+)*)\s*\}/i', array(__CLASS__, 'apiString')),

            //循环输出型API调用标签，返回数组，用于循环输出 需要结束标签
            /*<!--myapi::method pname1=pvalue1 pname2=pvalue2 ...-->替换成 <?php $myapi_method=call_user_func(array('appName\\LabelApi\\myapi', 'method'),array("pname1"=>pvalue1,"pname2"=>pvalue2, ... )); if(is_array($myapi_method)) foreach($myapi_method as $apikey => $apival){ ?>*/
            array('/\<!--([a-z_][a-z0-9_]*)::([a-z_][a-z0-9_]*)((\s+[a-z_][a-z0-9_]*=\S+)*)\s*-->/i', array(__CLASS__, 'apiArray')),

            //数组标签解析
            /*{$arr.key}替换成<?php echo $array["key"]...;?> */
            array('/\{(\$[a-z_][a-z0-9_]*)((\.[\$a-z_][a-z0-9_]*)+)*\}/i', array(__CLASS__, 'arrayKeyEcho')), array('/(<\?\s*php\s.*)(\$[a-z_][a-z0-9_]*)((\.[\$a-z_][a-z0-9_]*)+)(.*\?>)/i', array(__CLASS__, 'arrayKey')),);
    }

    /*************下面三个方法是实现上面接口的辅助方法*************/
    public static function apiString($matches) {
        return '<?php echo call_user_func(array(\'' . self::$appName . '\\\\LabelApi\\\\' . $matches[1] . '\', \'' . $matches[2] . '\'),' . self::regParam($matches[3]) . ' ); ?>';
    }

    public static function apiArray($matches) {
        $arr = '$' . $matches[1] . '_' . $matches[2];
        return '<?php ' . $arr . '=call_user_func(array(\'' . self::$appName . '\\\\LabelApi\\\\' . $matches[1] . '\', \'' . $matches[2] . '\'),' . self::regParam($matches[3]) . ' ); if(is_array(' . $arr . ')) foreach(' . $arr . ' as $apikey => $apival){?>';
    }

    public static function arrayKeyEcho($matches) {
        $var_name = $matches[1];
        $keys = $matches[2];

        while ($key = self::getKey($keys)) {
            $var_name .= $key;
        }

        return '<?php echo ' . $var_name . '; ?>';
    }

    public static function arrayKey($matches) {
        $search = '/(\$[a-z_][a-z0-9_]*)((\.[\$a-z_][a-z0-9_]*)+)/i';
        return preg_replace_callback($search, array(__CLASS__, 'arrayKey_'), $matches[0]);
    }

    public static function arrayKey_($matches) {
        $var_name = $matches[1];
        $keys = $matches[2];

        while ($key = self::getKey($keys)) {
            $var_name .= $key;
        }
        return $var_name;
    }

    ////////////////////////////////////
    private static function getKey(&$keys) {
        if (empty($keys)) {
            return null;
        }

        $keys = trim($keys, ' .');
        $offset = strpos($keys, '.');

        if ($offset) {
            $key = substr($keys, 0, $offset - 1);
            $keys = substr($keys, $offset + 1);
        }
        else {
            $key = $keys;
            $keys = null;
        }

        if ($key[0] == '"' || $key[0] == '\'' || $key[0] == '$') {
            return '[' . $key . ']';

        }
        elseif (is_numeric($key)) {
            return '[' . $key . ']';

        }
        else {
            return '["' . $key . '"]';
        }
    }

    private static function regParam($param_str = '') {
        $param_str = trim($param_str);

        if (empty($param_str)) {
            return 'null';
        }

        $params = '';

        while ($p = self::getOneParam($param_str)) {
            $params .= '\'' . $p['name'] . '\'=>' . $p['value'] . ', ';
        }

        if (empty($params)) {
            return 'null';
        }

        return ' array(' . $params . ')';
    }

    private static function getOneParam(&$str) {
        $offset = strpos($str, '=');
        if (!$offset) {
            return false;
        }

        $p['name'] = substr($str, 0, $offset);
        $str = trim(substr($str, $offset + 1));

        if ($str[0] == '"' or $str[0] == '\'') {
            $offset = strpos($str, $str[0], 1);
            if (!$offset) {
                return false;
            }

            if ($str[$offset - 1] == '\\') {
                $offset = strpos($str, $str[0], $offset + 1);
            }

            if (!$offset) {
                return false;
            }

            $v = substr($str, 0, $offset + 1);
            $str = trim(substr($str, $offset + 1));

        }
        elseif ($offset = strpos($str, ' ', 1)) {
            $v = substr($str, 0, $offset);
            $str = trim(substr($str, $offset + 1));

        }
        else {
            $v = $str;
            $str = '';
        }

        if ($v[0] != '$' && $v[0] != '"' && $v[0] != '\'' && !is_numeric($v)) {
            $p['value'] = '\'' . $v . '\'';
        }
        else {
            $p['value'] = $v;
        }
        //if($p['value'][0]=='\\')$p['value']='\''.substr($p['value'],2,-2).'\'';

        return $p;
    }
}
