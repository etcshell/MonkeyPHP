<?php
namespace Library;

/**
 * 字符串工具 str
 */
final class Str
{
    /**
     * @static
     * 子字符串是否存在于完整的字符串中
     * @param string $str :字符或字符串
     * @param string $fullStr :字符串
     * @return bool 例子: $str='34' $string='1234' 返回 TRUE
     */
    public static function hasSub($str, $fullStr) {
        return strstr((string) $fullStr,(string) $str)===false ? false : true;
    }
    /**
     * @static
     * 过滤左侧字符串
     * @param string $str 要过滤的主字符串
     * @param string $filter 主字符串左侧要过滤的子字符串
     * @return string 成功返回主字符串的右侧剩余部分，失败返回false，由于可能返回空字符串，所以请用===来检测false
     */
    public static function filterLeft($str, $filter) {
        $filterLen = strlen($filter);
        if ($filterLen == 0)
            return $str;
        if (substr($str, 0, $filterLen) !== $filter)
            return FALSE;
        if (strlen($str) == $filterLen)
            return '';
        return substr($str, $filterLen);
    }
    /**
     * @static
     * 过滤右侧字符串
     * @param string $mainStr 要过滤的主字符串
     * @param string $filter 主字符串右侧要过滤的子字符串
     * @return string 成功返回主字符串的左侧剩余部分，失败返回false，由于可能返回空字符串，所以请用===来检测false
     */
    public static function filterRight($mainStr, $filter) {
        $filterLen = strlen($filter);
        if ($filterLen == 0)
            return $mainStr;
        if (substr($mainStr, 0 - $filterLen) !== $filter)
            return FALSE;
        return substr($mainStr, 0, 0 - $filterLen);
    }
    /**
     * @static
     * 获取多语言字符串的子串，支持gb2312,gbk,utf-8,big5
     * @param string $str 主体字符串
     * @param int $start 截取起始位置,第一个字符位置是0
     * @param int $length 截取长度
     * @param string $charset utf-8|gb2312|gbk|big5 编码，默认为'utf-8'
     * @param bool $suffix 是否加尾缀，注意加尾缀时，尾缀会覆盖所取得的字串内容，导致内容不完整！仅用在列表分页的列表行切割中（使得每一行一样长）。
     * @param string $suffix_str 尾缀字符串
     * @return string
     */
    public static function subText($str, $start, $length,$charset=null,$suffix=false,$suffix_str='…') {
        if (empty ( $str )) {
            return $str;
        }
        $a=array('&', '"', '<', '>',' ');
        $b=array('&amp;', '&quot;', '&lt;', '&gt;','&nbsp;');
        $str = str_replace($b,$a, $str);
        !$charset and $charset = 'utf-8';
        if(function_exists('mb_substr'))    {
            if(mb_strlen($str, $charset) <= $start+$length){
                $suffix=FALSE;
            }else{
                $suffix_len=mb_strlen($suffix_str, $charset);
                self::fixLength($suffix,$length,$suffix_len);
            }
            $slice = mb_substr($str, $start, $length, $charset);
        }elseif (function_exists('iconv_substr'))  {
            if(iconv_strlen($str, $charset) <= $start+$length){
                $suffix=FALSE;
            }else{
                $suffix_len=iconv_strlen($suffix_str, $charset);
                self::fixLength($suffix,$length,$suffix_len);
            }
            $slice = iconv_substr($str, $start, $length, $charset);
        } else {
            $re=array();$match=array();$suffix_match=array();
            $re['utf-8']   = '/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/';
            $re['gb2312'] = '/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/';
            $re['gbk']     = '/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/';
            $re['big5']   = '/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/';
            preg_match_all($re[$charset], $str, $match);
            if(isset($match[0][$length+$start])){
                preg_match_all($re[$charset], $suffix_str, $suffix_match);
                $suffix_len=count($suffix_match[0]);
                self::fixLength($suffix,$length,$suffix_len);
            }else{
                $suffix=FALSE;
            }
            $slice = join('',array_slice($match[0], $start, $length));
        }
        $suffix and $slice.=$suffix_str;
        return str_replace($a,$b, $slice);
    }
    private static function fixLength(&$suffix,&$length,$suffix_len){
        if($suffix && $length>$suffix_len){
            $length-=$suffix_len;
        }  else {
            $suffix=FALSE;
        }
    }
    /**
     * @static
     * 截取HTML字符串 忽略HTML标志不计
     * @param string $body 要截取的HTML
     * @param int $size 截取的数量
     * @return string
     */
    public static function subHtml($body, $size) {
        $_size = mb_strlen ( $body, 'utf-8' );
        $html_array_str='';
        if ($_size <= $size)
            return $body;
        $strlen_var = strlen ( $body );
        // 不包含 html 标签
        if (strpos ( $body, '<' ) === false) {
            return mb_substr ( $body, 0, $size );
        }
        // 包含截断标志，优先
        $e = strpos ( $body, '<!-- break -->' );
        if ($e) {
            return mb_substr ( $body, 0, $e );
        }
        // html 代码标记
        $html_tag = 0;
        // 摘要字符串
        $summary_string = '';
        /**
         * 数组用作记录摘要范围内出现的 html 标签
         * 开始和结束分别保存在 left 和 right 键名下
         * 如字符串为：<h3><p><b>a</b></h3>，假设 p 未闭合
         * 数组则为：array('left' => array('h3', 'p', 'b'), 'right' => 'b', 'h3');
         * 仅补全 html 标签，<? <% 等其它语言标记，会产生不可预知结果
         */
        $html_array = array ('left' => array (), 'right' => array () );
        for($i = 0; $i < $strlen_var; ++ $i) {
            if (! $size) {
                break;
            }
            $current_var = substr ( $body, $i, 1 );
            if ($current_var == '<') {
                // html 代码开始
                $html_tag = 1;
                $html_array_str = '';
            } else if ($html_tag == 1) {
                // 一段 html 代码结束
                if ($current_var == '>') {
                    /**
                     * 去除首尾空格，如 <br /  > < img src="" / > 等可能出现首尾空格
                     */
                    $html_array_str = trim ( $html_array_str );
                    /**
                     * 判断最后一个字符是否为 /，若是，则标签已闭合，不记录
                     */
                    if (substr ( $html_array_str, - 1 ) != '/') {
                        // 判断第一个字符是否 /，若是，则放在 right 单元
                        $f = substr ( $html_array_str, 0, 1 );
                        if ($f == '/') {
                            // 去掉 /
                            $html_array ['right'] [] = str_replace ( '/', '', $html_array_str );
                        } else if ($f != '?') {
                            // 判断是否为 ?，若是，则为 PHP 代码，跳过
                            /**
                             * 判断是否有半角空格，若有，以空格分割，第一个单元为 html 标签
                             * 如 <h2> <p>
                             */
                            if (strpos ( $html_array_str, ' ' ) !== false) {
                                // 分割成2个单元，可能有多个空格，如：<h2 class="" id="">
                                $html_array ['left'] [] = strtolower ( current ( explode ( ' ', $html_array_str, 2 ) ) );
                            } else {
                                /**
                                 * * 若没有空格，整个字符串为 html 标签，如：<b> <p> 等
                                 * 统一转换为小写
                                 */
                                $html_array ['left'] [] = strtolower ( $html_array_str );
                            }
                        }
                    }
                    // 字符串重置
                    $html_array_str = '';
                    $html_tag = 0;
                } else {
                    /**
                     * 将< >之间的字符组成一个字符串
                     * 用于提取 html 标签
                     */
                    $html_array_str .= $current_var;
                }
            } else {
                // 非 html 代码才记数
                -- $size;
            }
            $ord_var_c = ord ( $body {$i} );
            switch (true) {
                case (($ord_var_c & 0xE0) == 0xC0) :
                    // 2 字节
                    $summary_string .= substr ( $body, $i, 2 );
                    $i += 1;
                    break;
                case (($ord_var_c & 0xF0) == 0xE0) :
                    // 3 字节
                    $summary_string .= substr ( $body, $i, 3 );
                    $i += 2;
                    break;
                case (($ord_var_c & 0xF8) == 0xF0) :
                    // 4 字节
                    $summary_string .= substr ( $body, $i, 4 );
                    $i += 3;
                    break;
                case (($ord_var_c & 0xFC) == 0xF8) :
                    // 5 字节
                    $summary_string .= substr ( $body, $i, 5 );
                    $i += 4;
                    break;
                case (($ord_var_c & 0xFE) == 0xFC) :
                    // 6 字节
                    $summary_string .= substr ( $body, $i, 6 );
                    $i += 5;
                    break;
                default :
                    // 1 字节
                    $summary_string .= $current_var;
            }
        }
        if ($html_array ['left']) {
            /**
             * 比对左右 html 标签，不足则补全
             */
            /**
             * 交换 left 顺序，补充的顺序应与 html 出现的顺序相反
             * 如待补全的字符串为：<h2>abc<b>abc<p>abc
             * 补充顺序应为：</p></b></h2>
             */
            $html_array ['left'] = array_reverse ( $html_array ['left'] );
            foreach ( $html_array ['left'] as $tag ) {
                // 判断该标签是否出现在 right 中
                $key = array_search ( $tag, $html_array ['right'] );
                if ($key !== false) {
                    // 出现，从 right 中删除该单元
                    unset ( $html_array ['right'] [$key] );
                } else {
                    // 没有出现，需要补全
                    $summary_string .= '</' . $tag . '>';
                }
            }
        }
        return $summary_string;
    }
    /**
     * @static
     * 获取多语言字符串的字符数，支持gb2312,gbk,utf-8,big5
     * @param string $str 要统计的字串
     * @param string $charset utf-8|gb2312|gbk|big5 编码
     * @return int
     */
    public static function length($str, $charset='utf-8') {
        if (empty ( $str )) {
            return 0;
        }
        if(function_exists('mb_substr'))    {
            return mb_strlen($str, $charset);
        }elseif (function_exists('iconv_strlen')) {
            return iconv_strlen($str, $charset);
        }else {
            $re=array();$match=array();
            $re['utf-8']   = '/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/';
            $re['gb2312'] = '/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/';
            $re['gbk']     = '/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/';
            $re['big5']   = '/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/';
            preg_match_all($re[$charset], $str, $match);
            return count($match[0]);
        }
    }
    /**
     * @static
     * 简单的加密解密函数
     * @param string $string  加密的字符串
     * @param string $key     加密的密钥
     * @param string $type    加密的方法-ENCODE|加密 DECODE|解密
     * @return string
     */
    public static function encrypt($string, $key, $type = 'ENCODE') {
        $string = ($type == 'DECODE') ? base64_decode($string) : $string;
        $key_len = strlen($key);
        $key     = md5($key);
        $string_len = strlen($string);$code='';
        for ($i=0; $i<$string_len; $i++) {
            $j = ($i * $key_len) % 32;
            $code .= $string[$i] ^ $key[$j];
        }
        return ($type == 'ENCODE') ? base64_encode($code) : $code;
    }
    /**
     * @static
     * 全角转半角
     * @param string $str 待转换的字符串
     * @param boolean $onlyw 是否只转换文字，默认为是
     * @return string
     */
    public static function toSemiangle($str, $onlyw=TRUE) {
        if ($onlyw)
            $arr = array(
                '０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
                '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
                'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E',
                'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
                'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O',
                'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
                'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y',
                'Ｚ' => 'Z', '＠' =>'@',  '－' => '-');
        else
            $arr = array(
                '０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
                '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
                'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E',
                'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
                'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O',
                'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
                'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y',
                'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
                'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i',
                'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
                'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's',
                'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
                'ｙ' => 'y', 'ｚ' => 'z', '（' => '(', '）' => ')', '〔' => '[',
                '〕' => ']', '【' => '[', '】' => ']', '〖' => '[', '〗' => ']',
                '“'  => '[', '”'  => ']',  '‘' => '[', '’'  => ']', '｛' => '{',
                '｝' => '}', '《' => '<', '》' => '>', '％' => '%', '＋' => '+',
                '—'  => '-', '－' => '-', '～' => '-', '：' => ':', '。' => '.',
                '、' => ',', '，' => '.', '、' => '.', '；' => ',', '？' => '?',
                '！' => '!', '…'  => '-', '‖' => '|',  '”' => '"', '’'  => '`',
                '‘'  => '`', '｜' => '|', '〃' => '"', '　' => ' ', '＄' => '$',
                '＠' => '@', '＃' => '#', '＾' => '^', '＆' => '&', '＊' => '*',
                '＂' => '"');
        return strtr($str, $arr);
    }

    /**
     * @static
     * 中文简体转为繁体
     * @param string $data 需要转换的内容
     * @param bool $data_is_utf8  TRUE（默认值）：原数据是UTF8编码的。FALSE：原数据是GB2312编码的。
     * @return string
     */
    public static function cn2tw($data,$data_is_utf8 =true)
    {
        return self::cn($data,1,$data_is_utf8);
    }

    /**
     * @static
     * 中文繁体转为简体
     * @param string $data 需要转换的内容
     * @param bool $data_is_utf8  TRUE（默认值）：原数据是UTF8编码的。FALSE：原数据是GB2312编码的。
     * @return string
     */
    public static function tw2cn($data,$data_is_utf8 =true)
    {
        return self::cn($data,0,$data_is_utf8);
    }

    /**
     * @static
     * 中文简体和繁体互转
     * @param string $data 需要转换的内容
     * @param int $type 0繁体转简体，1简体转繁体。默认为0（繁体转为简体）
     * @param bool $data_is_utf8  TRUE（默认值）：原数据是UTF8编码的。FALSE：原数据是GB2312编码的。
     * @return string
     */
    private static function cn($data, $type = 0, $data_is_utf8 =true) {
        $simcn = "万与丑专业丛东丝丢两严丧个丬丰临为丽举么义乌乐乔习乡书买乱争于亏云亘亚产亩亲亵亸亿仅从仑仓仪们价众优伙会伛伞伟传伤伥伦伧伪伫体余佣佥侠侣侥侦侧侨侩侪侬俣俦俨俩俪俭债倾偬偻偾偿傥傧储傩儿兑兖党兰关兴兹养兽冁内冈册写军农冢冯冲决况冻净凄凉凌减凑凛几凤凫凭凯击凼凿刍划刘则刚创删别刬刭刽刿剀剂剐剑剥剧劝办务劢动励劲劳势勋勐勚匀匦匮区医华协单卖卢卤卧卫却卺厂厅历厉压厌厍厕厢厣厦厨厩厮县参叆叇双发变叙叠叶号叹叽吁后吓吕吗吣吨听启吴呒呓呕呖呗员呙呛呜咏咔咙咛咝咤咴咸哌响哑哒哓哔哕哗哙哜哝哟唛唝唠唡唢唣唤唿啧啬啭啮啰啴啸喷喽喾嗫呵嗳嘘嘤嘱噜噼嚣嚯团园囱围囵国图圆圣圹场坂坏块坚坛坜坝坞坟坠垄垅垆垒垦垧垩垫垭垯垱垲垴埘埙埚埝埯堑堕塆墙壮声壳壶壸处备复够头夸夹夺奁奂奋奖奥妆妇妈妩妪妫姗姜娄娅娆娇娈娱娲娴婳婴婵婶媪嫒嫔嫱嬷孙学孪宁宝实宠审宪宫宽宾寝对寻导寿将尔尘尧尴尸尽层屃屉届属屡屦屿岁岂岖岗岘岙岚岛岭岳岽岿峃峄峡峣峤峥峦崂崃崄崭嵘嵚嵛嵝嵴巅巩巯币帅师帏帐帘帜带帧帮帱帻帼幂幞干并广庄庆庐庑库应庙庞废庼廪开异弃张弥弪弯弹强归当录彟彦彻径徕御忆忏忧忾怀态怂怃怄怅怆怜总怼怿恋恳恶恸恹恺恻恼恽悦悫悬悭悯惊惧惨惩惫惬惭惮惯愍愠愤愦愿慑慭憷懑懒懔戆戋戏戗战戬户扎扑扦执扩扪扫扬扰抚抛抟抠抡抢护报担拟拢拣拥拦拧拨择挂挚挛挜挝挞挟挠挡挢挣挤挥挦捞损捡换捣据捻掳掴掷掸掺掼揸揽揿搀搁搂搅携摄摅摆摇摈摊撄撑撵撷撸撺擞攒敌敛数斋斓斗斩断无旧时旷旸昙昼昽显晋晒晓晔晕晖暂暧札术朴机杀杂权条来杨杩杰极构枞枢枣枥枧枨枪枫枭柜柠柽栀栅标栈栉栊栋栌栎栏树栖样栾桊桠桡桢档桤桥桦桧桨桩梦梼梾检棂椁椟椠椤椭楼榄榇榈榉槚槛槟槠横樯樱橥橱橹橼檐檩欢欤欧歼殁殇残殒殓殚殡殴毁毂毕毙毡毵氇气氢氩氲汇汉污汤汹沓沟没沣沤沥沦沧沨沩沪沵泞泪泶泷泸泺泻泼泽泾洁洒洼浃浅浆浇浈浉浊测浍济浏浐浑浒浓浔浕涂涌涛涝涞涟涠涡涢涣涤润涧涨涩淀渊渌渍渎渐渑渔渖渗温游湾湿溃溅溆溇滗滚滞滟滠满滢滤滥滦滨滩滪漤潆潇潋潍潜潴澜濑濒灏灭灯灵灾灿炀炉炖炜炝点炼炽烁烂烃烛烟烦烧烨烩烫烬热焕焖焘煅煳熘爱爷牍牦牵牺犊犟状犷犸犹狈狍狝狞独狭狮狯狰狱狲猃猎猕猡猪猫猬献獭玑玙玚玛玮环现玱玺珉珏珐珑珰珲琎琏琐琼瑶瑷璇璎瓒瓮瓯电画畅畲畴疖疗疟疠疡疬疮疯疱疴痈痉痒痖痨痪痫痴瘅瘆瘗瘘瘪瘫瘾瘿癞癣癫癯皑皱皲盏盐监盖盗盘眍眦眬着睁睐睑瞒瞩矫矶矾矿砀码砖砗砚砜砺砻砾础硁硅硕硖硗硙硚确硷碍碛碜碱碹磙礼祎祢祯祷祸禀禄禅离秃秆种积称秽秾稆税稣稳穑穷窃窍窑窜窝窥窦窭竖竞笃笋笔笕笺笼笾筑筚筛筜筝筹签简箓箦箧箨箩箪箫篑篓篮篱簖籁籴类籼粜粝粤粪粮糁糇紧絷纟纠纡红纣纤纥约级纨纩纪纫纬纭纮纯纰纱纲纳纴纵纶纷纸纹纺纻纼纽纾线绀绁绂练组绅细织终绉绊绋绌绍绎经绐绑绒结绔绕绖绗绘给绚绛络绝绞统绠绡绢绣绤绥绦继绨绩绪绫绬续绮绯绰绱绲绳维绵绶绷绸绹绺绻综绽绾绿缀缁缂缃缄缅缆缇缈缉缊缋缌缍缎缏缐缑缒缓缔缕编缗缘缙缚缛缜缝缞缟缠缡缢缣缤缥缦缧缨缩缪缫缬缭缮缯缰缱缲缳缴缵罂网罗罚罢罴羁羟羡翘翙翚耢耧耸耻聂聋职聍联聩聪肃肠肤肷肾肿胀胁胆胜胧胨胪胫胶脉脍脏脐脑脓脔脚脱脶脸腊腌腘腭腻腼腽腾膑臜舆舣舰舱舻艰艳艹艺节芈芗芜芦苁苇苈苋苌苍苎苏苘苹茎茏茑茔茕茧荆荐荙荚荛荜荞荟荠荡荣荤荥荦荧荨荩荪荫荬荭荮药莅莜莱莲莳莴莶获莸莹莺莼萚萝萤营萦萧萨葱蒇蒉蒋蒌蓝蓟蓠蓣蓥蓦蔷蔹蔺蔼蕲蕴薮藁藓虏虑虚虫虬虮虽虾虿蚀蚁蚂蚕蚝蚬蛊蛎蛏蛮蛰蛱蛲蛳蛴蜕蜗蜡蝇蝈蝉蝎蝼蝾螀螨蟏衅衔补衬衮袄袅袆袜袭袯装裆裈裢裣裤裥褛褴襁襕见观觃规觅视觇览觉觊觋觌觍觎觏觐觑觞触觯詟誉誊讠计订讣认讥讦讧讨让讪讫训议讯记讱讲讳讴讵讶讷许讹论讻讼讽设访诀证诂诃评诅识诇诈诉诊诋诌词诎诏诐译诒诓诔试诖诗诘诙诚诛诜话诞诟诠诡询诣诤该详诧诨诩诪诫诬语诮误诰诱诲诳说诵诶请诸诹诺读诼诽课诿谀谁谂调谄谅谆谇谈谊谋谌谍谎谏谐谑谒谓谔谕谖谗谘谙谚谛谜谝谞谟谠谡谢谣谤谥谦谧谨谩谪谫谬谭谮谯谰谱谲谳谴谵谶谷豮贝贞负贠贡财责贤败账货质贩贪贫贬购贮贯贰贱贲贳贴贵贶贷贸费贺贻贼贽贾贿赀赁赂赃资赅赆赇赈赉赊赋赌赍赎赏赐赑赒赓赔赕赖赗赘赙赚赛赜赝赞赟赠赡赢赣赪赵赶趋趱趸跃跄跖跞践跶跷跸跹跻踊踌踪踬踯蹑蹒蹰蹿躏躜躯车轧轨轩轪轫转轭轮软轰轱轲轳轴轵轶轷轸轹轺轻轼载轾轿辀辁辂较辄辅辆辇辈辉辊辋辌辍辎辏辐辑辒输辔辕辖辗辘辙辚辞辩辫边辽达迁过迈运还这进远违连迟迩迳迹适选逊递逦逻遗遥邓邝邬邮邹邺邻郁郄郏郐郑郓郦郧郸酝酦酱酽酾酿释里鉅鉴銮錾钆钇针钉钊钋钌钍钎钏钐钑钒钓钔钕钖钗钘钙钚钛钝钞钟钠钡钢钣钤钥钦钧钨钩钪钫钬钭钮钯钰钱钲钳钴钵钶钷钸钹钺钻钼钽钾钿铀铁铂铃铄铅铆铈铉铊铋铍铎铏铐铑铒铕铗铘铙铚铛铜铝铞铟铠铡铢铣铤铥铦铧铨铪铫铬铭铮铯铰铱铲铳铴铵银铷铸铹铺铻铼铽链铿销锁锂锃锄锅锆锇锈锉锊锋锌锍锎锏锐锑锒锓锔锕锖锗错锚锜锞锟锠锡锢锣锤锥锦锨锩锫锬锭键锯锰锱锲锳锴锵锶锷锸锹锺锻锼锽锾锿镀镁镂镃镆镇镈镉镊镌镍镎镏镐镑镒镕镖镗镙镚镛镜镝镞镟镠镡镢镣镤镥镦镧镨镩镪镫镬镭镮镯镰镱镲镳镴镶长门闩闪闫闬闭问闯闰闱闲闳间闵闶闷闸闹闺闻闼闽闾闿阀阁阂阃阄阅阆阇阈阉阊阋阌阍阎阏阐阑阒阓阔阕阖阗阘阙阚阛队阳阴阵阶际陆陇陈陉陕陧陨险随隐隶隽难雏雠雳雾霁霉霭靓静靥鞑鞒鞯鞴韦韧韨韩韪韫韬韵页顶顷顸项顺须顼顽顾顿颀颁颂颃预颅领颇颈颉颊颋颌颍颎颏颐频颒颓颔颕颖颗题颙颚颛颜额颞颟颠颡颢颣颤颥颦颧风飏飐飑飒飓飔飕飖飗飘飙飚飞飨餍饤饥饦饧饨饩饪饫饬饭饮饯饰饱饲饳饴饵饶饷饸饹饺饻饼饽饾饿馀馁馂馃馄馅馆馇馈馉馊馋馌馍馎馏馐馑馒馓馔馕马驭驮驯驰驱驲驳驴驵驶驷驸驹驺驻驼驽驾驿骀骁骂骃骄骅骆骇骈骉骊骋验骍骎骏骐骑骒骓骔骕骖骗骘骙骚骛骜骝骞骟骠骡骢骣骤骥骦骧髅髋髌鬓魇魉鱼鱽鱾鱿鲀鲁鲂鲄鲅鲆鲇鲈鲉鲊鲋鲌鲍鲎鲏鲐鲑鲒鲓鲔鲕鲖鲗鲘鲙鲚鲛鲜鲝鲞鲟鲠鲡鲢鲣鲤鲥鲦鲧鲨鲩鲪鲫鲬鲭鲮鲯鲰鲱鲲鲳鲴鲵鲶鲷鲸鲹鲺鲻鲼鲽鲾鲿鳀鳁鳂鳃鳄鳅鳆鳇鳈鳉鳊鳋鳌鳍鳎鳏鳐鳑鳒鳓鳔鳕鳖鳗鳘鳙鳛鳜鳝鳞鳟鳠鳡鳢鳣鸟鸠鸡鸢鸣鸤鸥鸦鸧鸨鸩鸪鸫鸬鸭鸮鸯鸰鸱鸲鸳鸴鸵鸶鸷鸸鸹鸺鸻鸼鸽鸾鸿鹀鹁鹂鹃鹄鹅鹆鹇鹈鹉鹊鹋鹌鹍鹎鹏鹐鹑鹒鹓鹔鹕鹖鹗鹘鹚鹛鹜鹝鹞鹟鹠鹡鹢鹣鹤鹥鹦鹧鹨鹩鹪鹫鹬鹭鹯鹰鹱鹲鹳鹴鹾麦麸黄黉黡黩黪黾鼋鼌鼍鼗鼹齄齐齑齿龀龁龂龃龄龅龆龇龈龉龊龋龌龙龚龛龟志制咨只里系范松没尝尝闹面准钟别闲干尽脏拼";
        $tracn = "萬與醜專業叢東絲丟兩嚴喪個爿豐臨為麗舉麼義烏樂喬習鄉書買亂爭於虧雲亙亞產畝親褻嚲億僅從侖倉儀們價眾優夥會傴傘偉傳傷倀倫傖偽佇體餘傭僉俠侶僥偵側僑儈儕儂俁儔儼倆儷儉債傾傯僂僨償儻儐儲儺兒兌兗黨蘭關興茲養獸囅內岡冊寫軍農塚馮衝決況凍淨淒涼淩減湊凜幾鳳鳧憑凱擊氹鑿芻劃劉則剛創刪別剗剄劊劌剴劑剮劍剝劇勸辦務勱動勵勁勞勢勳猛勩勻匭匱區醫華協單賣盧鹵臥衛卻巹廠廳曆厲壓厭厙廁廂厴廈廚廄廝縣參靉靆雙發變敘疊葉號歎嘰籲後嚇呂嗎唚噸聽啟吳嘸囈嘔嚦唄員咼嗆嗚詠哢嚨嚀噝吒噅鹹呱響啞噠嘵嗶噦嘩噲嚌噥喲嘜嗊嘮啢嗩唕喚呼嘖嗇囀齧囉嘽嘯噴嘍嚳囁嗬噯噓嚶囑嚕劈囂謔團園囪圍圇國圖圓聖壙場阪壞塊堅壇壢壩塢墳墜壟壟壚壘墾坰堊墊埡墶壋塏堖塒塤堝墊垵塹墮壪牆壯聲殼壺壼處備複夠頭誇夾奪奩奐奮獎奧妝婦媽嫵嫗媯姍薑婁婭嬈嬌孌娛媧嫻嫿嬰嬋嬸媼嬡嬪嬙嬤孫學孿寧寶實寵審憲宮寬賓寢對尋導壽將爾塵堯尷屍盡層屭屜屆屬屢屨嶼歲豈嶇崗峴嶴嵐島嶺嶽崠巋嶨嶧峽嶢嶠崢巒嶗崍嶮嶄嶸嶔崳嶁脊巔鞏巰幣帥師幃帳簾幟帶幀幫幬幘幗冪襆幹並廣莊慶廬廡庫應廟龐廢廎廩開異棄張彌弳彎彈強歸當錄彠彥徹徑徠禦憶懺憂愾懷態慫憮慪悵愴憐總懟懌戀懇惡慟懨愷惻惱惲悅愨懸慳憫驚懼慘懲憊愜慚憚慣湣慍憤憒願懾憖怵懣懶懍戇戔戲戧戰戩戶紮撲扡執擴捫掃揚擾撫拋摶摳掄搶護報擔擬攏揀擁攔擰撥擇掛摯攣掗撾撻挾撓擋撟掙擠揮撏撈損撿換搗據撚擄摑擲撣摻摜摣攬撳攙擱摟攪攜攝攄擺搖擯攤攖撐攆擷擼攛擻攢敵斂數齋斕鬥斬斷無舊時曠暘曇晝曨顯晉曬曉曄暈暉暫曖劄術樸機殺雜權條來楊榪傑極構樅樞棗櫪梘棖槍楓梟櫃檸檉梔柵標棧櫛櫳棟櫨櫟欄樹棲樣欒棬椏橈楨檔榿橋樺檜槳樁夢檮棶檢欞槨櫝槧欏橢樓欖櫬櫚櫸檟檻檳櫧橫檣櫻櫫櫥櫓櫞簷檁歡歟歐殲歿殤殘殞殮殫殯毆毀轂畢斃氈毿氌氣氫氬氳彙漢汙湯洶遝溝沒灃漚瀝淪滄渢溈滬濔濘淚澩瀧瀘濼瀉潑澤涇潔灑窪浹淺漿澆湞溮濁測澮濟瀏滻渾滸濃潯濜塗湧濤澇淶漣潿渦溳渙滌潤澗漲澀澱淵淥漬瀆漸澠漁瀋滲溫遊灣濕潰濺漵漊潷滾滯灩灄滿瀅濾濫灤濱灘澦濫瀠瀟瀲濰潛瀦瀾瀨瀕灝滅燈靈災燦煬爐燉煒熗點煉熾爍爛烴燭煙煩燒燁燴燙燼熱煥燜燾煆糊溜愛爺牘犛牽犧犢強狀獷獁猶狽麅獮獰獨狹獅獪猙獄猻獫獵獼玀豬貓蝟獻獺璣璵瑒瑪瑋環現瑲璽瑉玨琺瓏璫琿璡璉瑣瓊瑤璦璿瓔瓚甕甌電畫暢佘疇癤療瘧癘瘍鬁瘡瘋皰屙癰痙癢瘂癆瘓癇癡癉瘮瘞瘺癟癱癮癭癩癬癲臒皚皺皸盞鹽監蓋盜盤瞘眥矓著睜睞瞼瞞矚矯磯礬礦碭碼磚硨硯碸礪礱礫礎硜矽碩硤磽磑礄確鹼礙磧磣堿镟滾禮禕禰禎禱禍稟祿禪離禿稈種積稱穢穠穭稅穌穩穡窮竊竅窯竄窩窺竇窶豎競篤筍筆筧箋籠籩築篳篩簹箏籌簽簡籙簀篋籜籮簞簫簣簍籃籬籪籟糴類秈糶糲粵糞糧糝餱緊縶糸糾紆紅紂纖紇約級紈纊紀紉緯紜紘純紕紗綱納紝縱綸紛紙紋紡紵紖紐紓線紺絏紱練組紳細織終縐絆紼絀紹繹經紿綁絨結絝繞絰絎繪給絢絳絡絕絞統綆綃絹繡綌綏絛繼綈績緒綾緓續綺緋綽緔緄繩維綿綬繃綢綯綹綣綜綻綰綠綴緇緙緗緘緬纜緹緲緝縕繢緦綞緞緶線緱縋緩締縷編緡緣縉縛縟縝縫縗縞纏縭縊縑繽縹縵縲纓縮繆繅纈繚繕繒韁繾繰繯繳纘罌網羅罰罷羆羈羥羨翹翽翬耮耬聳恥聶聾職聹聯聵聰肅腸膚膁腎腫脹脅膽勝朧腖臚脛膠脈膾髒臍腦膿臠腳脫腡臉臘醃膕齶膩靦膃騰臏臢輿艤艦艙艫艱豔艸藝節羋薌蕪蘆蓯葦藶莧萇蒼苧蘇檾蘋莖蘢蔦塋煢繭荊薦薘莢蕘蓽蕎薈薺蕩榮葷滎犖熒蕁藎蓀蔭蕒葒葤藥蒞蓧萊蓮蒔萵薟獲蕕瑩鶯蓴蘀蘿螢營縈蕭薩蔥蕆蕢蔣蔞藍薊蘺蕷鎣驀薔蘞藺藹蘄蘊藪槁蘚虜慮虛蟲虯蟣雖蝦蠆蝕蟻螞蠶蠔蜆蠱蠣蟶蠻蟄蛺蟯螄蠐蛻蝸蠟蠅蟈蟬蠍螻蠑螿蟎蠨釁銜補襯袞襖嫋褘襪襲襏裝襠褌褳襝褲襇褸襤繈襴見觀覎規覓視覘覽覺覬覡覿覥覦覯覲覷觴觸觶讋譽謄訁計訂訃認譏訐訌討讓訕訖訓議訊記訒講諱謳詎訝訥許訛論訩訟諷設訪訣證詁訶評詛識詗詐訴診詆謅詞詘詔詖譯詒誆誄試詿詩詰詼誠誅詵話誕詬詮詭詢詣諍該詳詫諢詡譸誡誣語誚誤誥誘誨誑說誦誒請諸諏諾讀諑誹課諉諛誰諗調諂諒諄誶談誼謀諶諜謊諫諧謔謁謂諤諭諼讒諮諳諺諦謎諞諝謨讜謖謝謠謗諡謙謐謹謾謫譾謬譚譖譙讕譜譎讞譴譫讖穀豶貝貞負貟貢財責賢敗賬貨質販貪貧貶購貯貫貳賤賁貰貼貴貺貸貿費賀貽賊贄賈賄貲賃賂贓資賅贐賕賑賚賒賦賭齎贖賞賜贔賙賡賠賧賴賵贅賻賺賽賾贗讚贇贈贍贏贛赬趙趕趨趲躉躍蹌蹠躒踐躂蹺蹕躚躋踴躊蹤躓躑躡蹣躕躥躪躦軀車軋軌軒軑軔轉軛輪軟轟軲軻轤軸軹軼軤軫轢軺輕軾載輊轎輈輇輅較輒輔輛輦輩輝輥輞輬輟輜輳輻輯轀輸轡轅轄輾轆轍轔辭辯辮邊遼達遷過邁運還這進遠違連遲邇逕跡適選遜遞邐邏遺遙鄧鄺鄔郵鄒鄴鄰鬱郤郟鄶鄭鄆酈鄖鄲醞醱醬釅釃釀釋裏钜鑒鑾鏨釓釔針釘釗釙釕釷釺釧釤鈒釩釣鍆釹鍚釵鈃鈣鈈鈦鈍鈔鍾鈉鋇鋼鈑鈐鑰欽鈞鎢鉤鈧鈁鈥鈄鈕鈀鈺錢鉦鉗鈷缽鈳鉕鈽鈸鉞鑽鉬鉭鉀鈿鈾鐵鉑鈴鑠鉛鉚鈰鉉鉈鉍鈹鐸鉶銬銠鉺銪鋏鋣鐃銍鐺銅鋁銱銦鎧鍘銖銑鋌銩銛鏵銓鉿銚鉻銘錚銫鉸銥鏟銃鐋銨銀銣鑄鐒鋪鋙錸鋱鏈鏗銷鎖鋰鋥鋤鍋鋯鋨鏽銼鋝鋒鋅鋶鐦鐧銳銻鋃鋟鋦錒錆鍺錯錨錡錁錕錩錫錮鑼錘錐錦鍁錈錇錟錠鍵鋸錳錙鍥鍈鍇鏘鍶鍔鍤鍬鍾鍛鎪鍠鍰鎄鍍鎂鏤鎡鏌鎮鎛鎘鑷鐫鎳鎿鎦鎬鎊鎰鎔鏢鏜鏍鏰鏞鏡鏑鏃鏇鏐鐔钁鐐鏷鑥鐓鑭鐠鑹鏹鐙鑊鐳鐶鐲鐮鐿鑔鑣鑞鑲長門閂閃閆閈閉問闖閏闈閑閎間閔閌悶閘鬧閨聞闥閩閭闓閥閣閡閫鬮閱閬闍閾閹閶鬩閿閽閻閼闡闌闃闠闊闋闔闐闒闕闞闤隊陽陰陣階際陸隴陳陘陝隉隕險隨隱隸雋難雛讎靂霧霽黴靄靚靜靨韃鞽韉韝韋韌韍韓韙韞韜韻頁頂頃頇項順須頊頑顧頓頎頒頌頏預顱領頗頸頡頰頲頜潁熲頦頤頻頮頹頷頴穎顆題顒顎顓顏額顳顢顛顙顥纇顫顬顰顴風颺颭颮颯颶颸颼颻飀飄飆飆飛饗饜飣饑飥餳飩餼飪飫飭飯飲餞飾飽飼飿飴餌饒餉餄餎餃餏餅餑餖餓餘餒餕餜餛餡館餷饋餶餿饞饁饃餺餾饈饉饅饊饌饢馬馭馱馴馳驅馹駁驢駔駛駟駙駒騶駐駝駑駕驛駘驍罵駰驕驊駱駭駢驫驪騁驗騂駸駿騏騎騍騅騌驌驂騙騭騤騷騖驁騮騫騸驃騾驄驏驟驥驦驤髏髖髕鬢魘魎魚魛魢魷魨魯魴魺鮁鮃鯰鱸鮋鮓鮒鮊鮑鱟鮍鮐鮭鮚鮳鮪鮞鮦鰂鮜鱠鱭鮫鮮鮺鯗鱘鯁鱺鰱鰹鯉鰣鰷鯀鯊鯇鮶鯽鯒鯖鯪鯕鯫鯡鯤鯧鯝鯢鯰鯛鯨鯵鯴鯔鱝鰈鰏鱨鯷鰮鰃鰓鱷鰍鰒鰉鰁鱂鯿鰠鼇鰭鰨鰥鰩鰟鰜鰳鰾鱈鱉鰻鰵鱅鰼鱖鱔鱗鱒鱯鱤鱧鱣鳥鳩雞鳶鳴鳲鷗鴉鶬鴇鴆鴣鶇鸕鴨鴞鴦鴒鴟鴝鴛鴬鴕鷥鷙鴯鴰鵂鴴鵃鴿鸞鴻鵐鵓鸝鵑鵠鵝鵒鷳鵜鵡鵲鶓鵪鶤鵯鵬鵮鶉鶊鵷鷫鶘鶡鶚鶻鶿鶥鶩鷊鷂鶲鶹鶺鷁鶼鶴鷖鸚鷓鷚鷯鷦鷲鷸鷺鸇鷹鸌鸏鸛鸘鹺麥麩黃黌黶黷黲黽黿鼂鼉鞀鼴齇齊齏齒齔齕齗齟齡齙齠齜齦齬齪齲齷龍龔龕龜誌製谘隻裡係範鬆冇嚐嘗鬨麵準鐘彆閒乾儘臟拚";
        if ($data_is_utf8) $word = 3;
        else $word = 2;
        if ($type) {
            $srcString = $simcn;
            $toString = $tracn;
        } else {
            $srcString = $tracn;
            $toString = $simcn;
        }
        $tran = array ();
        $cntmp = '';
        $twtmp = '';
        for($i = 0; $i <= strlen ( $srcString ); $i ++) {
            if ($i != 0 && $i % $word == 0) {
                $tran [$cntmp] = $twtmp;
                if ($i != strlen ( $srcString )) {
                    $cntmp = $srcString {$i};
                    $twtmp = $toString {$i};
                }
            } else {
                $cntmp .= $srcString {$i};
                $twtmp .= $toString {$i};
            }
        }
        $return = '';
        $cnword = '';
        for($i = 0; $i <= strlen ( $data ); $i ++) {
            if ($i != 0 && $i % $word == 0) {
                $return .= isset ( $tran [$cnword] ) ? $tran [$cnword] : $cnword;
                if ($i != strlen ( $data ))
                    $cnword = $data {$i};
            } else {
                $cnword .= $data {$i};
            }
        }
        return $return;
    }
    /**
     * @static
     * utf16be编码转化为utf8编码
     * @param string $str
     * @return string
     */
    public static function utf16beToUtf8(&$str) {
        return self::unicodeToUtf8(unpack('n*', $str));
    }
    /**
     * @static
     * unicode编码转化为utf8编码
     * @param string $str 要转换的字符串,不能为null
     * @return string
     */
    public static function unicodeToUtf8(&$str) {
        if(empty($str) || (strlen($str)%4) != 0 ) return false;
        $utf8 = '';
        $len = strlen($str)/4;
        for($i=0;$i<$len;$i++){
            $unicode=substr($str, $i*4, 4);
            if ($unicode < 128) {
                $utf8 .= chr($unicode);
            } elseif ($unicode < 2048) {
                $utf8 .= chr(192 + (($unicode - ($unicode % 64)) / 64));
                $utf8 .= chr(128 + ($unicode % 64));
            } else {
                $utf8 .= chr(224 + (($unicode - ($unicode % 4096)) / 4096));
                $utf8 .= chr(128 + ((($unicode % 4096) - ($unicode % 64)) / 64));
                $utf8 .= chr(128 + ($unicode % 64));
            }
        }
        return $utf8;
    }
    /**
     * @static
     * utf8编码转为utf16BE
     * @param string $str 要转换的字符串,不能为null
     * @param boolean $bom 是否增加bom标记 Big-Endian
     * @return string
     */
    public static function utf8ToUtf16BE(&$str, $bom = false) {
        $out = $bom ? '\xFE\xFF' : '';
        if (function_exists('mb_convert_encoding')) {
            return $out . mb_convert_encoding($str, 'UTF-16BE', 'UTF-8');
        }
        $uni = self::utf8ToUnicode($str);
        foreach ($uni as $cp) {
            $out .= pack('n', $cp);
        }
        return $out;
    }
    /**
     * @static
     * utf8编码转化为unicode
     * @param string $str 要转换的字符串,不能为null
     * @return string
     */
    public static function utf8ToUnicode(&$str) {
        $unicode = array();
        $values = array();
        $lookingFor = 1;
        for ($i = 0, $length = strlen($str); $i < $length; $i++) {
            $thisValue = ord($str[$i]);
            if ($thisValue < 128) {
                $unicode[] = $thisValue;
            } else {
                if (count($values) == 0) {
                    $lookingFor = ($thisValue < 224) ? 2 : 3;
                }
                $values[] = $thisValue;
                if (count($values) == $lookingFor) {
                    $unicode[] = ($lookingFor == 3) ? ($values[0] % 16) * 4096 + ($values[1] % 64) * 64 + $values[2] % 64 : ($values[0] % 32) * 64 + $values[1] % 64;
                    $values = array();
                    $lookingFor = 1;
                }
            }
        }
        return $unicode;
    }
    /**
     * @static
     * 检测字符串的编码是否为utf8
     * @param string $str 待检测的字符串
     * @return bool
     */
    public static function isUtf8($str) {
        return (bool)preg_match('%^(?:
        [\x09\x0A\x0D\x20-\x7E]              # ASCII
        | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
        |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
        | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
        |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
        |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
        | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
        |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
    )*$%xs', $str);
    }
    /**
     * @static
     * base64 加密
     * @param array|string $data 字符或数组
     * @return array|string
     */
    public static function encodeBase64($data) {
        if(is_array($data)) return array_map(__METHOD__ , $data);
        return base64_encode($data);
    }
    /**
     * @static
     * base64 解密
     * @param array|string $data 字符或数组
     * @return array|string
     */
    public static function decodeBase64($data) {
        if(is_array($data)) return array_map(__METHOD__ , $data);
        return base64_decode($data);
    }

    /**
     * 以指定页长方式分割多语言文本，支持gb2312,gbk,utf-8,big5
     * @param string $str 主体字符串
     * @param int $length 截取长度
     * @param string $charset utf-8|gb2312|gbk|big5 编码
     * @param bool $suffix 是否加尾缀
     * @param string $suffix_str 尾缀字符串
     * @return array
     */
    public function splitText($str, $length, $charset='utf-8', $suffix=false, $suffix_str='……' ){
        $str_len= self::length($str, $charset);
        $n= ceil($str_len/$length);
        if($n==1) return array($str);
        $str_array=array();
        for($i=0;$i<$n;$i++){
            $str_array[$i]=self::subText($str, $i*$length, $length, $charset, $suffix, $suffix_str);
        }
        return $str_array;
    }

    /**
     * 尽最大努力自动分割多语言html内容，并保存到数组，支持gb2312,gbk,utf-8,big5
     * @param string $content 主体字符串
     * @param int $page_max_length 结果数组中每项的最大显示字符数（忽略了Html代码）
     * @param string $charset utf-8|gb2312|gbk|big5 编码
     * @param array $tag_array 分割的字符或Html标记（注意字符的顺序），默认为array('</table>', '</div>', '</p>', '<br/>', '”。', '。', '.', '！', '……', '？',',')
     * @return array 数组
     */
    public function splitHtml( $content, $page_max_length=500, $tag_array=null, $charset='utf-8' ){
        $a=array('&', '"', '<', '>',' ');
        $b=array('&amp;', '&quot;', '&lt;', '&gt;','&nbsp;');
        $str = str_replace($b,$a, $content);
        $str_len= Str::length($str, $charset);
        if($str_len<$page_max_length)return array($content);
        $str_len= strlen($str);
        if(empty($tag_array) ){
            $tag_array=array('</table>', '</div>', '</p>', '<br/>', '”。', '。', '.', '！', '……', '？',',');
        }
        $content_array=array();
        $cut_start=$temp=0;
        do{
            $i=0;
            $cut_str =  substr($str, $cut_start, $page_max_length);
            if(strlen($cut_str)<$page_max_length){
                $content_array[]=str_replace($a,$b,$cut_str);
                break;
            }
            foreach ($tag_array as $tag){
                $i = strrpos($cut_str, $tag);   //逆向查找第一个分页符的位置
                if ($i!==FALSE){
                    $content_array[] = str_replace($a,$b,substr($cut_str, 0, $i).$tag);
                    $cut_start += $i + strlen($tag);
                    break;
                }
            }
            if(!$i){
                if($cut_start+$page_max_length==$str_len-1){
                    $content_array[]=str_replace($a,$b,$cut_str);
                    break;
                }  else {
                    $temp=  Str::subText(
                        $cut_str,
                        0,
                        Str::length($cut_str,$charset)-1,
                        $charset,
                        false);
                    $cut_start+=strlen($temp);
                    $content_array[]=str_replace($a,$b,$temp);
                }
            }
        }while(1);
        return $content_array;
    }
}
