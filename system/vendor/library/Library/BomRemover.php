<?php
namespace Library;

/**
 * BomRemover
 * Bom去除类
 * @package Library
 */
final class BomRemover {
    private function __construct() {}
    /**
     * 扫描Bom
     * @param string $dir 目标目录
     * @param boolean $autoClear 是否清除扫描到的bom
     * @return array 存在bom的文件
     */
    public static function scan($dir,$autoClear=TRUE){
        $result=array();
        self::_scanDir($dir, $result, $autoClear);
        $count=1;
        !empty($result) and $result=str_replace($dir, '', $result, $count);
        return $result;
    }
    private static function _scanDir($dir,&$result,$clear=TRUE){
        $handler=opendir($dir);
        if(!$handler) return FALSE;
        while (($item = readdir($handler)) !== false) {
            if($item=='.' || $item=='..' )  continue;
            $new=$dir."/".$item;
            is_dir($new) && self::_scanDir ($new, $result, $clear);
            self::_checkBom($new, $result, $clear);
        }
        closedir($handler);
    }
    private static function _checkBom($file,&$result, $clear){
        $contents=file_get_contents($file);
        $bom=substr($contents, 0, 3);
        if (ord($bom[0])==239 && ord($bom[1])==187 && ord($bom[2])==191) {
            $result[]=$file;
            $clear && file_put_contents($file, substr($contents, 3));
        }
    }
}