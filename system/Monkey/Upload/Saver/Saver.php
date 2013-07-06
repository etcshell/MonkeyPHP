<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Administrator
 * Date: 13-5-2
 * Time: 下午1:31
 * To change this template use File | Settings | File Templates.
 */

namespace Monkey\Upload\Saver;


class Saver
{
    private
        $fileSystemCharset,
        $defaultDir,
        $files,
        $status=array()
    ;

    /**
     * @param array $files 安全转换后的$_FILES，默认为原生的$_FILES
     * @param $config 配置
     */
    public function __construct($files, $config)
    {
        $charset=$config['file_system_charset'];
        !$charset and $charset=DIRECTORY_SEPARATOR=='\\' ? 'GB2312' : 'utf-8';//如果是Windows，则默认为中文Windows了
        $this->fileSystemCharset= $charset;
        $this->defaultDir = $config['dir'] ? $config['dir'] : dirname(FRONT_FILE).'/upload';
        $this->files = $files ? $files : $_FILES;
    }

    /**
     * 保存上传文件
     * 例如
     * 1.单文件：
     * $saver->save('myfile', null, null, 'doc|xls|ppt|zip|rar', '2mb' )
     * 2.多文件（两个）：
     * $saver->save('myfiles[]',dirname(FRONT_FILE).'/upload', null, array('jpg|bmp|png','zip|rar') )
     * $saver->save('myfiles', null, null, array('jpg|bmp|png','zip|rar') ) //'[]'可以省略
     *
     * @param string $name 表单中input框的name值
     * @param string|array $saveDir 单文件为字符串；多文件非数组参数表示每个上传文件都用相同的设置，多文件数组参数必须是自然数键名，且个数等于上传文件数目
     * @param string|array $saveName  同上
     * @param string|array $filterType 同上
     * @param int|array $maxSize 类似上
     * @param bool|array $overwrite 类似上
     * @return array 存储结果
     * 返回数组的结构：
     * array(
     *      0=>array(
     *          'status'=>成功为True，失败为False,
     *          'info'=>成功时为文件保存路径（含文件名及扩展名）,失败时为错误信息
     *      ),
     *      ……
     * )
     */
    public function save($name, $saveDir=null, $saveName=null, $filterType=null, $maxSize=0, $overwrite=false)
    {
        $this->status=array();
        $temp=strstr($name, '[', true);
        $temp!==false and $name=$temp;
        !$saveDir and $saveDir= $this->defaultDir;
        $files=$this->files;

        if(empty($files) || empty($files[$name]))
        {
            return $this->_notice(0, '没有文件上传成功!');
        }

        $file=$files[$name];
        if(is_string($file['name']))
        {
            $this->_upload(
                0,
                $file['name'],
                $file['type'],
                $file['size'],
                $file['tmp_name'],
                $file['error'],
                is_array($saveDir)?$saveDir[0]:$saveDir,
                is_array($saveName)?$saveName[0]:$saveName,
                is_array($filterType)?$filterType[0]:$filterType,
                is_array($maxSize)?$maxSize[0]:$maxSize,
                is_array($overwrite)?$overwrite[0]:$overwrite
            );
        }
        else
        {
            foreach ($file['name'] as $i => $file_name)
            {
                $this->_upload(
                    $i,
                    $file_name,
                    $file['type'][$i],
                    $file['size'][$i],
                    $file['tmp_name'][$i],
                    $file['error'][$i],
                    is_array($saveDir)?$saveDir[$i]:$saveDir,
                    is_array($saveName)?$saveName[$i]:$saveName,
                    is_array($filterType)?$filterType[$i]:$filterType,
                    is_array($maxSize)?$maxSize[$i]:$maxSize,
                    is_array($overwrite)?$overwrite[$i]:$overwrite
                );
            }
        }
        return $this->status;
    }

    private function _upload(
        $index,
        $upName,
        $upType,//没有用，是因为浏览器给出的mime_types不可靠，经常有歧义。
        $upSize,
        $upTmp,
        $upError,
        $saveDir,
        $saveName,
        $filterType=null,
        $maxSize=0,
        $overwrite=FALSE)
    {
        dir_check($saveDir);

        if(!is_uploaded_file($upTmp))
        {
            return $this->_notice(0, '这不是一个上传的文件!');
        }

        !is_int($maxSize) and $maxSize=size_to_bit($maxSize);
        if($maxSize>0 && $upSize>$maxSize)
        {
            return $this->_notice(0, '文件超过指定的文件大小！');
        }

        switch ($upError)
        {
            case 0:break;
            case 1: $error='文件超过服务器的约定大小';break;
            case 2: $error='文件超过指定的文件大小！';break;
            case 3: $error='文件只上传了部分！';break;
            case 4: $error='文件上传失败！';break;
            default:$error='未知错误！';
        }

        if(!empty($error))
        {
            return $this->_notice(0, $error);
        }

        $pathinfo=pathinfo($upName);
        $extension = $pathinfo['extension'];
        empty($saveName) and $saveName= substr($upName,0,0-1-strlen($extension));
        $saveName= strtr($saveName,'.','_');//消除因为apache配置失误造成的上传漏洞。
        $index and $saveName.='_'.$index;
        $saveName.='.'.strtolower($extension);
        $this->fileSystemCharset=='GB2312' and $saveName=iconv("UTF-8","GB2312//IGNORE",$saveName);
        $saveName=$saveDir.'/'.$saveName;
        if(!is_null($filterType))
        {
            $types=explode('|',strtolower($filterType));
            if(!in_array($extension, $types))
            {
                return $this->_notice(0, '上传文件类型不在限定的文件类型之内！');
            }

            $up_real_types=$this->_getTypeOfReal($upTmp);
            $up_real_types=explode('|',$up_real_types);
            if(!in_array($extension, $up_real_types))
            {
                return $this->_notice(0, '上传文件文件类型与其真实类型不一致，可能是修改了扩展名！');
            }
        }
        if(file_exists($saveName))
        {
            if(!$overwrite)
                return $this->_notice(0, '要保存的文件名已存在，且不可覆盖！');
            else
                unlink($saveName);
        }
        $result=move_uploaded_file($upTmp, $saveName);
        return $this->_notice(
            $result,
            $result?$saveName:'未知错误，上传失败！'
        );
    }

    private function _notice($status,$msg){
        return $this->status[]=array('status'=>$status?1:0,'info'=>$msg);
    }

    /**
     * @static
     * 获取文件的真实类型
     * @param string $file_name 文件名
     * @return string  失败为''（空字符串）
     */
    private function _getTypeOfReal($file_name){
        static $file_code=array(
            '-48-49'=>'doc|xls',
            '7790'=>'exe',
            '7784'=>'midi',
            '8075'=>'zip',
            '8297'=>'rar',
            '7173'=>'gif',
            '255216'=>'jpg',
            '6677'=>'bmp',
            '13780'=>'png',
            '104116'=>'txt',
        );
        if(!file_exists($file_name)) return '';
        $file=  fopen($file_name, 'rb');
        $bin = fread($file,2);
        $strInfo = @unpack("c2chars", $bin);
        $typeCode = $strInfo['chars1'].''.$strInfo['chars2'];
        if($typeCode == '-1-40' ) $typeCode='255216';
        if($typeCode == '-11980' ) $typeCode='13780';
        return (string)$file_code[$typeCode];
    }

}