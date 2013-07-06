<?php
namespace Library;

/**
 * csv_exporter
 * @category   导出csv格式的EXCEL表格工具（大数据专用）
 * @package    扩展库
 * @author     HuangYi
 * @copyright  Copyright (c) 2012-4-1——至今
 * @license    New BSD License
 * @version    $Id: csv_exporter.class.php 版本号 2013-1-1  $
 *
 */
class ExporterCSV {
    private static $_file;
    private static $_limit=100;
    private static $_i=0;
    /**
     * 向浏览器输出cvs格式的Excel文件
     * @param string $file_name
     */
    public function __construct($file_name) {
        header('Content-Type: application/force-download');   
        header('Content-Type: application/octet-stream');   
        header('Content-Type: application/download');;   
        header("Content-Type: application/vnd.ms-excel; name=\"".$file_name.".csv\"");
        header("Content-Disposition: attachment; filename=\"" . $file_name . ".csv\"");
        header('Cache-Control: max-age=0');
        self::$_file = fopen('php://output', 'a');
    }
    public function addRow(&$row){
        if (self::$_i == self::$_limit)   $this->push();
        self::$_i++;
        fputcsv(self::$_file, self::iconv($row));
    }
    public function push(){
        ob_flush();
        flush();
        self::$_i = 0;
    }
    private static function iconv(&$data){
        if(is_array($data)) return array_map(__METHOD__ , $data);
        return $data=iconv('utf-8', 'gbk', $data);
    }
    public function __destruct() {
        self::$_i && $this->push();
    }
}