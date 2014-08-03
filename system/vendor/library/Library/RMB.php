<?php
namespace Library;

    /**
     * 财务工具 rmb
     *转换示例
     * $cn=new rmb();
     * $rmb=$cn->toRmb($num);
     * $date=$cn->toDate();
     */
/**
 * RMB
 * 人民币处理类
 * @package Library
 *
 * $cn=new rmb();
 * $rmb=$cn->toRmb($num);
 * $date=$cn->toDate();
 */
class RMB {

    /**
     * 将数字金额转换为人民币金额
     * @param int|string $numbers 数字金额
     * @return string
     */
    public function numbersToRMB($numbers) {
        $capNum = array("零", "壹", "贰", "叁", "肆", "伍", "陆", "柒", "捌", "玖");
        $capDigit = array("", "拾", "佰", "仟");
        $subData = explode(".", (string)$numbers);
        $yuan = $subData[0];
        $j = 0;
        $nonzero = 0;
        $cnCap = '';
        for ($i = 0; $i < strlen($subData[0]); $i++) {
            if (0 == $i) { //确定个位
                if ($subData[1]) {
                    $cnCap = (substr($subData[0], -1, 1) != 0) ? "元" : "元零";
                }
                else {
                    $cnCap = "元";
                }
            }
            if (4 == $i) {
                $j = 0;
                $nonzero = 0;
                $cnCap = "万" . $cnCap;
            } //确定万位
            if (8 == $i) {
                $j = 0;
                $nonzero = 0;
                $cnCap = "亿" . $cnCap;
            } //确定亿位
            $numb = substr($yuan, -1, 1); //截取尾数
            $cnCap = ($numb) ? $capNum[$numb] . $capDigit[$j] . $cnCap : (($nonzero) ? "零" . $cnCap : $cnCap);
            $nonzero = ($numb) ? 1 : $nonzero;
            $yuan = substr($yuan, 0, strlen($yuan) - 1); //截去尾数
            $j++;
        }
        if ($subData[1]) {
            $cnCap .= (substr($subData[1], 0, 1)) ? $capNum[substr($subData[1], 0, 1)] . "角" : "零";
            $cnCap .= (substr($subData[1], 1, 1)) ? $capNum[substr($subData[1], 1, 1)] . "分" : "零分";
        }
        $cnCap = preg_replace("/(零)+/", "\\1", $cnCap); //合并连续“零”
        return $cnCap . '整';
    }

    /**
     * 将日期转换为支票的标准日期（贰零壹拾贰年肆月壹日）
     * @param string $dateString 时间字符串
     * @return string
     */
    public function dateToRMB($dateString = null) {
        if (empty($dateString)) {
            $Year = date("Y");
            $Mon = date("m");
            $Day = date("d");
        }
        else {
            $date = strtotime($dateString);
            $Year = date("Y", $date);
            $Mon = date("m", $date);
            $Day = date("d", $date);
        }
        $n = strlen($Year);
        $Y = '';
        $M = '';
        $D = '';
        $arrYear = array(
            "零","壹","贰","叁","肆","伍","陆","柒","捌","玖"
        );
        $arrMon = array(
            '', "零壹","零贰","零叁","零肆","零伍","零陆","零柒","零捌","零玖","零壹拾","壹拾壹","壹拾贰"
        );
        $arrDate = array(
            array('', "零壹","零贰","零叁","零肆","零伍","零陆","零柒","零捌","零玖"),
            array('零壹拾', "壹拾壹","壹拾贰","壹拾叁","壹拾肆","壹拾伍","壹拾陆","壹拾柒","壹拾捌","壹拾玖"),
            array('零贰拾', "贰拾壹","贰拾贰","贰拾叁","贰拾肆","贰拾伍","贰拾陆","贰拾柒","贰拾捌","贰拾玖"),
            array('零叁拾','叁拾壹')
        );

        for ($m = 0; $m < $n; $m++) {
            $jiaow = substr($Year, $m, 1);
            if (isset($arrYear[$jiaow])) {
                $Y .= $arrYear[$jiaow];
            }
        }

        if (isset($arrMon[$Mon])) {
            $M = $arrMon[$Mon];
        }

        $r1 = substr($Day, 0, 1);
        $r2 = substr($Day, 1, 1);
        if (isset($arrDate[$r1]) and isset($arrDate[$r1][$r2])) {
            $D = isset($arrDate[$r1][$r2]);
        }

        Return $Y . "年" . $M . "月" . $D . "日";
    }
}