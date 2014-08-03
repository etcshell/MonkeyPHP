<?php
namespace Library;

/**
 * FTPClient
 * FTP客户端类
 * @package Library
 */
class FTPClient {

    private $link;
    private $falseMessage;
    private $timeout = 50; //超时（秒）
    /**
     * 连接FTP
     * @param string $server 服务器名
     * @param integer $port 服务器端口
     * @param string $username 用户名
     * @param string $password 密码
     * @return boolean
     */
    public function connect($server, $username, $password, $port = 21) {
        //参数分析
        if (!$server || !$username || !$password) {
            return false;
        }
        $this->link = ftp_connect($server, $port);
        if (!$this->link) {
            $this->falseMessage = '不能连接到FTP！';
            return false;
        }
        @ftp_set_option($this->link, FTP_TIMEOUT_SEC, $this->timeout);
        if (@!ftp_login($this->link, $username, $password)) {
            $this->falseMessage = '不能登录到FTP！请检查用户名和密码。';
            return false;
        }
        //打开被动模拟
        @ftp_pasv($this->link, 1);
        return true;
    }

    /**
     * FTP-文件上传
     * @param string $localFile 本地文件
     * @param string $ftpFile Ftp文件
     * @return bool
     */
    public function upload($localFile, $ftpFile) {
        if (!$localFile || !$ftpFile) {
            return false;
        }
        $ftppath = dirname($ftpFile);
        if (!empty($ftppath)) {
            //创建目录
            $this->makeDir($ftppath);
            @ftp_chdir($this->link, $ftppath);
            $ftpFile = basename($ftpFile);
        }
        $ret = ftp_nb_put($this->link, $ftpFile, $localFile, FTP_BINARY);
        while ($ret == FTP_MOREDATA) {
            $ret = ftp_nb_continue($this->link);
        }
        return $ret == FTP_FINISHED;
    }

    /**
     * FTP-文件下载
     * @param string $localFile 本地文件
     * @param string $ftpFile Ftp文件
     * @return bool
     */
    public function download($localFile, $ftpFile) {
        if (!$localFile || !$ftpFile) {
            return false;
        }
        $ret = ftp_nb_get($this->link, $localFile, $ftpFile, FTP_BINARY);
        while ($ret == FTP_MOREDATA) {
            $ret = ftp_nb_continue($this->link);
        }
        if ($ret != FTP_FINISHED) {
            return false;
        }
        return true;
    }

    /**
     * FTP-创建目录
     * @param string $path 路径地址
     * @return bool
     */
    public function makeDir($path) {
        if (!$path) {
            return false;
        }
        $dir = explode("/", $path);
        $path = ftp_pwd($this->link) . '/';
        $ret = true;
        for ($i = 0; $i < count($dir); $i++) {
            $path = $path . $dir[$i] . '/';
            if (!@ftp_chdir($this->link, $path)) {
                if (!@ftp_mkdir($this->link, $dir[$i])) {
                    $ret = false;
                    break;
                }
            }
            @ftp_chdir($this->link, $path);
        }
        return !!$ret;
    }

    /**
     * FTP-删除文件目录
     * @param string $dir 删除文件目录
     * @return bool
     */
    public function deleteDir($dir) {
        $dir = $this->checkpath($dir);
        if (@!ftp_rmdir($this->link, $dir)) {
            return false;
        }
        return true;
    }

    /**
     * FTP-删除文件
     * @param string $file 删除文件
     * @return bool
     */
    public function deleteFile($file) {
        $file = $this->checkpath($file);
        if (@!ftp_delete($this->link, $file)) {
            return false;
        }
        return true;
    }

    /**
     * FTP-FTP上的文件列表
     * @param string $path 路径
     * @return bool
     */
    public function nlist($path = '/') {
        return ftp_nlist($this->link, $path);
    }

    /**
     * 改变文件权限值
     * @param string $file 文件名
     * @param int $value 值
     * @return bool
     */
    public function chmod($file, $value = 0777) {
        return @ftp_chmod($this->link, $value, $file);
    }

    /**
     * FTP-返回文件大小
     * @param string $file 文件
     * @return bool
     */
    public function fileSize($file) {
        return ftp_size($this->link, $file);
    }

    /**
     * FTP-文件修改时间
     * @param string $file 文件
     * @return bool
     */
    public function mdtime($file) {
        return ftp_mdtm($this->link, $file);
    }

    /**
     * FTP-更改ftp上的文件名称
     * @param string $oldname 旧文件
     * @param string $newname 新文件名称
     * @return bool
     */
    public function rename($oldname, $newname) {
        return ftp_rename($this->link, $oldname, $newname);
    }

    /**
     * FTP-检测path
     * @param $path
     * @return string
     */
    private function checkpath($path) {
        return (isset($path)) ? trim(str_replace('\\', '/', $path), '/') . '/' : '/';
    }

    /**
     * 析构函数
     * @return void
     */
    public function __destruct() {
        if ($this->link) {
            ftp_close($this->link);
        }
    }
}