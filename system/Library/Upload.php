<?php
namespace Library;

/**
 * upload
 * @category   上传工具
 * @package    核心扩展包
 * @author     HuangYi
 * @copyright  Copyright (c) 2012-4-1——至今
 * @license    New BSD License
 * @version    $Id: upload.class.php 版本号 2013-1-1 $
 *
 */
class Upload
{
    private static $_status=array();
    private static $_osCharset;
    /**
     * 上传文件
     * @param string $fieldNameOfFile 上传文件的信息，input表单的name值
     * @param string|array $saveDir    保存文件的目录
     * @param string|array $saveName   保存文件的名称,——不含——扩展名(多文件上传时，系统将自动追加文件序号：空、_1、_2……)
     * @param string|array $checkType  效验文件类型（扩展名）如'txt'、'doc|xls'、'zip|rar'等
     * @param string|array $maxSize    效验文件大小限,如3mb、8MB等
     * @param bool|array $overwrite     是否覆盖同名文件
     * @return array 存储结果
     * 返回数组的结构：
     * array(
     *      0=>array(
     *          'status'=>成功为True，失败为False,
     *          'info'=>成功时为文件保存路径（含文件名及扩展名）,失败时为错误信息
     *      ),
     *      ……
     * )
     * 注意：
     *     当上传多个文件时，字段名必须相同，
     *     其它参数可以是字符串（表示每个上传文件都用相同的设置）
     *     其它参数也可以是数组（表示每个上传文件用不同的设置），但是数组的元素必须是0起头的自然数键名，并且元素个数等于上传文件数目。
     */
    public static function storeFile($fieldNameOfFile,$saveDir=null,$saveName=null,$checkType=null,$maxSize=-1,$overwrite=FALSE){
        is_null($saveDir) and $saveDir= config()->dir_upload;
        self::$_osCharset= DIRECTORY_SEPARATOR=='\\'?'GB2312':'utf-8';
        self::$_status=array();
        if(empty($_FILES) || empty($_FILES[$fieldNameOfFile])) return self::_notice(0, '没有文件上传成功!');
        $file=$_FILES[$fieldNameOfFile];
        if(is_string($file['name'])){
            self::_upload(0,$file['name'], $file['type'],$file['size'],$file['tmp_name'],$file['error'],
                is_array($saveDir)?$saveDir[0]:$saveDir,
                is_array($saveName)?$saveName[0]:$saveName,
                is_array($checkType)?$checkType[0]:$checkType,
                is_array($maxSize)?$maxSize[0]:$maxSize,
                is_array($overwrite)?$overwrite[0]:$overwrite);
        }  else {
            foreach ($file['name'] as $i => $file_name) {
                self::_upload($i,$file_name, $file['type'][$i],$file['size'][$i],$file['tmp_name'][$i],$file['error'][$i],
                    is_array($saveDir)?$saveDir[$i]:$saveDir,
                    is_array($saveName)?$saveName[$i]:$saveName,
                    is_array($checkType)?$checkType[$i]:$checkType,
                    is_array($maxSize)?$maxSize[$i]:$maxSize,
                    is_array($overwrite)?$overwrite[$i]:$overwrite);
            }
        }
        return self::$_status;
    }
    private static function _upload($index,
                                    $upName,$upType,$upSize,$upTmp,$upError,
                                    $saveDir,$saveName,$checkType=null,$maxSize=-1,$overwrite=FALSE){
        if(!is_uploaded_file($upTmp)) return self::_notice(0, '这不是一个上传的文件!');
        if($maxSize!=-1 ) $maxSize=size_to_bit($maxSize);
        if($maxSize==0 ) $maxSize=-1;
        if($maxSize!=-1 && $upSize>$maxSize) return self::_notice(0, '文件超过指定的文件大小！');
        switch ($upError) {
            case 0:break;
            case 1: $error='文件超过服务器的约定大小';break;
            case 2: $error='文件超过指定的文件大小！';break;
            case 3: $error='文件只上传了部分！';break;
            case 4: $error='文件上传失败！';break;
            default:$error='未知错误！';
        };
        if(!empty($error)) return self::_notice(0, $error);
        $origin_type = explode('.', $upName);
        $origin_type = strtolower(end($origin_type));
        if(empty($saveName))$saveName= $index?substr($upName,0,0-1-strlen($origin_type)).'_'.$index.'.'.$origin_type:$upName;
        else $saveName.=($index?'_'.$index:'').'.'.$origin_type;
        self::$_osCharset=='GB2312' and $saveName=iconv("UTF-8","GB2312//IGNORE",$saveName);
        dir_format($saveDir);
        $saveName=$saveDir.'/'.$saveName;
        if(!is_null($checkType)){
            $type_array=explode('|',strtolower($checkType));
            if(!in_array($origin_type, $type_array)) return self::_notice(0, '上传文件类型不在限定的文件类型之内！');
            $up_real_type_array=explode('|',self::getTypeOfReal($upTmp));
            if(!in_array($origin_type, $up_real_type_array)) return self::_notice(0, '上传文件文件类型与其真实类型不一致，可能是修改了扩展名！');
        }
        if(file_exists($saveName)){
            if(!$overwrite) return self::_notice(0, '要保存的文件名已存在，且不可覆盖！');
            unlink($saveName);
        }
        dir_check(dirname($saveName));
        move_uploaded_file($upTmp, $saveName);
        return self::_notice(1, $saveName);
    }
    private static function _notice($status,$msg){
        return self::$_status[]=array('status'=>$status?1:0,'info'=>$msg);
    }
    /**
     * @static
     * 获取文件的真实类型
     * @param string $file_name 文件名
     * @return string  失败为''（空字符串）
     */
    private static function getTypeOfReal($file_name){
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
