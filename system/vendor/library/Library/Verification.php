<?php
namespace Library;

/**
 * Verification
 * 客户端输入数据验证类
 * @package Library
 */
class Verification {
    /**
     * 批量验证
     * @param array $rule //结构：array($rule,$str,$other_params,……,$error_msg)
     * @return mixed 成功时返回TRUE，失败时返回对应的$error_msg或False，请用===比较
     * 可以批量加入$rules如：
     * $verification = new verification();
     * $verification->testing(
     *              array($rule1,$str1,$other_params1,……,$error_msg1),
     *              array($rule2,$str2,$other_params2,……,$error_msg2),
     *              ……
     *              );
     *
     */
    public function testing($rule=array()){
        $rules=func_get_args();
        foreach ($rules as $rule) {
            if(!is_array($rule))  return FALSE;
            if(count($rule)<3) return FALSE;
            $method=array_shift($rule);
            $error_msg=array_pop($rule);
            if(!call_user_func_array(array($this, $method), $rule)){
                return $error_msg;
            }  else {
                continue;
            }
        }
        return TRUE;
    }

    /**
     * 英文、数字和下划线验证（用于检查用户名）
     * @param string $str 需要验证的字符串
     * @return boolean
     */
    public function isEnOrNum($str){
        return preg_match('/^[\-\_a-zA-Z0-9]+$/', $str);
    }

    /**
     * 	长度验证
     * 	@param  string $str 需要验证的字符串
     * 	@param  int    $min   字符串最小长度
     * 	@param  int    $max   字符串最大长度
     *  @return bool
     */
    public function inLength($str, $min = -1, $max= null) {
        if ($min >= -1 && strlen($str) < $min)
            return false;
        if (!is_null($max) && strlen($str) > $max)
            return false;
        return true;
    }
    /**
     * 比较两个值是否相同
     * @param string $str1
     * @param string $str2
     * @return bool
     */
    public function isSame($str1,$str2){
        return $str1==$str2;
    }
    /**
     * 	空白验证
     * 	@param  string $str 需要验证的字符串
     *  @return bool
     */
    public function isEmpty($str) {
        if (empty($str) || trim($str) == '')
            return TRUE;
        return FALSE;
    }
    /**
     * 	Email验证
     * 	@param  string $str 需要验证的字符串
     *  @return bool
     */
    public function isEmail($str) {
        return preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', trim($str));
    }
    /**
     * 	IP验证
     * 	@param  string $str 需要验证的字符串
     *  @return bool
     */
    public function isIP($str) {
        return preg_match('/^(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9])$/', trim($str));
    }
    /**
     * 	整数验证
     * 	@param  string $str 需要验证的字符串
     *  @return bool
     */
    public function isInteger($str) {
        return preg_match('/^[-\+]?\d+$/', trim($str));
    }
    /**
     * 	实数验证
     * 	@param  string $str 需要验证的字符串
     *  @return bool
     */
    public function isFloat($str) {
        return preg_match('/^[-\+]?[1-9]\d*(\.\d{0,2})?$/', trim($str));
    }
    /**
     * 	身份证验证
     * 	@param  string $str 需要验证的字符串
     *  @return bool
     */
    public function isIDCard($str) {
        return preg_match('/^(\d{15}|\d{17}[\dx])$/i', $str);
    }
    /**
     * 	座机电话验证
     * 	@param  string $str 需要验证的字符串
     *  @return bool
     */
    public function isTelephone($str) {
        return preg_match('/^((\(\d{2,3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7}(\-\d{1,4})?$/', trim($str));
    }
    /**
     * 	移动电话验证
     * 	@param  string $str 需要验证的字符串
     *  @return bool
     */
    public function isMobile($str) {
        return preg_match('/^((\(\d{2,3}\))|(\d{3}\-))?(13|15)\d{9}$/', trim($str));
    }
    /**
     * 	URL验证
     * 	@param  string $str 需要验证的字符串
     *  @return bool
     */
    public function isURL($str) {
        return preg_match('/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/', trim($str));
    }
    /**
     * 	邮政编码验证
     * 	@param  string $str 需要验证的字符串
     *  @return bool
     */
    public function isZipcode($str) {
        return preg_match('/^[1-9]\d{5}$/', trim($str));
    }
    /**
     * 	QQ号验证
     * 	@param  string $str 需要验证的字符串
     *  @return bool
     */
    public function isQQ($str) {
        return preg_match('/^[1-9]\d{4,12}$/', trim($str));
    }
    /**
     * 	英文字母验证
     * 	@param  string $str 需要验证的字符串
     *  @return bool
     */
    public function isEnglish($str) {
        return preg_match('/^[A-Za-z]+$/', trim($str));
    }
    /**
     * 	中文验证
     * 	@param  string $str 需要验证的字符串
     *  @return bool
     */
    public function isChinese($str) {
        return preg_match('/^([\xE4-\xE9][\x80-\xBF][\x80-\xBF])+$/', trim($str));
    }
    /**
     * 	安全字符验证
     * 	@param  string $str 需要验证的字符串
     *  @return bool
     */
    public function isSafeAll($str) {
        return !preg_match('/[\"\'<>\?\#\$\*\&;\\\/\[\]\{\}=\(\)\^%,]/i', $str);
    }
    /**
     * 	mysql内容安全验证
     * 	@param  string $str 需要验证的字符串
     *  @return bool
     */
    public function isSafeMysql($str) {
        return !preg_match('/[\"\'`\-\*;\s\[\]\{\}=\(\)\^%,', $str);
    }
    /**
     * 是否为合法的用户名
     * @param string $username 用户名
     * @return boolean 
     */
    public function isUserName($username) {
        if(!$username) return false;
        return
            preg_match("/^[_a-zA-Z0-9]+$/",$username) //欧美语系
            or preg_match("/^[\x{4e00}-\x{9fa5}_a-zA-Z0-9]+$/u",$username)  //汉字
            or preg_match("/^[\x{2e80}-\x{9fff}_a-zA-Z0-9]+$/u",$username);   //亚洲语系
    }
}