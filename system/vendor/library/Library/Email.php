<?php
namespace Library;

/**
 * Email
 * 电子邮件处理类
 * @package Library
 */
final class Email {
    var $smtp_port;
    var $time_out;
    var $host_name;
    var $log_file;
    var $relay_host;
    var $debug;
    var $auth;
    var $user;
    var $pass;
    var $sock;

    /**
     * 发送电子邮件
     * 构造函数（主要传递邮箱登录信息）
     * @param string $user 用户名
     * @param string $pass 密码
     * @param string $relay_host 　发件邮箱（SMTP服务器）地址
     * @param int $smtp_port 　邮箱（SMTP服务器）端口
     * @param bool $auth 是否使用身份验证，默认为否
     */
    public function __construct($user, $pass, $relay_host = "", $smtp_port = 25, $auth = false) {
        $this->debug = FALSE;
        $this->smtp_port = $smtp_port;
        $this->relay_host = $relay_host;
        $this->time_out = 30; //is used in fsockopen()
        $this->auth = $auth; //auth
        $this->user = $user;
        $this->pass = $pass;
        $this->host_name = "localhost"; //is used in HELO command
        $this->log_file = "";
        $this->sock = FALSE;
    }

    /**
     * 发送邮件
     * @param string $to 收件人邮箱
     * @param string $from 发件人邮箱
     * @param string $subject 邮件主题
     * @param string $body 邮件正文
     * @param string $type 邮件类型（HTML/TXT）,TXT为文本邮件
     * @param string $cc
     * @param string $bcc
     * @param string $additional_headers 头部文件
     * @return bool 发送状态，成功为TRUE，失败为FALSE
     */
    public function sendEmail($to, $from, $subject = "", $body = "", $type = "HTML", $cc = "", $bcc = "", $additional_headers = "") {
        $mail_from = $this->getAddress($this->stripComment($from));
        $body = preg_replace('/(^|(\r\n))(\\.)/', '\\1.\\3', $body);
        $header = "MIME-Version:1.0\r\n";
        if ($type == "HTML") {
            $header .= "Content-Type:text/html;charset=utf-8 \r\n";
        }
        $header .= "To: " . $to . "\r\n";
        if ($cc != "") {
            $header .= "Cc: " . $cc . "\r\n";
        }
        $header .= "From: $from<" . $from . ">\r\n";
        $header .= "Subject: " . $subject . "\r\n";
        $header .= $additional_headers;
        $header .= "Date: " . date("r") . "\r\n";
        $header .= "X-Mailer:By Redhat (PHP/" . phpversion() . ")\r\n";
        list($msec, $sec) = explode(" ", microtime());
        $header .= "Message-ID: <" . date("YmdHis", $sec) . "." . ($msec * 1000000) . "." . $mail_from . ">\r\n";
        $TO = explode(",", $this->stripComment($to));

        if ($cc != "") {
            $TO = array_merge($TO, explode(",", $this->stripComment($cc)));
        }

        if ($bcc != "") {
            $TO = array_merge($TO, explode(",", $this->stripComment($bcc)));
        }

        $sent = TRUE;
        foreach ($TO as $rcpt_to) {
            $rcpt_to = $this->getAddress($rcpt_to);
            if (!$this->smtpSockopen($rcpt_to)) {
                $this->logWrite("Error: Cannot send email to " . $rcpt_to . "\n");
                $sent = FALSE;
                continue;
            }
            if ($this->smtpSend($this->host_name, $mail_from, $rcpt_to, $header, $body)) {
                $this->logWrite("E-mail has been sent to <" . $rcpt_to . ">\n");
            }
            else {
                $this->logWrite("Error: Cannot send email to <" . $rcpt_to . ">\n");
                $sent = FALSE;
            }
            fclose($this->sock);
            $this->logWrite("Disconnected from remote host\n");
        }
        //echo "<br>";
        //echo $header;
        return $sent;
    }

    private function smtpSend($helo, $from, $to, $header, $body = "") {
        if (!$this->smtpPutCMD("HELO", $helo)) {
            return $this->smtpError("sending HELO command");
        }
        if ($this->auth) {
            if (!$this->smtpPutCMD("AUTH LOGIN", base64_encode($this->user))) {
                return $this->smtpError("sending HELO command");
            }
            if (!$this->smtpPutCMD("", base64_encode($this->pass))) {
                return $this->smtpError("sending HELO command");
            }
        }
        if (!$this->smtpPutCMD("MAIL", "FROM:<" . $from . ">")) {
            return $this->smtpError("sending MAIL FROM command");
        }
        if (!$this->smtpPutCMD("RCPT", "TO:<" . $to . ">")) {
            return $this->smtpError("sending RCPT TO command");
        }
        if (!$this->smtpPutCMD("DATA")) {
            return $this->smtpError("sending DATA command");
        }
        if (!$this->smtpMessage($header, $body)) {
            return $this->smtpError("sending message");
        }
        if (!$this->smtpEom()) {
            return $this->smtpError("sending <CR><LF>.<CR><LF> [EOM]");
        }
        if (!$this->smtpPutCMD("QUIT")) {
            return $this->smtpError("sending QUIT command");
        }
        return TRUE;
    }

    private function smtpSockopen($address) {
        if ($this->relay_host == "") {
            return $this->smtpSockopenMX($address);
        }
        else {
            return $this->smtpSockopenRelay();
        }
    }

    private function smtpSockopenRelay() {
        $this->logWrite("Trying to " . $this->relay_host . ":" . $this->smtp_port . "\n");
        $this->sock = @fsockopen($this->relay_host, $this->smtp_port, $errno, $errstr, $this->time_out);
        if (!($this->sock && $this->smtpOK())) {
            $this->logWrite("Error: Cannot connenct to relay host " . $this->relay_host . "\n");
            $this->logWrite("Error: " . $errstr . " (" . $errno . ")\n");
            return FALSE;
        }
        $this->logWrite("Connected to relay host " . $this->relay_host . "\n");
        return TRUE;;
    }

    private function smtpSockopenMX($address) {
        $domain = ereg_replace("^.+@([^@]+)$", "\\1", $address);
        $MXHOSTS = NULL;
        if (!@getmxrr($domain, $MXHOSTS)) {
            $this->logWrite("Error: Cannot resolve MX \"" . $domain . "\"\n");
            return FALSE;
        }
        foreach ($MXHOSTS as $host) {
            $this->logWrite("Trying to " . $host . ":" . $this->smtp_port . "\n");
            $this->sock = @fsockopen($host, $this->smtp_port, $errno, $errstr, $this->time_out);
            if (!($this->sock && $this->smtpOK())) {
                $this->logWrite("Warning: Cannot connect to mx host " . $host . "\n");
                $this->logWrite("Error: " . $errstr . " (" . $errno . ")\n");
                continue;
            }
            $this->logWrite("Connected to mx host " . $host . "\n");
            return TRUE;
        }
        $this->logWrite("Error: Cannot connect to any mx hosts (" . implode(", ", $MXHOSTS) . ")\n");
        return FALSE;
    }

    private function smtpMessage($header, $body) {
        fputs($this->sock, $header . "\r\n" . $body);
        $this->smtpDebug("> " . str_replace("\r\n", "\n" . "> ", $header . "\n> " . $body . "\n> "));
        return TRUE;
    }

    private function smtpEom() {
        fputs($this->sock, "\r\n.\r\n");
        $this->smtpDebug(". [EOM]\n");
        return $this->smtpOK();
    }

    private function smtpOK() {
        $response = str_replace("\r\n", "", fgets($this->sock, 512));
        $this->smtpDebug($response . "\n");
        if (!ereg("^[23]", $response)) {
            fputs($this->sock, "QUIT\r\n");
            fgets($this->sock, 512);
            $this->logWrite("Error: Remote host returned \"" . $response . "\"\n");
            return FALSE;
        }
        return TRUE;
    }

    private function smtpPutCMD($cmd, $arg = "") {
        if ($arg != "") {
            if ($cmd == "") {
                $cmd = $arg;
            }
            else $cmd = $cmd . " " . $arg;
        }
        fputs($this->sock, $cmd . "\r\n");
        $this->smtpDebug("> " . $cmd . "\n");
        return $this->smtpOK();
    }

    private function smtpError($string) {
        $this->logWrite("Error: Error occurred while " . $string . ".\n");
        return FALSE;
    }

    private function logWrite($message) {
        $this->smtpDebug($message);
        if ($this->log_file == "") {
            return TRUE;
        }
        $message = date("M d H:i:s ") . get_current_user() . "[" . getmypid() . "]: " . $message;
        if (!@file_exists($this->log_file) || !($fp = @fopen($this->log_file, "a"))) {
            $this->smtpDebug("Warning: Cannot open log file \"" . $this->log_file . "\"\n");
            return FALSE;
        }
        flock($fp, LOCK_EX);
        fputs($fp, $message);
        fclose($fp);
        return TRUE;
    }

    private function stripComment($address) {
        $comment = "\\([^()]*\\)";
        while (ereg($comment, $address)) {
            $address = ereg_replace($comment, "", $address);
        }
        return $address;
    }

    private function getAddress($address) {
        $address = ereg_replace("([ \t\r\n])+", "", $address);
        $address = ereg_replace("^.*<(.+)>.*$", "\\1", $address);
        return $address;
    }

    private function smtpDebug($message) {
        if ($this->debug) {
            echo $message . "<br>";
        }
    }

    private function getAttachType($image_tag) { //
        $filedata = array();
        $img_file_con = fopen($image_tag, "r");
        $image_data = null;
        while ($tem_buffer = AddSlashes(fread($img_file_con, filesize($image_tag))))
            $image_data .= $tem_buffer;
        fclose($img_file_con);
        $filedata['context'] = $image_data;
        $filedata['filename'] = basename($image_tag);
        $extension = substr($image_tag, strrpos($image_tag, "."), strlen($image_tag) - strrpos($image_tag, "."));
        switch ($extension) {
            case ".gif":
                $filedata['type'] = "image/gif";
                break;
            case ".gz":
                $filedata['type'] = "application/x-gzip";
                break;
            case ".htm":
                $filedata['type'] = "text/html";
                break;
            case ".html":
                $filedata['type'] = "text/html";
                break;
            case ".jpg":
                $filedata['type'] = "image/jpeg";
                break;
            case ".tar":
                $filedata['type'] = "application/x-tar";
                break;
            case ".txt":
                $filedata['type'] = "text/plain";
                break;
            case ".zip":
                $filedata['type'] = "application/zip";
                break;
            default:
                $filedata['type'] = "application/octet-stream";
                break;
        }
        return $filedata;
    }
}