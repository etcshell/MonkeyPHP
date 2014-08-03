<?php
namespace Library;

/**
 * Unique
 * 唯一码生成类
 * @package Library
 */
final class Unique {

    /**
     * @static
     * 获取定长的随机字符串
     * @param int $len 随机值的长度<32的非负整数
     * @param string $str 基准字符串
     * @return string
     */
    public static function getRand($len, $str = 'abcdefghijklmnopqrstuvwxyz') {
        return substr(md5(uniqid(rand() * strval($str))), 0, (int)$len);
    }

    /**
     * @static
     * 生成20位唯一的订单号(交易序列号)
     * 如20110809111259232312可以解释为：
     * 2011-年日期
     * 08-月份
     * 09-日期
     * 11-小时
     * 12-分
     * 59-秒
     * 2323-微秒
     * 12-随机值
     * @return string
     */
    public static function getSerialByTime() {
        $usec = substr(str_replace('0.', '', microtime(true)), 0, 4);
        $str = rand(10, 99);
        return date("YmdHis") . $usec . $str;
    }

    /**
     * @static
     * 获取定长的随机Hash值
     * @param int $length hash值的长度
     * @return string
     */
    public static function getHash($length = 13) {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyz';
        $max = strlen($chars) - 1;
        mt_srand((double)microtime() * 1000000);
        $hash = '';
        for ($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
        return $hash;
    }
}
