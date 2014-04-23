<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="refresh" content="5.0;url='<?php echo $errorInfo['goto_index']; ?>';" />
<title>404-访问出错啦!</title>
<style type="text/css">
body{color:#333; font:12px Verdana, Geneva, sans-serif; padding:0px; margin:0px;}
.m_box{margin:10px auto; width:900px;}
.m_line{border-bottom:1px dashed #ddd; padding:12px; line-height:24px; background:#fff}
.m_title{padding:12px; font-size:20px; line-height:40px; color:#09F; border-bottom:2px solid #59F; font-weight:bold}
.m_line a{color:#09F; padding:0 10px;}
</style>
</head>
<body>
<div class="m_box">
        <div class="m_title">
            404 Error: Not Found !
        </div>
        <div class="m_line">
            <b>你访问的网址不存在，请修正后再访问！</b>
        </div>
        <div class="m_line">
            您也可以选择 
            <a href="javascript:history.back()" title="返回">返回</a>
            <a href="<?php echo $errorInfo['goto_index']; ?>" title="回到首页">回到首页</a>
        </div>
        <div class="m_line">
            5&nbsp;秒后将自动回到首页。
      </div>
    </div>
</body>
</html>