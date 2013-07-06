<?php
/**
 * Created by JetBrains PhpStorm.
 * User: hyiyou
 * Date: 13-4-29
 * Time: 上午11:34
 * To change this template use File | Settings | File Templates.
 */

namespace Monkey\Upload;


use Monkey\_Interface\Component;

class Upload implements Component
{
    /**
     * @var Saver\Saver
     */
    private $oSaver;

    private
        $_files,
        $_options=array(),
        $config
    ;

    private function __construct()
    {
        $this->_files = $this->convertFileInformation($_FILES);
    }

    /**
     * 组件初始化注入
     * @param \Monkey\Monkey|null $monkey 依赖类
     * @param mixed|null $config 配置
     * @return mixed
     */
    public function _initialize($monkey = null, $config = null)
    {
        $this->config=$config;
    }

    /**
     * 获取上传实例
     * @return \Monkey\Upload\Upload
     */
    public static function _instance(){
        static $self;
        !$self and $self=new self();
        return $self;
    }

    /**
     * 设置要保存的表单中的文件字段
     * @param string $name 字段名
     * @return $this
     */
    public function setInputField($name)
    {
        $this->_options['field']=$name;
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
     * @param string|array $dir
     * @return $this
     */
    public function setSaveDirectory($dir)
    {
        $this->_options['saveDir']=$dir;
        return $this;
    }

    /**
     * 设置存储文件名
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
        $this->_options['saveName']=$name;
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
        $this->_options['filterType']=$type;
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
        $this->_options['maxSize']=$size;
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
        $this->_options['overwrite']=(bool)$overwrite;
        return $this;
    }

    /**
     * 保存上传的文件
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
    public function save()
    {
        $options=$this->_options;
        $this->_options=array();
        if(!$options['field'])
        {
            error_exit('接收上传文件必须提供表单中的上传字段名。',1024,__FILE__,__LINE__);
            return false;
        }

        if(!$this->oSaver)
        {
            $this->oSaver= new Saver\Saver($this->_files, $this->config);
        }

        return $this->oSaver->save(
                $options['field'],
                $options['saveDir'],
                $options['saveName'],
                $options['filterType'],
                $options['maxSize'],
                $options['overwrite']
            );
    }

    /**
     * 获取安全转换后的$_FILES
     * @return array
     */
    public function getSafeFilesInfo()
    {
        return $this->_files;
    }

    private function convertFileInformation($taintedFiles)
    {
        $pattern='#^(/[^/]+)?(/name|/type|/tmp_name|/error|/size)([^\s]*)( = [^\n]*)#m';
        return $this->pathsToArray(
            preg_replace($pattern, '$1$3$2$4', $this->arrayToPaths($taintedFiles))
        );
    }

    private function pathsToArray($str)
    {
        $array = array();
        $lines = explode('\n', trim($str));

        if (!empty($lines[0]))
        {
            foreach ($lines as $line)
            {
                list($path, $value) = explode(' = ', $line);

                $steps = explode('/', $path);
                array_shift($steps);

                $insertion =& $array;

                foreach ($steps as $step)
                {
                    if (!isset($insertion[$step]))
                    {
                        $insertion[$step] = array();
                    }
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

        foreach ($array as $key => $value)
        {
            $freshPrefix .= '/'.$key;

            if (is_array($value))
            {
                $str .= $this->arrayToPaths($value, $freshPrefix);
                $freshPrefix = $prefix;
            }
            else
            {
                $str .= $prefix.'/'.$key.' = '.$value.'\n';
            }
        }

        return $str;
    }

}