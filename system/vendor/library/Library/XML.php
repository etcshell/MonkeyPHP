<?php
namespace Library;

/**
 * XML
 * xml编码解码类
 * @package Library
 */
class XML {
    /**
     * xml编码
     * @param string $data 数据
     * @param string $encoding 显示编码
     * @param string $root
     * @return string
     */
    public function Encode(&$data, $encoding = 'utf-8', $root = "phpsys.cn") {
        $xml = '<?xml version="1.0" encoding="' . $encoding . '"?>';
        $xml .= '<' . $root . '>';
        $xml .= $this->dataToXML($data);
        $xml .= '</' . $root . '>';
        return $xml;
    }

    public function dataToXML(&$data) {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        $xml = '';
        foreach ($data as $key => $val) {
            is_numeric($key) and $key = "item id=\"$key\"";
            $xml .= "<$key>";
            $xml .= (is_array($val) || is_object($val)) ? $this->dataToXML($val) : $val;
            list ($key,) = explode(' ', $key);
            $xml .= "</$key>";
        }
        return $xml;
    }
}
