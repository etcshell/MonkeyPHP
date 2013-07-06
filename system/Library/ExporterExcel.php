<?php
namespace Library;

/**
 * excel_exporter
 * @category   导出xml格式的EXCEL表格工具（大数据专用）
 * @package    扩展库
 * @author     HuangYi
 * @copyright  Copyright (c) 2012-4-1——至今
 * @license    New BSD License
 * @version    $Id: excel_exporter.class.php 版本号 2013-1-1  $
 *
 */
class ExporterExcel {
    private static $file;
    private static $limit=100;
    private static $i=0;
    private static $_end=false;
    private static $encoding;
    private static $convert_types;
    /**
     * 向浏览器输出xml格式的Excel文件
     * @param string $filename 下载文件名
     * @param string $worksheet_title 工作表的标题
     * @param string $encoding
     * @param bool $convert_types 单元格中的数字处理方式，FALSE：按文本处理，TRUE：按数字处理
     */
    public function __construct($filename, $worksheet_title = 'Table1',$encoding = 'UTF-8', $convert_types = false){
        $file_name = urlencode($filename);//消除下的中文文件名乱码
        self::$convert_types = $convert_types;
        self::$encoding=$encoding;
        $title = preg_replace ('/[\\\|:|\/|\?|\*|\[|\]]/', '', $worksheet_title);
        $title = substr ($title, 0, 31);
        header('Content-Type: application/vnd.ms-excel; charset=' . $encoding);
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        self::$file = fopen('php://output', 'a');
        $header = '<?xml version="1.0" encoding="'.$encoding.'"?>'.PHP_EOL.'<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">';
        fwrite(self::$file, $header);
        fwrite(self::$file, PHP_EOL.'<Worksheet ss:Name="' . $title . '">'.PHP_EOL.'<Table>'.PHP_EOL);
    }
    /**
     * 添加数据行
     * @param array $row
     */
    public function addRow (array $row){
        if (self::$i == self::$limit)   $this->_push();
        self::$i++;
        $cells = '';
        foreach ($row as $v){
            $type = 'String';
            if (self::$convert_types === true && is_numeric($v))$type = 'Number';
            $v = htmlentities($v, ENT_COMPAT, self::$encoding);
            $cells .= '<Cell><Data ss:Type="'.$type.'">' . $v . "</Data></Cell>".PHP_EOL;
        }
        fwrite(self::$file, '<Row>'.PHP_EOL.$cells.'</Row>'.PHP_EOL);
    }
    /**
     * 结束输出（必须要，否则表格的格式不完整，Office不认可。）
     */
    public function end(){
        fwrite(self::$file, '</Table>'.PHP_EOL.'</Worksheet>'.PHP_EOL);
        fwrite(self::$file, '</Workbook>');
        $this->_push();
        self::$_end=TRUE;
    }
    private function _push(){
        ob_flush();
        flush();
        self::$i = 0;
    }
    /**
    * 构晰函数
    *
    * @return void
    */
    public function __destruct(){
        !self::$_end && $this->end();
        exit;
    }
}