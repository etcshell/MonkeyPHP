<?php
namespace Library;

/**
 * ExporterCSV
 * 导出csv表格类（可以在EXCEL表格工具中打开）
 * 已针对大数据特别优化
 * @package Library
 */
class ExporterCSV {

    private static $file;
    private static $limit = 100;
    private static $i = 0;

    /**
     * 向浏览器输出cvs格式的Excel文件
     * @param string $fileName
     */
    public function __construct($fileName) {
        header('Content-Type: application/force-download');
        header('Content-Type: application/octet-stream');
        header('Content-Type: application/download');;
        header("Content-Type: application/vnd.ms-excel; name=\"" . $fileName . ".csv\"");
        header("Content-Disposition: attachment; filename=\"" . $fileName . ".csv\"");
        header('Cache-Control: max-age=0');
        self::$file = fopen('php://output', 'a');
    }

    /**
     * 添加表格行
     * @param $row
     */
    public function addRow(&$row) {
        if (self::$i == self::$limit) {
            $this->push();
        }
        self::$i++;
        fputcsv(self::$file, self::iconv($row));
    }

    /**
     * 向浏览器推送数据表
     */
    public function push() {
        ob_flush();
        flush();
        self::$i = 0;
    }

    private static function iconv(&$data) {
        if (is_array($data)) {
            return array_map(__METHOD__, $data);
        }
        return $data = iconv('utf-8', 'gbk', $data);
    }

    public function __destruct() {
        self::$i && $this->push();
    }
}