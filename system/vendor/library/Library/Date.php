<?php
namespace Library;

/**
 * Date
 * 日期工具类
 * @package Library
 */
class Date {
    private $year, $month, $day;  //定义年 月 日
    /**
     * 设置日期
     * @param string $date 设置日期（格式2010-10-10）
     */
    public function __construct($date = '') {
        $this->setDate($date);
    }
    public function getDay(){
        return $this->day;
    }
    public function getMonth(){
        return $this->month;
    }
    public function getYear(){
        return $this->year;
    }
    /**
     * 增加天数
     * @param  int  $day_num  增加多少天
     * @return $this
     */
    public function addDay($day_num = 1) {
        $day_num = (int) $day_num;
        $day_num = $day_num * 86400;
        $time = $this->getTime() + $day_num;
        $this->setYear(date('Y', $time));
        $this->setMonth(date('m', $time));
        $this->setDay(date('d', $time));
        return $this;
    }
    /**
    * 获取已设置月的最后一天
    * @return int
    */
    public function getDayOfMonthEnd() {
        $end_day = 31;
        if($this->month==2) {
            $end_day = $this->isLeapYear($this->year) ? 29 : 28;
        } elseif($this->month==4 || $this->month==6 || $this->month==9 || $this->month==11) {
            $end_day = 30;
        }
        return $end_day;
    }
    /**
    * 获取星期几
    * @return int
    */
    public function getWeek() {
        return date('w', $this->getTime(1));
    }
    /**
     * 是否是闰年
     * @return boolean
     */
    public function isLeapYear() {
        return (bool)date('L', mktime(0,0,0,$this->month,$this->day,$this->year));
    }
    /**
    * 获取已设置日期的字符串形式
    * @return string 返回：2010-10-10
    */
    public function getDate() {
        return $this->year.'-'.$this->month.'-'.$this->day;
    }
    /**
    * 获取已设置日期的数组形式
    * @return array  如array( '2010', '10', '20')
    */
    public function getDateArray() {
        return array($this->year,$this->month,$this->day);
    }
    /**
     * 返回时间戳
     * 默认返回指定日期的0时
     * @param int $hour 小时
     * @param int $minute 分
     * @param int $second 秒
     * @param int|null $month 月
     * @param int|null $day 日
     * @param int|null $year 年
     * @return int
     */
    public function getTime($hour=0,$minute=0,$second=0,$month=null,$day=null,$year=null){
        !$month and $month=$this->month;
        !$day and $day=$this->day;
        !$year and $year=$this->year;
        return mktime($hour,$minute,$second,$month,$day,$year);
    }
    /**
     * 计算2个日期的差值
     * 算法结果与sql查询结果相同
     * @param string $dif_date 要比较的时间字符串
     * @param string $refer_date 参考时间，默认为类中指定日期天的0时
     * @return int
     */
    public function getDifferenceOfSQL($dif_date, $refer_date=null) {
        $dif_date = strtotime($dif_date);
        $dif_date = $dif_date - $dif_date%86400;
        if(is_null($refer_date)){
            $refer_date = mktime(0,0,0,$this->month,$this->day,$this->year);
        }else{
            $refer_date = strtotime($refer_date);
            $refer_date = $refer_date - $refer_date%86400;
        }
        return abs(($dif_date - $refer_date)/86400);
    }
    /**
     * 计算2个日期的差值
     * @param string $dif_date 要比较的时间字符串
     * @param string $refer_date 参考时间，默认为类中指定日期天的0时
     * @return int
     */
    public function getDifference($dif_date, $refer_date=null) {
        $dif_date = strtotime($dif_date);
        if(is_null($refer_date)){
            $refer_date = mktime(0,0,0,$this->month,$this->day,$this->year);
        }else{
            $refer_date = strtotime($refer_date);
        }
        return ceil(abs(($dif_date - $refer_date)/86400));
    }
    /**
    * 获取星期几
    * @param int $time 时间戳，默认使用已设置日期
    * @return string
    */
    public function getWeekOfChina($time = null) {
        !$time and $time=$this->getTime(1);
        $weekArr = array("星期天", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六");
        return $weekArr[(int) date('w', $time)];
    }
    /**
     * 设置日期
     * @param string   $date  设置日期（格式2010-10-10）
     * @return $this
     */
    public function setDate($date = '') {
        if ($date !== '') {
            list($year, $month, $day) = explode('-', $date);
            $this->setYear($year);
            $this->setMonth($month);
            $this->setDay($day);
        } else {
            $this->setYear(date('Y'));
            $this->setMonth(date('m'));
            $this->setDay(date('d'));
        }
        return $this;
    }
    /**
    * 单独设置年
    * @param string  $year   年
    * @return $this
    */
    private function setYear($year) {
        $year = (int) $year;
        $this->year = ($year <= 2100 && $year >= 1970) ? $year : date('Y');
        return $this;
    }
    /**
    * 单独设置月
    * @param string  $month  月
    * @return $this
    */
    private function setMonth($month) {
        $month = ltrim((int) $month, '0');
        $this->month = ($month < 13 && $month > 0) ? $month : date('m');
        return $this;
    }
    /**
    * 单独设置日
    * @param string  $day  日
    * @return $this;
    */
    private function setDay($day) {
        $day = ltrim((int) $day, '0');
        $this->day=
            ($this->year && $this->month  && checkdate($this->month, $day, $this->year))
            ? $day
            : date('d');
        return $this;
    }
}