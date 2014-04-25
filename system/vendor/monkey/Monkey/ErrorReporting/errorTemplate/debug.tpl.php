<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>系统错误提示!</title>
<style>
body{color:#333; font:12px Verdana, Geneva, sans-serif; padding:0px; margin:0px;}
.m_box{margin:10px auto; width:900px;}
.m_line{border-bottom:1px dashed #ddd; padding:12px; line-height:24px; background:#fff}
.m_title{padding:12px; font-size:20px; line-height:40px; color:#09F; border-bottom:2px solid #59F; font-weight:bold}
.m_back{font-size:14px; padding:20px; background:#f6f6f6}
.m_back a{color:#09F; padding:0 10px;}
</style>
</head>
<body>
<div class="m_box">
<div class="m_title"><?php echo $errorInfo['title'] ?>：</div>
    <div class="m_line">
        错误代号：<b><?php echo $errorInfo['code'] ?></b>
    </div>
    <div class="m_line">
        访问路径：<b><?php echo $errorInfo['path'] ?></b>
    </div>
    <div class="m_line">
        错误发生在IP：<b><?php echo $errorInfo['ip'] ?></b>
    </div>
    <div class="m_line">
        错误消息：<b><?php echo $errorInfo['message'] ?></b>
    </div>
    <div class="m_line">
        出错文件：<b><?php echo $errorInfo['file'] ?></b>
    </div>
    <div class="m_line">
        错误行号：<b><?php echo $errorInfo['line'] ?></b>
    </div>
    <div class="m_line">
        错误行的源码：<b><pre><?php echo $errorInfo['source'] ?></pre></b>
    </div>
    <div class="m_line">
        调用信息如下：<pre><?php echo $errorInfo['backtrace'] ?></pre>
    </div>

    <div class="m_back">
        您可以选择跳转到<a href="<?php echo $errorInfo['goto_index'] ?>">首页</a><a href="<?php $_SERVER['PHP_SELF'] ?>" title="重试">重试</a><a href="javascript:history.back()" title="返回">返回</a>
    </div>
</div>
</body>
</html>