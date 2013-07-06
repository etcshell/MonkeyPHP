<?php
namespace Library;

/**
 * php_code_compress
 * @category   php代码压缩类
 * @package    初始化包
 * @author     HuangYi
 * @copyright  Copyright (c) 2012-4-1——至今
 * @license    New BSD License
 * @version    $Id: php_code_compress.class.php 版本号 2013-1-1 $ *
 */
class CodeCompress{
    /**
     * 请覆盖此函数以提供源文件列表
     * @return array
     */
    protected static function sourceList(){
        return array(
            //'源文件列表',
        );
    }
    /**
     * 压缩文件列表中的文件，合并到一个文件中
     * 需要继承并覆盖列表获取函数以提供源文件列表
     * @param $mergefile
     * @return bool
     */
    public static function compressListAndMerge($mergefile){
        $source_list=self::sourceList();
        $compress="";
        foreach($source_list as $source_file){
            $compress.=rtrim(self::phpStripWhitespace($source_file));
        }
        if($compress){
            file_put_contents($mergefile,$compress);
            return true;
        }else{
            return false;
        }
    }
    /**
     * 压缩文件列表中的文件，源文件将备份为'.bak'文件
     * 需要继承并覆盖列表获取函数以提供源文件列表
     */
    public static function compressList(){
        $source_list=self::sourceList();
        foreach($source_list as $source_file){
            copy($source_file, $source_file.'.bak');
            file_put_contents($source_file, rtrim(self::phpStripWhitespace($source_file)));
        }
    }
    /**
     * 压缩目标目录中的文件，合并到一个文件中
     * @param string $dir 目标目录
     * @param $mergefile
     * @return bool
     */
    public static function compressDirAndMerge($dir,$mergefile){
        $compress='';
        self::_compressDirAndMerge($dir, $compress);
        if($compress){
            file_put_contents($mergefile,$compress);
            return true;
        }else{
            return false;
        }
    }
    /**
     * 压缩目标目录中的文件，源文件将备份为'.bak'文件
     * @param string $dir 目标目录
     * @return bool
     */
    public static function compressDir($dir){
        $handler=opendir($dir);
        if(!$handler) return FALSE;
        while (($item = readdir($handler)) !== false) {
            if($item=='.' || $item=='..')  continue;
            $new=$dir."/".$item;
            is_dir($new) && self::compressDir($new);
            if(substr($item,-4)!=='.php')  continue;
            copy($new, $new.'.bak');
            file_put_contents($new, rtrim(self::phpStripWhitespace($new)));
        }
        closedir($handler);
    }
    private static function _compressDirAndMerge($dir,&$compress){
        $handler=opendir($dir);
        if(!$handler) return FALSE;
        while (($item = readdir($handler)) !== false) {
            if($item=='.' || $item=='..')  continue;
            $new=$dir."/".$item;
            is_dir($new) && self::_compressDirAndMerge($new, $compress);
            if(substr($item,-4)!=='.php')  continue;
            $compress.=rtrim(self::phpStripWhitespace($new));
        }
        closedir($handler);
    }
    /**
     * 去除php脚本的空白字符和注释，但保留了换行符号
     * 方便统计代码行数
     * @param string $src 代码字符串 或 代码文件的绝对路径
     * @return string|bool
     */
    public static function phpStripWhitespace($src){
        // Whitespaces left and right from this signs can be ignored
        static $IW = array(
            T_CONCAT_EQUAL,             // .=
            T_DOUBLE_ARROW,             // =>
            T_BOOLEAN_AND,              // &&
            T_BOOLEAN_OR,               // ||
            T_IS_EQUAL,                 // ==
            T_IS_NOT_EQUAL,             // != or <>
            T_IS_SMALLER_OR_EQUAL,      // <=
            T_IS_GREATER_OR_EQUAL,      // >=
            T_INC,                      // ++
            T_DEC,                      // --
            T_PLUS_EQUAL,               // +=
            T_MINUS_EQUAL,              // -=
            T_MUL_EQUAL,                // *=
            T_DIV_EQUAL,                // /=
            T_IS_IDENTICAL,             // ===
            T_IS_NOT_IDENTICAL,         // !==
            T_DOUBLE_COLON,             // ::
            T_PAAMAYIM_NEKUDOTAYIM,     // ::
            T_OBJECT_OPERATOR,          // ->
            T_DOLLAR_OPEN_CURLY_BRACES, // ${
            T_AND_EQUAL,                // &=
            T_MOD_EQUAL,                // %=
            T_XOR_EQUAL,                // ^=
            T_OR_EQUAL,                 // |=
            T_SL,                       // <<
            T_SR,                       // >>
            T_SL_EQUAL,                 // <<=
            T_SR_EQUAL,                 // >>=
        );
        if(is_file($src)) {
            if(!$src = file_get_contents($src)) {
                return false;
            }
        }
        $tokens = token_get_all($src);
        $new = "";
        $c = count($tokens);
        $iw = false; // ignore whitespace
        $ih = false; // in HEREDOC
        $ls = "";    // last sign
        $ot = null;  // open tag
        for($i = 0; $i < $c; $i++) {
            $token = $tokens[$i];
            if(is_array($token)) {
                list($tn, $ts) = $token; // tokens: number, string, line
                $tname = token_name($tn);
                if($tn == T_INLINE_HTML) {
                    $new .= $ts;
                    $iw = false;
                } else {
                    if($tn == T_OPEN_TAG) {
                        if(strpos($ts, " ") || strpos($ts, "\r") || strpos($ts, "\n") || strpos($ts, "\t")) {
                            $ts = rtrim($ts);
                        }
                        $new .=$ts.PHP_EOL; //substr($ts,5);
                        $ot = T_OPEN_TAG;
                        $iw = true;
                    } elseif($tn == T_OPEN_TAG_WITH_ECHO) {
                        $new .= $ts;
                        $ot = T_OPEN_TAG_WITH_ECHO;
                        $iw = true;
                    } elseif($tn == T_CLOSE_TAG) {
                        if($ot == T_OPEN_TAG_WITH_ECHO) {
                            $new = rtrim($new, "; ");
                        } else {
                            $ts = " ".$ts;
                        }
                        $new .= $ts;
                        $ot = null;
                        $iw = false;
                    } elseif(in_array($tn, $IW)) {
                        $new .= $ts;
                        $iw = true;
                    } elseif($tn == T_CONSTANT_ENCAPSED_STRING
                        || $tn == T_ENCAPSED_AND_WHITESPACE)
                    {
                        if($ts[0] == '"') {
                            $ts = addcslashes($ts, "\t\n\r");
                        }
                        $new .= $ts;
                        $iw = true;
                    } elseif($tn == T_WHITESPACE) {
                        $nt = @$tokens[$i+1];
                        if(!$iw && (!is_string($nt) || $nt == '$') && !in_array($nt[0], $IW)) {
                            $new .= " ";
                        }
                        $_last=substr($new,-1);
                        if($_last==';' || $_last=='{' || $_last=='}')$new .= PHP_EOL;
                        $iw = false;
                    } elseif($tn == T_START_HEREDOC) {
                        $new .= "<<<S\n";
                        $iw = false;
                        $ih = true; // in HEREDOC
                    } elseif($tn == T_END_HEREDOC) {
                        $new .= "S;";
                        $iw = true;
                        $ih = false; // in HEREDOC
                        for($j = $i+1; $j < $c; $j++) {
                            if(is_string($tokens[$j]) && $tokens[$j] == ";") {
                                $i = $j;
                                break;
                            } else if($tokens[$j][0] == T_CLOSE_TAG) {
                                break;
                            }
                        }
                    } elseif($tn == T_COMMENT || $tn == T_DOC_COMMENT) {
                        $iw = true;
                    } else {
                        //if(!$ih) {
                        //    $ts = strtolower($ts);
                        //}
                        $new .= $ts;
                        $iw = false;
                    }
                }
                $ls = "";
            } else {
                if(($token != ";" && $token != ":") || $ls != $token) {
                    $new .= $token;
                    $ls = $token;
                }
                $iw = true;
            }
        }
        return $new;
    }
}
