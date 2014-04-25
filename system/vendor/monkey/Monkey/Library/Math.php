<?php
namespace Library;

/**
 * 数学计算工具 math
 */
final class Math
{
    /**
     * @static
     * 10进制转N进制
     * @param int $int 十进制数
     * @param int $n 目标进制，介于2到62之间
     * @return string n进制结果字符串
     */
    public static function decimalToN($int, $n=62) {
        $return = '';
        while($int > 0) {
            $s = $int % $n;
            if($s > 35) $s = chr($s+61);
            elseif($s > 9) $s = chr($s + 55);
            $return .= $s;
            $int = floor($int/$n);
        }
        return strrev($return);
    }
    /**
     * @static
     * N进制转10进制
     * @param string $str 代表n进制的字符串
     * @param int $n 输入参数的进制，介于2到62之间
     * @return int
     */
    public static function nToDecimal($str, $n=62) {
        $return = $num = 0;
        $len = strlen($str);
        for($i=0;$i<$len;$i++) {
            $num = ord($str{$i});
            if($num > 96) $num -= 61;
            elseif($num > 64) $num -= 55;
            else $num -= 48;
            $return += $num * pow($n, $len-1-$i);
        }
        return $return;
    }
}
