<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML xmlns="http://www.w3.org/1999/xhtml"><HEAD>
    <TITLE>多文件上传示例</TITLE>
    <META content="text/html; charset=utf-8" http-equiv=Content-Type>
</HEAD>
<body>
<div class="login_main">
    <div class="login_box">
        <div class="login_do" id="tips" style="display:none"> </div>
        <div style="padding:15px 20px;">
            <form action="<?php echo $actionUrl; ?>" method="post" enctype="MULTIPART/FORM-DATA">
                <table border="0" cellspacing="0" cellpadding="0" width="100%">
                    <thead><tr><td align="center">支持同时上传到不能路径，上传不同类型的文件</td></tr></thead>
                    <tbody>
                    <tr>
                        image,zip上传
                    </tr>
                    <tr>
                        <th>imagepath:</th>
                        <td><input name="uppath[]"  type="file" /></td>
                    </tr>
                    <tr>
                        <th>zippath:</th>
                        <td><input name="uppath[]"  type="file" /></td>
                    </tr>
                    <tr>
                        <th>&nbsp;</th>
                        <td>
                            <input name="submit" type="submit" value="导 入" style="margin-right:10px;height:26px; width:50px;">
                        </td>
                    </tr>
                    </tbody>
                </table>
            </form>
        </div>
    </div>
</div>
</body>