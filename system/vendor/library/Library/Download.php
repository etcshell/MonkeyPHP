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
     * @param string $filePath 文件全路径
     * @param string $rename 重命名文件名
     * @return array 下载状态的信息，二维数组array('status'=>成功为True失败为False,'info'=>成功时为文件字节数失败时为错误信息)
     */
    public static function getFile($filePath, $rename = NULL) {
        if (DIRECTORY_SEPARATOR == '\\') {
            $filePath = iconv("UTF-8", "GB2312//IGNORE", $filePath);
        }
        if (!file_exists($filePath)) {
            return notice(false, '你要下载的文件不存在！');
        }
        $fileSizeString = sprintf("%u", filesize($filePath));
        $fileSize = intval($fileSizeString);
        if (headers_sent()) {
            return notice(false, '文件头已经输出了！');
        }
        $filename = $rename == NULL ? basename($filePath) : $rename;
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . htmlspecialchars($filename) . '; charset=utf-8');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . $fileSizeString);
        if (substr(php_sapi_name(), 0, 6) == 'apache') {
            header('X-Sendfile: ' . $filePath); //这句将使得文件发送转由apache处理，因此文件下载速度非常快。
            return notice(true, $fileSize);
        }
        ob_clean();
        flush();
        if ($fileSize > 1 * (1024 * 1024)) {
            return self::readFileOfLarge($filePath);
        }
        else {
            $info = @readfile($filePath);
            return notice($info, $info ? $info : '文件读取失败');
        }
    }

    private static function readFileOfLarge($filePath, $chunkSize = 1024) {
        $cnt = $buffer = 0;
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            return notice(false, '不能打开这个大文件！');
        }
        while (!feof($handle)) {
            $buffer = fread($handle, $chunkSize);
            echo $buffer;
            ob_flush();
            flush();
            $cnt += strlen($buffer);
        }
        if (!fclose($handle)) {
            throw new \Exception('下载文件后出错了！向浏览器发送完大文件后关闭文件失败。');
        }
        return notice(true, $cnt);
    }
}
