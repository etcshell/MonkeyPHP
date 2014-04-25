<?php
namespace Library;

/**
 * 上传组件 Upload
 * @package Monkey\Upload
 */

class Upload
{
    private
        $files,
        $options=array(),
        $fileSystemCharset,
        $defaultOptions,
        $status=array()
    ;

    /**
     * @param string $fileSystemCharset 文件系统字符集，不设置时，Windows默认为中文GB2312，Linux为utf-8
     * @param string $defaultDir 默认保存目录，默认为前端目录下的/upload子文件夹里： dir_format(dirname(FRONT_FILE)).'/upload';
     * @param string|array $filterType  默认效验文件类型（扩展名）如'txt'、'doc|xls'、'zip|rar'等，不设置将不检测
     * @param string|array $maxSize    效验文件大小限,如3mb、8MB等
     * @param bool|array $overwrite     是否覆盖同名文件
     */
    public function __construct($fileSystemCharset=null, $defaultDir=null, $filterType=null,$maxSize=-1,$overwrite=FALSE)
    {
        $this->files = $this->convertFileInformation($_FILES);
        //设置的文件系统字符集，如果是Windows，则默认为中文Windows了，Linux为utf-8
        $this->fileSystemCharset= $fileSystemCharset? $fileSystemCharset : DIRECTORY_SEPARATOR=='\\' ? 'GB2312' : 'utf-8';
        $this->defaultOptions=array(
            'saveDir'       => $defaultDir ? $defaultDir : dir_format(dirname(FRONT_FILE)).'/upload',
            'filterType'    => (string)$filterType,
            'maxSize'       => ctype_digit($maxSize) ? $maxSize : -1,
            'overwrite'     => (bool)$overwrite
        );
        $this->options=$this->defaultOptions;
    }

    /**
     * 获取安全转换后的$_FILES
     * @return array
     */
    public function getSafeFilesInfo()
    {
        return $this->files;
    }

    /**
     * 设置要保存的表单中的文件字段
     * 上传文件的字段唯一时，虽然为粗心的人自动做了设置，但是浏览器可以伪造上传字段，所以不想被攻击就必须手动设置
     * @param string $name 字段名
     * @return $this
     */
    public function setInputField($name)
    {
        $this->options['field']=$name;
        return $this;
    }

    /**
     * 设置存储目录
     * 不使用本方法将自动调用配置中的上传目录进行统一设置
     * 单文件时为字符串，
     * 多文件可以是字符串（表示每个上传文件都用相同的设置），
     * 多文件也可以是数组，且数组键名必须是0起头的自然数键名，并且元素个数等于上传文件数目。
     * $upload->setSaveDirectory( INDEX.'/upload');
     * $upload->setSaveDirectory( array(INDEX.'/upload/image', INDEX.'/upload/office') );
     *
     * @param string|array $dir 默认为前端目录下的/upload子文件夹里： dir_format(dirname(FRONT_FILE)).'/upload';
     * @return $this
     */
    public function setSaveDirectory($dir)
    {
        $this->options['saveDir']=$dir;
        return $this;
    }

    /**
     * 设置存储文件名，不设置就会使用源文件的名字，如有重名则会自动加上数字编码的后缀
     * 单文件时为字符串，
     * 多文件可以是字符串（表示每个上传文件都用相同的设置），
     * 多文件也可以是数组，且数组键名必须是0起头的自然数键名，并且元素个数等于上传文件数目。
     * $upload->setSaveName('test');
     * $upload->setSaveName( array('test1', 'test2') );
     *
     * @param string|array $name 文件名，不含路径和扩展名
     * @return $this
     */
    public function setSaveName($name)
    {
        $this->options['saveName']=$name;
        return $this;
    }

    /**
     * 设置过滤文件类型
     * 单文件时为字符串，
     * 多文件可以是字符串（表示每个上传文件都用相同的设置），
     * 多文件也可以是数组，且数组键名必须是0起头的自然数键名，并且元素个数等于上传文件数目。
     * $upload->setFilterType('jpg|jpge|png|bmp');
     * $upload->setFilterType( array('jpg|jpge|png|bmp', 'doc|xls|ppt|zip|rar|tag') );
     *
     * @param string|array $type
     * @return $this
     */
    public function setFilterType($type)
    {
        $this->options['filterType']=$type;
        return $this;
    }

    /**
     * 设置上传文件的最大字节数
     * 单文件时为字符串，
     * 多文件可以是字符串（表示每个上传文件都用相同的设置），
     * 多文件也可以是数组，且数组键名必须是0起头的自然数键名，并且元素个数等于上传文件数目。
     * 例如：
     * $upload->setMaxSize('2mb');
     * $upload->setMaxSize( array('500kb', 0) );
     *
     * @param int|string|array $size 字节字符串，0表示不限制
     * @return $this
     */
    public function setMaxSize($size=0)
    {
        $this->options['maxSize']=$size;
        return $this;
    }

    /**
     * 设置保存上传文件是否覆盖
     * 单文件时为字符串，
     * 多文件可以是字符串（表示每个上传文件都用相同的设置），
     * 多文件也可以是数组，且数组键名必须是0起头的自然数键名，并且元素个数等于上传文件数目。
     * 例如：
     * $upload->setMaxSize( false );
     * $upload->setMaxSize( array(true, false) );
     *
     * @param bool|array $overwrite 默认全部不覆盖，因此一般不必调用
     * @return $this
     */
    public function setOverwrite($overwrite=false)
    {
        $this->options['overwrite']=(bool)$overwrite;
        return $this;
    }

    /**
     * 上传文件保存
     * @param bool $isAloneFile 是否为单文件上传，默认为是true
     * @param array $options 参数结构如下，意义见上面单独设置参数的方法
     * array(
     *      'field'         =>...,  //见setInputField 必须有，这里设置或上面单独设置都视为有
    'saveDir'       =>...,  //见setSaveDirectory
    'saveName'      =>...,  //见setSaveName
    'filterType'    =>...,  //见setFilterType
    'maxSize'       =>...,  //见setMaxSize
    'overwrite'     =>...   //见setOverwrite
     * )
     * @return array|bool
     * 返回数组的结构：
     * array(
     *      0=>array(
     *          'status'=>成功为True，失败为False,
     *          'info'=>成功时为文件保存路径（含文件名及扩展名）,失败时为错误信息
     *      ),
     *      ......
     * )
     * @param bool $isAloneFile
     * @param array $options
     * @return array
     * @throws \Exception
     */
    public function save($isAloneFile=true, array $options=array())
    {
        $this->status=array();
        $o = $options + $this->options;
        $this->options=$this->defaultOptions;
        if(!$o['field']){
            if(count($this->files)==1){
                $o['field']=key($this->files);
            }
            else{
                throw new \Exception('接收上传文件必须提供表单中的上传字段名。',1024);
                //return false;
            }
        }
        strpos($o['field'], '[') and $o['field']=substr($o['field'], 0, -2);
        if(empty($this->files) || empty($this->files[$o['field']])) return $this->_notice(0, '文件没有上传成功!');
        $file=$this->files[$o['field']];
        if(is_string($file['name'])){
            $this->upload(0,$file,$o);
        }
        else if(!$isAloneFile){
            foreach ($file['name'] as $i => $file_name){
                $this->upload($i,$file,$o);
            }
        }
        else if(count($file['name'])==1){
            $this->upload(0,$file,$o);
        }
        else{
            throw new \Exception('浏览器上传文件多余一个,超出预期。',1024);
        }
        return $this->status;
    }

    private function upload($i, &$file, &$o)
    {
        $this->_upload(
            $i,
            is_array($file['name']) ? $file['name'][$i] : $file['name'],
            is_array($file['size']) ? $file['size'][$i] : $file['size'],
            is_array($file['tmp_name']) ? $file['tmp_name'][$i] : $file['tmp_name'],
            is_array($file['error']) ? $file['error'][$i] : $file['error'],
            is_array($o['saveDir']) ? $o['saveDir'][$i] : $o['saveDir'],
            is_array($o['saveName']) ? $o['saveName'][$i] : $o['saveName'],
            is_array($o['filterType']) ? $o['filterType'][$i] : $o['filterType'],
            is_array($o['maxSize']) ? $o['maxSize'][$i] : $o['maxSize'],
            is_array($o['overwrite']) ? $o['overwrite'][$i] : $o['overwrite']
        );
    }

    private function _upload($index, $upName, $upSize, $upTmp, $upError,
                             $saveDir, $saveName, $filterType=null, $maxSize=0, $overwrite=FALSE)
    {
        dir_check($saveDir);
        if(!is_uploaded_file($upTmp)){
            return $this->_notice(0, '这不是一个上传的文件!');
        }
        !is_int($maxSize) and $maxSize=size_to_bit($maxSize);
        if($maxSize>0 && $upSize>$maxSize){
            return $this->_notice(0, '文件超过指定的文件大小！');
        }
        switch ($upError){
            case 0:break;
            case 1: $error='文件超过服务器的约定大小';break;
            case 2: $error='文件超过指定的文件大小！';break;
            case 3: $error='文件只上传了部分！';break;
            case 4: $error='文件上传失败！';break;
            default:$error='未知错误！';
        }
        if(!empty($error)){
            return $this->_notice(0, $error);
        }
        $pathInfo=pathinfo($upName);
        $extension = $pathInfo['extension'];
        empty($saveName) and $saveName= substr($upName,0,0-1-strlen($extension));
        $saveName= strtr($saveName,'.','_');//消除因为apache配置失误造成的上传漏洞。
        $index and $saveName.='_'.$index;
        $saveName.='.'.strtolower($extension);
        $this->fileSystemCharset=='GB2312' and $saveName=iconv("UTF-8","GB2312//IGNORE",$saveName);
        $saveName=$saveDir.'/'.$saveName;
        if(!is_null($filterType)){
            $types=explode('|',strtolower($filterType));
            if(!in_array($extension, $types)) {
                return $this->_notice(0, '上传文件类型不在限定的文件类型之内！');
            }
            $up_real_types=$this->_getTypeOfReal($upTmp);
            $up_real_types=explode('|',$up_real_types);
            if(!in_array($extension, $up_real_types)){
                return $this->_notice(0, '上传文件文件类型与其真实类型不一致，可能是修改了扩展名！');
            }
        }
        if(file_exists($saveName)){
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
     * 过滤上传文件信息
     * @param $taintedFiles
     * @return array
     */
    private function convertFileInformation($taintedFiles)
    {
        $pattern='/^(/[^/]+)?(/name|/type|/tmp_name|/error|/size)([^\s]*)( = [^\n]*)/m';
        return $this->pathsToArray(
            preg_replace($pattern, '$1$3$2$4', $this->arrayToPaths($taintedFiles))
        );
    }

    private function pathsToArray($str)
    {
        $array = array();
        $lines = explode("\n", trim($str));
        if (!empty($lines[0])){
            foreach ($lines as $line){
                list($path, $value) = explode(' = ', $line);
                $steps = explode('/', $path);
                array_shift($steps);
                $insertion =& $array;
                foreach ($steps as $step){
                    !isset($insertion[$step]) and $insertion[$step] = array();
                    $insertion =& $insertion[$step];
                }
                $insertion = ctype_digit($value) ? (int) $value : $value;
            }
        }
        return $array;
    }

    private function arrayToPaths($array = array(), $prefix = '')
    {
        $str = '';
        $freshPrefix = $prefix;
        foreach ($array as $key => $value){
            $freshPrefix .= '/'.$key;
            if (is_array($value)){
                $str .= $this->arrayToPaths($value, $freshPrefix);
                $freshPrefix = $prefix;
            }
            else{
                $str .= $prefix.'/'.$key.' = '.$value."\n";
            }
        }
        return $str;
    }

    /**
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