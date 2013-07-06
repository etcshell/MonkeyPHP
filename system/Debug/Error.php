<?php
/**
 * Created by JetBrains PhpStorm.
 * User: hyiyou
 * Date: 13-5-4
 * Time: 下午7:07
 * To change this template use File | Settings | File Templates.
 */


class Error
{
    private static $_errors=array();
    private function __construct(){}

    /**
     * 获取错误管理器实例
     * @return \Error
     */
    public static function _instance(){
        static $self;
        !$self and $self=new self();
        return $self;
    }

    /**
     * 判断是否存在错误
     * @return bool
     */
    public function has(){
        return !empty(self::$_errors);
    }

    /**
     * 添加错误信息
     * @param array $errorData 错误信息包
     */
    public function put(array $errorData)
    {
        self::$_errors[] = $errorData;
    }

    /**
     * 显示404错误
     */
    public function show404(){
        $this->_denyAccess('404', '_error，页面没有找到！');
    }

    /**
     * 显示403错误
     */
    public function show403(){
        $this->_denyAccess('403', '_denied，你无权访问！');
    }

    /**
     * 显示错误堆栈中的错误（仅仅显示最后一条）
     * @param bool $exit 是否在显示完毕后退出程序
     */
    public function show($exit=false){
        $error=&self::$_errors;
        $data=end($error);
        if(config()->sys_log_enable)
        {
            o_logger()->add(self::$_errors);
        }
        if(DEBUG)
        {//显示所有错误信息
            $this->_show($data,'debug');
        }
        else
        {//显示可公开的错误信息
            $_data['title']=$data['title'];
            $_data['code']=$data['code'];
            $_data['time']=$data['time'];
            $this->_show($_data,'public');
        }
        if($exit) exit();
    }

    private function _show($error,$tpl)
    {
        if(o_request()->isAjax())
        {
            ob_clean();//清除之前输出的内容
            echo json_encode(notice(false,$error));
        }
        elseif(isset($_SERVER['REMOTE_ADDR']))
        {
            $error['goto_index']= o_request()->getRelativeUrlRoot().'/'.basename(FRONT_FILE);
            //载入显示模板
            require  __DIR__. '/tpl/'.$tpl.'.tpl.php';
        }
        else
        {
            echo PHP_EOL,'程序运行出错：';
            foreach ($error as $key=>$value) echo PHP_EOL,$key,': ',$value;
            echo PHP_EOL,'错误信息结束。';
        }
    }

    private function _denyAccess($code,$msg)
    {
        if(o_request()->isAjax())
        {
            exit(json_encode(notice(false,$code.$msg)));
        }
        else
        {
            include(__DIR__. '/tpl/'.$code.'.tpl.php');//载入404错误模板
            exit();
        }
    }
}