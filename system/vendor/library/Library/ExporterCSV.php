<?php
namespace Library;

/**
 * ExporterCSV
 * 导出csv表格类（可以在EXCEL表格工具中打开）
 * 已针对大数据特别优化
 * @package Library
 */
class ExporterCSV {
    private static $_file;
    private static $_limit = 100;
    private static $_i = 0;

    /**
     * 向浏览器输出cvs格式的Excel文件
     * @param string $file_name
     */
    public function __construct($file_name) {
        header('Content-Type: application/force-download');
        header('Content-Type: application/octet-stream');
        header('Content-Type: application/download');;
        header("Content-Type: application/vnd.ms-excel; name=\"" . $file_name . ".csv\"");
        header("Content-Disposition: attachment; filename=\"" . $file_name . ".csv\"");
        header('Cache-Control: max-age=0');
        self::$_file = fopen('php://output', 'a');
    }

    /**
     * 添加表格行
     * @param $row
     */
    public function addRow(&$row) {
        if (self::$_i == self::$_limit) {
            $this->push();
        }
        self::$_i++;
        fputcsv(self::$_file, self::iconv($row));
    }

    /**
     * 向浏览器推送数据表
     */
    public function push() {
        ob_flush();
        flush();
        self::$_i = 0;
    }

    private static function iconv(&$data) {
        if (is_array($data)) {
            return array_map(__METHOD__, $data);
        }
        return $data = iconv('utf-8', 'gbk', $data);
    }

    public function __destruct() {
        self::$_i && $this->push();
    }
}