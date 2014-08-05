<?php
namespace Library;

/**
 * RMB
 * 人民币处理类
 * @package Library
 *
 * $cn=new rmb();
 * $rmb=$cn->toRmb('2000000700.05');
 * $date=$cn->toDate('2014-01-28');
 */
class RMB {
    private $upNum = array("零","壹","贰","叁","肆","伍","陆","柒","捌","玖");
    /**
     * 将数字金额转换为人民币金额
     * @param int|string $numbers 数字金额
     * @return string
     */
    public function toRMB($numbers) {
        $numbers = explode(".", (string)$numbers);
        $left = strrev(strtr($numbers[0], array(','=>'')));
        $right = isset($numbers[1]) ? $numbers[1] : '';
        $leftResult = '';
        $rightResult = '';
        $upDigit = 0;
        $upDigitArray = array("元", "万", "亿", "兆", "京", "垓", "杼", "穰");;
        while ($left) {
            $n = substr($left,0,4);
            $left = substr($left, 4);
            $leftResult = $this->up($n, $upDigitArray[$upDigit++]) . $leftResult;
        }
        if ($right) {
            if (isset($right[1]) and $right[1]) {
                $rightResult = $this->upNum[$right[1]] . "分";
            }
            if (isset($right[0]) and $right[0]) {
                $rightResult = $this->upNum[$right[0]] . "角" . $rightResult;
            }
            elseif ($rightResult) {
                $rightResult = $this->upNum[0] . $rightResult;
            }
        }
        return preg_replace("/(零)+/", '$1', $leftResult . $rightResult) . '整';
    }

    /**
     * 将日期转换为支票的标准日期（贰零壹贰年肆月壹日）
     * @param string $dateString 时间字符串
     * @return string
     */
    public function toDate($dateString = null) {
        $date = empty($dateString) ? time() : strtotime($dateString);
        $Year = str_split(date("Y", $date));
        $Mon = strrev(date("n", $date));
        $Day = strrev(date("j", $date));
        foreach ($Year as $k => $v) {
            $Year[$k] = $this->upNum[$v];
        }
        return implode($Year) . "年" . $this->up($Mon) . "月" . $this->up($Day) . "日";
    }

    private function up($n, $digit = ''){
        $result = '';
        $has = false;
        $upDigit = 0;
        $upDigitArray = array("", "拾", "佰", "仟");
        for ($i = 0; $i < 4; $i++) {
            if (!isset($n[$i])) {
                break;
            }
            if ($n[$i] != 0) {
                $has = true;
                $result = $this->upNum[$n[$i]] . $upDigitArray[$upDigit] . $result;
            }
            elseif ($has) {
                $result = $this->upNum[0] . $result;
            }
            $upDigit++;
        }
        if ($result) {
            $result .= $digit;
        }
        else {
            $result .= $this->upNum[0];
        }
        return $result;
    }
}