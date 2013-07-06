<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Frameworkphp平台安装向导</title>
<link href="__STATICS__/css/css.css" rel="stylesheet" type="text/css" />
</head>
<body>
<div id="main">
  <div class="top">
    <div class="logo"></div>
    <div id="tool">联系我们&nbsp;&nbsp;&nbsp;&nbsp;<a href="http://www.Frameworkphp.org"> 官方网站</a></div>
  </div>
  <div class="central">
    <div id="left">
        <ul>
            <li>
                <h1>1</h1>
                <div class="left_title">
                    <h2>准备安装</h2>
                    <p>欢迎您使用Frameworkphp平台！</p>
                </div>
            </li>
            <li>
                <h1>2</h1>
                <div class="left_title">
                    <h2>阅读协议</h2>
                    <p>请认真阅读软件使用协议，以免您的利益受到损害！</p>
                </div>
            </li>
            <li>
                <h1 class="install">3</h1>
                <div class="left_title">
                    <h2 class="install">环境检测</h2>
                    <p class="install">检测软件安装环境是否适合Frameworkphp平台！</p>
                </div>
            </li>
            <li>
                <h1>4</h1>
                <div class="left_title">
                    <h2>基本设置</h2>
                    <p>请设置平台的基本信息！</p>
                </div>
            </li>
            <li>
                <h1>5</h1>
                <div class="left_title">
                    <h2>开始安装</h2>
                    <p>开始愉快的Frameworkphp平台安装之旅吧！</p>
                </div>
            </li>
        </ul>
    </div>
    <div class="right">
      <div class="right_title">安装环境检测</div>
      <div style="text-align:left; line-height:25px; margin-top:20px; font-size:12px;table-layout:fixed;">
        <p><a href="#">系统所需函数检测</a></p>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="120">图像函数库支持：</td>
    <td width="50">{env_test::func tester=imageline}</td>
    <td>此函数影响图片自动缩小和图片水印功能</td>
  </tr>
  <tr>
    <td>CURL函数支持：</td>
    <td>{env_test::func tester=curl_init}</td>
    <td>此函数可能会影响到远程载入等功能</td>
  </tr>
  <tr>
    <td>socket函数支持：</td>
    <td>{env_test::func tester=fsockopen}</td>
    <td>此函数可能会影响到远程载入等功能</td>
  </tr>
  <tr>
    <td colspan="3">&nbsp;</td>
    </tr>
</table>

        <p><a href="#">系统所需目录权限检测</a></p>
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="table-layout:fixed;">
    <!--env_test::dir-->
  <tr>
    <td width="100">{$apikey}</td>
    <td>{$apival}</td>
  </tr>
    <!--/-->
      <td colspan="2">注意：如果目录没有相应的写入权限可能会造成程序无法自动升级</td>
    </tr>
</table>
        <div class="agree"  align="center">
            <a href="{$next_url}"><input type="button" class="button" value="下一步" /></a>
        </div>
      </div>
    </div>
    <div style="clear:both"></div>
  </div>
</div>
<div class="foot">Copyright © 2013 Frameworkphp (www.Frameworkphp.org)</div>
</body>
</html>