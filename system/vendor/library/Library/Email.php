<?php
namespace Library;

/**
 * Email
 * 电子邮件处理类
 * @package Library
 */
final class Email {

    var $smtpPort;
    var $timeOut;
    var $hostName;
    var $logFile;
    var $relayHost;
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
     * @param string $relayHost 　发件邮箱（SMTP服务器）地址
     * @param int $smtpPort 　邮箱（SMTP服务器）端口
     * @param bool $auth 是否使用身份验证，默认为否
     */
    public function __construct($user, $pass, $relayHost = "", $smtpPort = 25, $auth = false) {
        $this->debug = false;
        $this->smtpPort = $smtpPort;
        $this->relayHost = $relayHost;
        $this->timeOut = 30; //is used in fsockopen()
        $this->auth = $auth; //auth
        $this->user = $user;
        $this->pass = $pass;
        $this->hostName = "localhost"; //is used in HELO command
        $this->logFile = "";
        $this->sock = false;
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
     * @param string $additionalHeaders 头部文件
     * @return bool 发送状态，成功为TRUE，失败为FALSE
     */
    public function sendEmail(
        $to, $from, $subject = "", $body = "", $type = "HTML", $cc = "", $bcc = "", $additionalHeaders = ""
    ) {
        $mailFrom = $this->getAddress($this->stripComment($from));
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
        $header .= $additionalHeaders;
        $header .= "Date: " . date("r") . "\r\n";
        $header .= "X-Mailer:By Redhat (PHP/" . phpversion() . ")\r\n";
        list($msec, $sec) = explode(" ", microtime());
        $header .= "Message-ID: <" . date("YmdHis", $sec) . "." . ($msec * 1000000) . "." . $mailFrom . ">\r\n";
        $TO = explode(",", $this->stripComment($to));

        if ($cc != "") {
            $TO = array_merge($TO, explode(",", $this->stripComment($cc)));
        }

        if ($bcc != "") {
            $TO = array_merge($TO, explode(",", $this->stripComment($bcc)));
        }

        $sent = true;
        foreach ($TO as $rcptTo) {
            $rcptTo = $this->getAddress($rcptTo);
            if (!$this->smtpSockopen($rcptTo)) {
                $this->logWrite("Error: Cannot send email to " . $rcptTo . "\n");
                $sent = false;
                continue;
            }
            if ($this->smtpSend(
                $this->hostName,
                $mailFrom,
                $rcptTo,
                $header,
                $body
            )
            ) {
                $this->logWrite("E-mail has been sent to <" . $rcptTo . ">\n");
            }
            else {
                $this->logWrite("Error: Cannot send email to <" . $rcptTo . ">\n");
                $sent = false;
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
            if (!$this->smtpPutCMD(
                "AUTH LOGIN",
                base64_encode($this->user)
            )
            ) {
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
        return true;
    }

    private function smtpSockopen($address) {
        if ($this->relayHost == "") {
            return $this->smtpSockopenMX($address);
        }
        else {
            return $this->smtpSockopenRelay();
        }
    }

    private function smtpSockopenRelay() {
        $this->logWrite(
            "Trying to " . $this->relayHost . ":" . $this->smtpPort . "\n"
        );
        $this->sock = @fsockopen(
            $this->relayHost,
            $this->smtpPort,
            $errno,
            $errstr,
            $this->timeOut
        );
        if (!($this->sock && $this->smtpOK())) {
            $this->logWrite(
                "Error: Cannot connenct to relay host " . $this->relayHost . "\n"
            );
            $this->logWrite("Error: " . $errstr . " (" . $errno . ")\n");
            return false;
        }
        $this->logWrite("Connected to relay host " . $this->relayHost . "\n");
        return true;;
    }

    private function smtpSockopenMX($address) {
        $domain = ereg_replace("^.+@([^@]+)$", "\\1", $address);
        $MXHOSTS = NULL;
        if (!@getmxrr($domain, $MXHOSTS)) {
            $this->logWrite("Error: Cannot resolve MX \"" . $domain . "\"\n");
            return false;
        }
        foreach ($MXHOSTS as $host) {
            $this->logWrite("Trying to " . $host . ":" . $this->smtpPort . "\n");
            $this->sock = @fsockopen(
                $host,
                $this->smtpPort,
                $errno,
                $errstr,
                $this->timeOut
            );
            if (!($this->sock && $this->smtpOK())) {
                $this->logWrite("Warning: Cannot connect to mx host " . $host . "\n");
                $this->logWrite("Error: " . $errstr . " (" . $errno . ")\n");
                continue;
            }
            $this->logWrite("Connected to mx host " . $host . "\n");
            return true;
        }
        $this->logWrite(
            "Error: Cannot connect to any mx hosts (" . implode(", ", $MXHOSTS) . ")\n"
        );
        return false;
    }

    private function smtpMessage($header, $body) {
        fputs($this->sock, $header . "\r\n" . $body);
        $this->smtpDebug(
            "> " . str_replace(
                "\r\n",
                "\n" . "> ",
                $header . "\n> " . $body . "\n> "
            )
        );
        return true;
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
            return false;
        }
        return true;
    }

    private function smtpPutCMD($cmd, $arg = "") {
        if ($arg != "") {
            if ($cmd == "") {
                $cmd = $arg;
            }
            else {
                $cmd = $cmd . " " . $arg;
            }
        }
        fputs($this->sock, $cmd . "\r\n");
        $this->smtpDebug("> " . $cmd . "\n");
        return $this->smtpOK();
    }

    private function smtpError($string) {
        $this->logWrite("Error: Error occurred while " . $string . ".\n");
        return false;
    }

    private function logWrite($message) {
        $this->smtpDebug($message);
        if ($this->logFile == "") {
            return true;
        }
        $message = date("M d H:i:s ") . get_current_user() . "[" . getmypid() . "]: " . $message;
        if (!@file_exists($this->logFile) || !($fp = @fopen($this->logFile, "a"))) {
            $this->smtpDebug(
                "Warning: Cannot open log file \"" . $this->logFile . "\"\n"
            );
            return false;
        }
        flock($fp, LOCK_EX);
        fputs($fp, $message);
        fclose($fp);
        return true;
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

    private function getAttachType($imageTag) { //
        $filedata = array();
        $imgFileCon = fopen($imageTag, "r");
        $imageData = null;
        while ($temBuffer = AddSlashes(fread($imgFileCon, filesize($imageTag))))
            $imageData .= $temBuffer;
        fclose($imgFileCon);
        $filedata['context'] = $imageData;
        $filedata['filename'] = basename($imageTag);
        $extension = substr(
            $imageTag,
            strrpos($imageTag, "."),
            strlen($imageTag) - strrpos($imageTag, ".")
        );
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