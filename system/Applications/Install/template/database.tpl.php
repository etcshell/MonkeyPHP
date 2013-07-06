<?php
class GetChar{
function getCode ($length = 5, $mode = 0){
    switch ($mode) {
    default:
    $str = '1234567890abcdefghijklmnopqrstuvwxtz';
    break;
    }

    $result = '';
    $l = strlen($str)-1;
    $num=0;

    for($i = 0;$i < $length;$i ++){
        $num = rand(0, $l);
        $a=$str[$num];
        $result =$result.$a;
    }
return $result.'_';
}
}

$code = new GetChar;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>IDreamphp平台安装向导</title>
<link href="__STATICS__/css/css.css" rel="stylesheet" type="text/css" />
</head>

<body>
<div id="main">
  <div class="top">
    <div class="logo"></div>
    <div id="tool">联系我们&nbsp;&nbsp;&nbsp;&nbsp;<a href="http://www.IDreamphp.org"> 官方网站</a></div>
  </div>
  <div class="central">
    <div id="left">
        <ul>
            <li>
                <h1>1</h1>
                <div class="left_title">
                    <h2>准备安装</h2>
                    <p>欢迎您使用IDreamPHP平台！</p>
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
                <h1>3</h1>
                <div class="left_title">
                    <h2>环境检测</h2>
                    <p>检测软件安装环境是否适合Frameworkphp平台！</p>
                </div>
            </li>
            <li>
                <h1 class="install">4</h1>
                <div class="left_title">
                    <h2 class="install">基本设置</h2>
                    <p class="install">请设置平台的基本信息！</p>
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
      <div class="right_title">网站基本设置</div>
      <div style="text-align:left; line-height:25px; margin-top:20px; font-size:14px;">
        <form action="../../../../L/install/install.php" method="post" name="form" id="form">
          <table class="data_set">
            <tr>
              <th colspan="3"></th>
            </tr>
            <tr>
              <td width="100">数据库类型</td>
              <td width="37%"><input type="text" class="setup_input" name="DB_TYPE" value="mysql" /></td>
              <td width="49%" class="lightcolor">数据库类型，支持mysql与mysqli，后期支持转换 </td>
            </tr>
            <tr>
            <tr>
              <th colspan="3"></th>
            </tr>
            
              <td width="14%">数据库地址</td>
              <td width="37%"><input type="text" class="setup_input" name="DB_HOST" value="localhost" /></td>
              <td width="49%" class="lightcolor">数据库服务器地址，一般为localhost </td>
            </tr>
            <tr>
              <th colspan="3"></th>
            </tr>
            <tr>
            <tr>
              <th colspan="3"></th>
            </tr>
            
              <td width="14%">数据库端口</td>
              <td width="37%"><input type="text" class="setup_input" name="DB_PORT" value="3306" /></td>
              <td width="49%" class="lightcolor">数据库端口,一般为3306 </td>
            </tr>
            <tr>
              <th colspan="3"></th>
            </tr>
            <tr>
              <td>数据库名称</td>
              <td><input type="text" class="setup_input" name="DB_NAME" value="dlcms" /></td>
              <td class="lightcolor">请先建立数据库</td>
            </tr>
            <tr>
              <th colspan="3"></th>
            </tr>
            <tr>
              <td>数据库用户名</td>
              <td><input type="text" class="setup_input" name="DB_USER" value="root" /></td>
              <td class="lightcolor">您的MySQL 用户名 </td>
            </tr>
            <tr>
              <th colspan="3"></th>
            </tr>
            <tr>
              <td>数据库密码</td>
              <td><input type="password" class="setup_input" name="DB_PWD" value="" /></td>
              <td class="lightcolor">您的MySQL密码</td>
            </tr>
            <tr>
              <th colspan="3"></th>
            </tr>
            <tr>
              <td>数据库前缀</td>
              <td><input name="DB_PREFIX" type="text" class="setup_input" id="DB_PREFIX" value="<?php echo $code->getCode(); ?>" /></td>
              <td class="lightcolor">多个站点请勿重叠</td>
            </tr>
            <tr>
              <th colspan="3"></th>
            </tr>
          </table>
          <div class="agree"  align="center">
            <input hidefocus="true" type="submit" style="margin-top:20px;" class="button" value="马上开始安装！" />
          </div>
        </form>
      </div>
    </div>
    <div style="clear:both"></div>
  </div>
</div>
<div class="foot">Copyright © 2013 Frameworkphp (www.Frameworkphp.org)</div>
</body>
</html>