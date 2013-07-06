<?php
class env_test
{
    public static function func($params){
        return self::_notice(function_exists($params['tester'])==1);
    }
    private static function _notice($yes=true){
        if($yes)return '<font color=green><b>√</b></font>';
        else return '<font color=red><b>×</b></font>';
    }
    public static function dir(){
        $dir['INDEX']=self::_notice(self::_check_dir(config()->dir_front)>11);
        $dir['SYSTEM']=self::_notice(self::_check_dir(SYSTEM)>11);
        return $dir;
    }
    private static function _check_dir($file_path){
        if (!file_exists($file_path))return false;/* 如果不存在，则不可读、不可写、不可改 */
        $mark = 0;
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN'){
            $test_file = $file_path . '/cf_test.txt'; /* 测试文件 */
            if (is_dir($file_path)){/* 如果是目录 */
                $dir = @opendir($file_path);/* 检查目录是否可读 */
                if($dir === false)return $mark; //如果目录打开失败，直接返回目录不可修改、不可写、不可读
                @readdir($dir) !== false and $mark ^= 1; //目录可读 001，目录不可读 000
                @closedir($dir);
                /* 检查目录是否可写 */
                $fp = @fopen($test_file, 'wb');
                if($fp === false)return $mark; //如果目录中的文件创建失败，返回不可写。
                @fwrite($fp, 'directory access testing.')!==false and $mark ^= 2; //目录可写可读011，目录可写不可读 010
                @fclose($fp);
                @unlink($test_file);
                /* 检查目录是否可修改 */
                $fp = @fopen($test_file, 'ab+');
                if ($fp === false) return $mark;
                @fwrite($fp, "modify test.\r\n")!==false and $mark ^= 4;
                @fclose($fp);
                /* 检查目录下是否有执行rename()函数的权限 */
                @rename($test_file, $test_file) !== false and $mark ^= 8;
                @unlink($test_file);
            }
            elseif (is_file($file_path)){/* 如果是文件 */
                $fp = @fopen($file_path, 'rb');/* 以读方式打开 */
                $fp and $mark ^= 1; //可读 001
                @fclose($fp);
                /* 试着修改文件 */
                $fp = @fopen($file_path, 'ab+');
                ($fp && @fwrite($fp, '')!==false) and $mark ^= 6; //可修改可写可读 111，不可修改可写可读011...
                @fclose($fp);
                @rename($test_file, $test_file)!==false and $mark ^= 8;/* 检查目录下是否有执行rename()函数的权限 */
            }
        }
        else{
            @is_readable($file_path) and $mark ^= 1;
            @is_writable($file_path) and $mark ^= 14;
        }
        return $mark;
    }
}