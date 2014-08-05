<?php
namespace Uploader;

/**
 * qqFileUploader
 * QQ邮箱的文件上传效果
 * @category   文件上传
 * @package    分享包
 * @author     HuangYi
 * @copyright  Copyright (c) 2012-4-1——至今
 * @license    New BSD License
 * @version    $Id: qqFileUploader.class.php 版本号 2012-4-1 $
 * 使用方法：
 * // list of valid extensions, ex. array("jpeg", "xml", "bmp")
 * $allowedExtensions = array();
 * // max file size in bytes
 * $sizeLimit = 10 * 1024 * 1024;
 * $uploader = new qqFileUploader($allowedExtensions, $sizeLimit,'GB2312');
 * $result = $uploader->handleUpload(__dir_index__.'/upload/');
 * // to pass data through iframe you will need to encode all html tags
 * echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
 *
 */

class Uploader {

    private $allowedExtensions = array();
    private $sizeLimit = 10485760;
    /**
     * @var FormRequest
     */
    private $file;
    private $systemCharset = 'GB2312';

    /**
     * QQ上传
     * @param array $allowedExtensions 允许的扩展名
     * @param integer $sizeLimit 最大字节数，这是要和ini设置配合的，php.ini是全局的，可以设置大一点，这里是细化到每个具体上传点，设置小一点，倒过来不行哈
     */
    public function __construct(array $allowedExtensions = array(), $sizeLimit = 10485760) {
        $this->systemCharset = DIRECTORY_SEPARATOR == '\\' ? 'GB2312' : 'utf-8';
        $allowedExtensions = array_map("strtolower", $allowedExtensions);
        $this->allowedExtensions = $allowedExtensions;
        $this->sizeLimit = $sizeLimit;
        $this->checkServerSettings();
        if (isset($_GET['qqfile'])) {
            $this->file = new AjaxRequest();
        }
        elseif (isset($_FILES['qqfile'])) {
            $this->file = new FormRequest();
        }
        else {
            $this->file = false;
        }
    }

    private function checkServerSettings() {
        $postSize = $this->toBytes(ini_get('post_max_size'));
        $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));
        if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit) {
            $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';
            die("{'error':'increase post_max_size and upload_max_filesize to $size'}");
        }
    }

    private function toBytes($str) {
        $powers = array(
            'b' => 0,
            'k' => 1,
            'm' => 2,
            'g' => 3,
            't' => 4,
            'p' => 5,
            'e' => 6,
            'z' => 7,
            'y' => 8
        );
        $str = trim($str);
        strtolower(substr($str, -1)) == 'b' and $str = substr($str, 0, -1);
        $unit = substr($str, 0, -1);
        if (isset($powers[$unit])) {
            $str = substr($str, 0, -1);
        }
        else {
            $unit = 'b';
        }

        return $str * pow(1024, $powers[$unit]);
    }

    /**
     * 处理上传文件
     * @param string $uploadDir 保存上传文件的目录
     * @param string $replaceFile 是否替换指定文件
     * @return array('success'=>true,'filename'=>string) or array('error'=>'error message')
     */
    public function saveToDir($uploadDir, $replaceFile = '') {
        $uploadDir = rtrim($uploadDir, '/') . '/';
        if (!$this->dirCheck($uploadDir)) {
            return array('error' => "Server error. Upload directory isn't exist.");
        }
        if (!is_writable($uploadDir)) {
            return array('error' => "Server error. Upload directory isn't writable.");
        }
        if (!$this->file) {
            return array('error' => 'No files were uploaded.');
        }
        $size = $this->file->getSize();
        if ($size == 0) {
            return array('error' => 'File is empty');
        }
        if ($size > $this->sizeLimit) {
            return array('error' => 'File is too large');
        }
        $pathinfo = pathinfo($this->file->getName());
        $extLen = strlen($pathinfo['extension']) + 1;
        $basename = substr($this->file->getName(), 0, 0 - $extLen);
        if ($this->systemCharset == 'GB2312') {
            $basename = iconv("UTF-8", "GB2312//IGNORE", $basename);
        }
        //$filename = md5(uniqid());
        $ext = $pathinfo['extension'];
        if ($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)) {
            $these = implode(', ', $this->allowedExtensions);
            return array('error' => 'File has an invalid extension, it should be one of ' . $these . '.');
        }

        if ($replaceFile) {
            $basename = $replaceFile;
        }
        else {
            /// don't overwrite previous files that were uploaded
            while (file_exists($uploadDir . $basename . '.' . $ext)) {
                $basename .= rand(10, 99);
            }
        }

        $basename = strtr($basename, '.', '_'); //消除因为apache配置失误造成的上传漏洞。
        $filename = $uploadDir . $basename . '.' . $ext;
        if ($this->file->save($filename)) {
            return array('success' => true, 'filename' => $filename);
        }
        else {
            return array(
                'error' => 'Could not save uploaded file.' . 'The upload was cancelled, or server error encountered'
            );
        }
    }

    protected function dirCheck($targetDir) {
        if (is_dir($targetDir)) {
            return true;
        }

        return @mkdir($targetDir, 0777, true);
    }
}