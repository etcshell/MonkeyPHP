<?php

/**
 * 格式化目录变量：1、分隔符统一替换为'/'2、末尾统一去掉'/'
 *
 * @param string $dir 待格式化的字符串
 *
 * @return string
 */
function dir_format($dir)
{
    DIRECTORY_SEPARATOR != '/' and $dir = strtr($dir, DIRECTORY_SEPARATOR, '/');
    return rtrim($dir, '/');
}

/**
 * 简单打印函数
 *
 * @param mixed $var
 */
function pre($var)
{
    echo '<pre>';
    print_r($var);
    echo '</pre>';
}

/**
 * dump
 * 浏览器友好的变量输出
 * 出自ThinkPHP
 *
 * @param mixed $var 要打印的数据
 * @param bool $echo 是否直接输出
 * @param string $label 输出标签
 * @param bool $strict 精确输出
 *
 * @return string|void
 */
function dump($var, $echo = true, $label = null, $strict = true)
{
    $label = ($label === null) ? '' : rtrim($label) . ' ';
    if (!$strict) {
        if (ini_get('html_errors')) {
            $output = print_r($var, true);
            $output = "<pre>" . $label . htmlspecialchars($output, ENT_QUOTES) . "</pre>";
        } else {
            $output = $label . " : " . print_r($var, true);
        }
    } else {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (!ini_get('xdebug.overload_var_dump')) {
            $output = preg_replace('/\]\=\>\n(\s+)/m', "] => ", $output);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        }
    }
    if ($echo) {
        echo($output);
        return null;
    } else {
        return $output;
    }
}

/**
 * 公用通知包装
 *
 * @param bool $status 状态
 * @param mixed $data 通知的数据
 * @param string $msg 附加信息
 *
 * @return array
 */
function notice($status, $data, $msg = '')
{
    return array('status' => (bool)$status, 'msg' => $msg, 'data' => $data);
}

//如果json_encode没有定义，则定义json_encode函数，常用于返回ajax数据
if (!function_exists('json_encode')) {
    /**
     * @param $value
     * @return string
     */
    function format_json_value(&$value)
    {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } else if (is_int($value)) {
            $value = intval($value);
        } else if (is_float($value)) {
            $value = floatval($value);
        } else if (defined($value) && $value === null) {
            $value = strval(constant($value));
        } else if (is_string($value)) {
            $value = '"' . addslashes($value) . '"';
        }
        return $value;
    }

    /**
     * @param $data
     * @return string
     */
    function json_encode($data)
    {
        if (is_object($data)) {
            $data = get_object_vars($data); //对象转换成数组
        } else if (!is_array($data)) {
            return format_json_value($data); // 普通格式直接输出
        }
        // 判断是否关联数组
        if (empty($data) || is_numeric(implode('', array_keys($data)))) {
            $assoc = false;
        } else {
            $assoc = true;
        }
        // 组装 Json字符串
        $json = $assoc ? '{' : '[';
        foreach ($data as $key => $val) {
            if (!is_null($val)) {
                if ($assoc) {
                    $json .= '"' . $key . '":' . json_encode($val) . ',';
                } else {
                    $json .= json_encode($val) . ',';
                }
            }
        }
        if (strlen($json) > 1) {
            $json = substr($json, 0, -1);
        } // 加上判断 防止空数组
        $json .= $assoc ? '}' : ']';
        return $json;
    }
}

/**
 *
 * 获取接受JS传递中文编码
 * 本函数基本用不上，仅作收藏
 * 作者：Min
 *
 * @param array|string $data
 * @return array|string
 */
function unescape_js_cn($data)
{
    if (is_array($data)) return array_map(__FUNCTION__, $data);
    $ret = '';
    $len = strlen($data);
    for ($i = 0; $i < $len; $i++) {
        if ($data[$i] == '%' && $data[$i + 1] == 'u') {
            $val = hexdec(substr($data, $i + 2, 4));
            if ($val < 0x7f)
                $ret .= chr($val);
            else if ($val < 0x800)
                $ret .= chr(0xc0 | ($val >> 6))
                    . chr(0x80 | ($val & 0x3f));
            else
                $ret .= chr(0xe0 | ($val >> 12))
                    . chr(0x80 | (($val >> 6) & 0x3f))
                    . chr(0x80 | ($val & 0x3f));
            $i += 5;
        } else if ($data[$i] == '%') {
            $ret .= urldecode(substr($data, $i, 3));
            $i += 2;
        } else $ret .= $data[$i];
    }
    return $ret;
}

/**
 * 转义变量 用于安全调用，如SQL值
 *
 * @param array|string $data 字符或数组
 *
 * @return array|string
 */
function addslashes_deep($data)
{
    return is_array($data) ? array_map(__FUNCTION__, $data) : addslashes($data);
}

/**
 * 对变量进行反转义到原始数据
 *
 * @param array|string $data 字符或数组
 *
 * @return array|string
 */
function stripslashes_deep($data)
{
    return is_array($data) ? array_map(__FUNCTION__, $data) : stripslashes($data);
}

/**
 * 格式化时间
 * @param string $timestamp 时间戳
 * @param string $format = rfc822 | rfc1123 | rfc1036 | asctime
 * @return string

function date_format($timestamp, $format = 'rfc1123')
 * {
 * $format = strtolower($format);
 * if ($format == 'rfc1123' or $format=='rfc822')
 * {
 * return substr(gmdate('r', $timestamp), 0, -5).'GMT';
 * }
 * elseif ($format == 'rfc1036')
 * {
 * return gmdate('l, d-M-y H:i:s ', $timestamp).'GMT';
 * }
 * elseif ($format == 'asctime')
 * {
 * return gmdate('D M j H:i:s', $timestamp);
 * }
 * else
 * {
 * return '';
 * }
 * }*/
/******************目录操作有关函数*******************/

/**
 * 检查目录的可写入性，不可写入时，尝试变为可写入。
 *
 * @param string $targetDir 目标目录
 *
 * @return boolean
 */
function dir_check_writable($targetDir)
{
    //检查缓存目录是否可写，不可写则修改它的属性
    if (!is_dir($targetDir)) {
        return false;
    } elseif (!is_writable($targetDir) && !@chmod($targetDir, 0777)) {
        return false;
    }

    return true;
}

/**
 * 检查目录是否存在
 * 当目录不存在时创建它
 *
 * @param string $targetDir 目标目录,不支持相对目录
 *
 * @return boolean
 */
function dir_check($targetDir)
{
    //$targetDir=dir_format($targetDir);
    if (is_dir($targetDir)) {
        return true;
    }
//
//    if ($targetDir[0] == '.') {
//        return false;
//    }

    return @mkdir($targetDir, 0777, true);

//    $tempDir = explode('/', $targetDir);
//    $subDir = $tempDir[0];
//    array_shift($tempDir);
//
//    foreach ($tempDir as $value) {
//
//        if ($value == '') {
//            continue;
//        }
//
//        if ($value == '.' || $value == '..') {
//            return false;
//        }
//
//        $subDir = $subDir . '/' . $value;
//
//        if (is_dir($subDir)) {
//            continue;
//        }
//
//        //创建目录
//        if (!@mkdir($subDir, 0777)) {
//            return false;
//        }
//    }
//
//    return true;
}

/**
 * 递归删除文件夹
 *
 * @param string $targetDir 目标文件夹
 *
 * @return boolean
 */
function dir_delete($targetDir)
{
    if (!is_dir($targetDir)) {
        return true;
    }

    $handle = opendir($targetDir);

    if ($handle) {

        while (($item = readdir($handle)) !== false) {

            if ($item == '.' || $item == '..') {
                continue;
            }

            $itemFile = $targetDir . '/' . $item;

            if (is_dir($itemFile)) {
                dir_delete($itemFile);
            } else {
                unlink($itemFile);
            }
        }

        closedir($handle);
        rmdir($targetDir);
    }

    return true;
}

/**
 * 将一个文件夹内容，复制或移动到另一个文件夹
 *
 * @param string $source 源文件夹名
 * @param string $target 目标文件夹
 * @param boolean $delete_source 是否删除源文件夹（是则相当于移动，否则相当于复制）
 *
 * @return boolean
 */
function dir_copy($source, $target, $delete_source = false)
{
    $source = dir_format($source);
    $target = dir_format($target);

    if ($source == $target) {
        return true;
    }

    if (!is_dir($source)) {
        return false;
    }

    dir_check($target);
    $handle = opendir($source);

    if (!$handle) {
        return false;
    }

    $source_path = $target_path = '';

    while (($item = readdir($handle)) !== false) {

        if ($item == '.' || $item == '..') {
            continue;
        }

        $source_path = $source . '/' . $item;
        $target_path = $target . '/' . $item;

        if (is_dir($source_path)) {
            dir_copy($source_path, $target_path, $delete_source);
            if ($delete_source) {
                rmdir($source_path);
            }

        } else {
            copy($source_path, $target_path);

            if ($delete_source) {
                unlink($source_path);
            }
        }
    }

    closedir($handle);

    return true;
}

/**
 * save_variable_to_file
 * 将一个变量写入一个文件中，用$var=include($file)就可以获取保存的变量了
 *
 * @param string $file 文件名
 * @param string|array $variable 变量内容
 */
function file_save_variable($file, $variable)
{
    $variable = '<?php' . PHP_EOL . 'return ' . var_export($variable, true) . ' ;';
    file_put_contents($file, $variable, LOCK_EX); //echo '<br/>保存扫描结果到缓存文件中...<br/>';
}

/**
 * 将变量写入一个序列化文件中
 *
 * @param string $file 文件名
 * @param string|array $variable 变量内容
 */
function file_save_serialize($file, $variable)
{
    file_put_contents($file, serialize($variable), LOCK_EX); //echo '<br/>保存扫描结果到缓存文件中...<br/>';
}

/**
 * 从一个序列化文件中读取并还原变量
 *
 * @param string $file 文件名
 *
 * @return mixed
 */
function file_read_serialize($file)
{
    return unserialize(file_get_contents($file));
}

/**
 * 将文件名的扩展名去掉，支持中文名
 *
 * @param string $filename 文件名的部分或完整名称（不含路径）
 *
 * @return string
 */
function file_basename($filename)
{
    $pathinfo = pathinfo($filename);
    //return $pathinfo['filename']; //理论上这行也可以，但没验证过。
    $extLen = 1 + strlen($pathinfo['extension']);
    return substr($pathinfo['basename'], 0, 0 - $extLen);
}


/**
 * 获取文件的真实类型
 * @param string $filename 文件名
 * @return string  失败为''（空字符串）
 */
function file_real_type($filename){
    static $typeMap=array(
        '-48-49'=>'doc|xls',
        '7790'=>'exe',
        '7784'=>'midi',
        '8075'=>'zip',
        '8297'=>'rar',
        '7173'=>'gif',
        '255216'=>'jpg',
        '6677'=>'bmp',
        '13780'=>'png',
        '104116'=>'txt',
    );
    if(!file_exists($filename)) return '';
    $file=  fopen($filename, 'rb');
    $bin = fread($file,2);
    fclose($file);
    $strInfo = @unpack("C2chars", $bin);
    $code = $strInfo['chars1'].''.$strInfo['chars2'];
    $code == '-1-40' and $code='255216';
    $code == '-11980' and $code='13780';
    return (string)$typeMap[$code];
}

/**
 * 字节数转换成MB格式等
 *
 * @param int $bit 数值
 *
 * @return string
 */
function bit_to_size($bit)
{
    if (!preg_match('/^[0-9]+$/', $bit)) {
        return 0;
    }

    $type = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    $j = 0;

    while ($bit >= 1024) {

        if ($j >= 5) {
            return $bit . $type[$j];
        }

        $bit = $bit / 1024;
        $j++;
    }

    return $bit . $type[$j];
}

/**
 * MB等字符大小格式转换成字节数
 *
 * @param string $size
 *
 * @return int
 */
function size_to_bit($size)
{
    $size = strtoupper($size);

    if (!preg_match('/^([1-9]\d+\.?\d*)(B|KB|MB|GB|TB|PB)?$/', $size, $matches)) {
        return 0;
    }

    $type = array('B' => 0, 'KB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4, 'PB' => 5);
    $bit = intval($matches[1]);

    if ($matches[0] == $matches[1]) {
        $i = 0;
    } else {
        $i = $type[$matches[2]];
    }

    $j = 0;

    while ($j < $i) {
        $bit = $bit * 1024;
        $j++;
    }

    return $bit;
}

/**
 * 生成客户端可访问的路径（前端绝对路径）
 *
 * @param string $real_path 实际路径
 *
 * @return string
 */
function file_to_url($real_path)
{
    $www = strtolower($_SERVER['DOCUMENT_ROOT']) . '/';
    $wwwLen = strlen($www);
    $real_path = strtolower($real_path);

    if (substr($real_path, 0, $wwwLen) != $www) {
        return '';
    }

    return '/' . substr($real_path, $wwwLen);
}

/**
 * 生成服务端可访问的路径（后端绝对路径）
 *
 * @param string $url 本站文件链接地址
 *
 * @return string 实际路径
 */
function url_to_file($url)
{
    if ($url[0] = '/') {
        return $_SERVER['DOCUMENT_ROOT'] . $url;
    }
    else {
        return $_SERVER['DOCUMENT_ROOT'] . '/' . $url;
    }
}

/**
 * ddos拦截防御
 * 作者未知
 */
function intercept_DDOS()
{
    static $isrun;
    if ($isrun) return;
    $isrun = true;
//查询禁止IP
    $ip = $_SERVER['REMOTE_ADDR'];
    $fileht = ".htaccess2";
    if (!file_exists($fileht)) file_put_contents($fileht, "");
    $filehtarr = @file($fileht);
    if (in_array($ip . "\r\n", $filehtarr)) exit("Warning:" . "<br>" . "Your IP address are forbided by some reason, IF you have any question Pls emill to shop@mydalle.com!");
//加入禁止IP
    $time = time();
    $fileforbid = "log/forbidchk.dat";
    if (file_exists($fileforbid)) {
        if ($time - filemtime($fileforbid) > 60) unlink($fileforbid);
        else {
            $fileforbidarr = @file($fileforbid);
            if ($ip == substr($fileforbidarr[0], 0, strlen($ip))) {
                if ($time - substr($fileforbidarr[1], 0, strlen($time)) > 600) unlink($fileforbid);
                elseif ($fileforbidarr[2] > 600) {
                    file_put_contents($fileht, $ip . "\r\n", FILE_APPEND);
                    unlink($fileforbid);
                } else {
                    $fileforbidarr[2]++;
                    file_put_contents($fileforbid, $fileforbidarr);
                }
            }
            unset($fileforbidarr);
        }
    }
//防刷新
    $str = "";
    $file = "log/ipdate.dat";
    if (!file_exists("log") && !is_dir("log")) mkdir("log", 0777);
    if (!file_exists($file)) file_put_contents($file, "");
    $allowTime = 120; //防刷新时间
    $allowNum = 10; //防刷新次数
    $uri = $_SERVER['REQUEST_URI'];
    $checkip = md5($ip);
    $checkuri = md5($uri);
    $yesno = true;
    $ipdate = @file($file);
    foreach ($ipdate as $k => $v) {
        $iptem = substr($v, 0, 32);
        $uritem = substr($v, 32, 32);
        $timetem = substr($v, 64, 10);
        $numtem = substr($v, 74);
        if ($time - $timetem < $allowTime) {
            if ($iptem != $checkip) $str .= $v;
            else {
                $yesno = false;
                if ($uritem != $checkuri) $str .= $iptem . $checkuri . $time . "1\r\n";
                elseif ($numtem < $allowNum) $str .= $iptem . $uritem . $timetem . ($numtem + 1) . "\r\n";
                else {
                    if (!file_exists($fileforbid)) {
                        $addforbidarr = array($ip . "\r\n", time() . "\r\n", 1);
                        file_put_contents($fileforbid, $addforbidarr);
                    }
                    file_put_contents("log/forbided_ip.log", $ip . "--" . date("Y-m-d H:i:s", time()) . "--" . $uri . "\r\n", FILE_APPEND);
                    $timepass = $timetem + $allowTime - $time;
                    exit("Warning:" . "<br>" . "Sorry,you are forbided by refreshing frequently too much, Pls wait for " . $timepass . " seconds to continue!");
                }
            }
        }
    }
    if ($yesno) $str .= $checkip . $checkuri . $time . "1\r\n";
    file_put_contents($file, $str);
}

/**
 * Or
 *
 * @return bool
 */
function isOr()
{
    $last = false;

    foreach (func_get_args() as $arg) {

        if ($arg) {
            return $arg;
        }

        $last = $arg;
    }

    return $last;
}

/**
 * And
 *
 * @return bool
 */
function isAnd()
{
    $last = false;

    foreach (func_get_args() as $arg) {

        if (!$arg) {
            return $arg;
        }

        $last = $arg;
    }

    return $last;
}

/**
 * 开启gzip压缩网页
 */
function gz_start()
{
    if (strpos($_SERVER["HTTP_ACCEPT_ENCODING"], 'gzip') !== false
        && !ini_get('zlib.output_compression')
        && extension_loaded("zlib")
        && function_exists('gzencode')
    ) {
        ob_start("ob_gzhandler");
    }
    /*方案二
    if(strpos($_SERVER["HTTP_ACCEPT_ENCODING"], 'gzip') !== false && extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
        ini_set('zlib.output_compression', 'On');
        ini_set('zlib.output_compression_level', $this->getConfig('gzip_level', 6));
    }
    */
}