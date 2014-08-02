<?php
namespace Library;

/**
 * Download
 * 文件下载类
 * @package Library
 */
class Download {
    /**
     * 下载文件
     * @param string $file_path 文件全路径
     * @param string $rename 重命名文件名
     * @return array 下载状态的信息，二维数组array('status'=>成功为True失败为False,'info'=>成功时为文件字节数失败时为错误信息)
     */
    public static function getFile($file_path, $rename = NULL) {
        if (DIRECTORY_SEPARATOR == '\\') {
            $file_path = iconv("UTF-8", "GB2312//IGNORE", $file_path);
        }
        if (!file_exists($file_path)) {
            return notice(FALSE, '你要下载的文件不存在！');
        }
        $file_size_string = sprintf("%u", filesize($file_path));
        $file_size = intval($file_size_string);
        if (headers_sent()) {
            return notice(FALSE, '文件头已经输出了！');
        }
        $filename = $rename == NULL ? basename($file_path) : $rename;
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . htmlspecialchars($filename) . '; charset=utf-8');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . $file_size_string);
        if (substr(php_sapi_name(), 0, 6) == 'apache') {
            header('X-Sendfile: ' . $file_path); //这句将使得文件发送转由apache处理，因此文件下载速度非常快。
            return notice(TRUE, $file_size);
        }
        ob_clean();
        flush();
        if ($file_size > 1 * (1024 * 1024)) {
            return self::_readFileOfLarge($file_path);
        }
        else {
            $info = @readfile($file_path);
            return notice($info, $info ? $info : '文件读取失败');
        }
    }

    private static function _readFileOfLarge($file_path, $chunk_size = 1024) {
        $cnt = $buffer = 0;
        $handle = fopen($file_path, 'rb');
        if ($handle === false) {
            return notice(FALSE, '不能打开这个大文件！');
        }
        while (!feof($handle)) {
            $buffer = fread($handle, $chunk_size);
            echo $buffer;
            ob_flush();
            flush();
            $cnt += strlen($buffer);
        }
        if (!fclose($handle)) {
            throw new \Exception('下载文件后出错了！向浏览器发送完大文件后关闭文件失败。');
        }
        return notice(TRUE, $cnt);
    }
}
