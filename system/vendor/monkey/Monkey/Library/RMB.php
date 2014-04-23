<?php
namespace Library;

/**
 * 财务工具 rmb
 *转换示例
 * $cn=new rmb();
 * $rmb=$cn->to_rmb($num);
 * $date=$cn->to_date();
 */
class RMB {
    /**
     * 将数字金额转换为人民币金额
     * @param int|string $numbers 数字金额
     * @return string
     */
    public function numbersToRMB($numbers){
        $capnum=array("零","壹","贰","叁","肆","伍","陆","柒","捌","玖");
        $capdigit=array("","拾","佰","仟");
        $subdata=explode(".",(string)$numbers);
        $yuan=$subdata[0];
        $j=0; $nonzero=0;
        $cncap='';
        for($i=0;$i<strlen($subdata[0]);$i++){
            if(0==$i){ //确定个位
                if($subdata[1]){
                    $cncap=(substr($subdata[0],-1,1)!=0)?"元":"元零";
                }else{
                    $cncap="元";
                }
            }
            if(4==$i){ $j=0;  $nonzero=0; $cncap="万".$cncap; } //确定万位
            if(8==$i){ $j=0;  $nonzero=0; $cncap="亿".$cncap; } //确定亿位
            $numb=substr($yuan,-1,1); //截取尾数
            $cncap=($numb)?$capnum[$numb].$capdigit[$j].$cncap:(($nonzero)?"零".$cncap:$cncap);
            $nonzero=($numb)?1:$nonzero;
            $yuan=substr($yuan,0,strlen($yuan)-1); //截去尾数
            $j++;
        }
        if($subdata[1]){
            $cncap.=(substr($subdata[1],0,1))?$capnum[substr($subdata[1],0,1)]."角":"零";
            $cncap.=(substr($subdata[1],1,1))?$capnum[substr($subdata[1],1,1)]."分":"零分";
        }
        $cncap=preg_replace("/(零)+/","\\1",$cncap); //合并连续“零”
        return $cncap.'整';
    }
    /**
     * 将日期转换为支票的标准日期（贰零壹拾贰年肆月壹日）
     * @param string $dateString 时间字符串
     * @return string
     */
    public function dateToRMB( $dateString=null) {
                if(empty($dateString)){
                    $Year=date("Y");
                    $Mon=date("m");
                    $Day=date("d");
                }else{
                    $date=strtotime($dateString);
                    $Year=date("Y",$date);
                    $Mon=date("m",$date);
                    $Day=date("d",$date);
                }
                $n=strlen($Year);$Y='';$M='';$D='';
                for($m=0;$m<$n;$m++)
                {
                    $jiaow=substr($Year,$m,1);
                    if($jiaow==1) $Y.="壹";
                    if($jiaow==2) $Y.="贰";
                    if($jiaow==3) $Y.="叁";
                    if($jiaow==4) $Y.="肆";
                    if($jiaow==5) $Y.="伍";
                    if($jiaow==6) $Y.="陆";
                    if($jiaow==7) $Y.="柒";
                    if($jiaow==8) $Y.="捌";
                    if($jiaow==9) $Y.="玖";
                    if($jiaow==0) $Y.="零";
                }
                if($Mon==1) $M="零壹";
                if($Mon==2) $M="零贰";
                if($Mon==3) $M="零叁";
                if($Mon==4) $M="零肆";
                if($Mon==5) $M="零伍";
                if($Mon==6) $M="零陆";
                if($Mon==7) $M="零柒";
                if($Mon==8) $M="零捌";
                if($Mon==9) $M="零玖";
                if($Mon==10) $M="零壹拾";
                if($Mon==11) $M="壹拾壹";
                if($Mon==12) $M="壹拾贰";

                $r1=substr($Day,0,1);
                $r2=substr($Day,1,1);
                if($r1==0) {
                if($r2==1) $D="零壹";
                if($r2==2) $D="零贰";
                if($r2==3) $D="零叁";
                if($r2==4) $D="零肆";
                if($r2==5) $D="零伍";
                if($r2==6) $D="零陆";
                if($r2==7) $D="零柒";
                if($r2==8) $D="零捌";
                if($r2==9) $D="零玖";
                }
                else {
                    if($r1==1) {
                        if($r2==1) $D="壹拾壹";
                        if($r2==2) $D="壹拾贰";
                        if($r2==3) $D="壹拾叁";
                        if($r2==4) $D="壹拾肆";
                        if($r2==5) $D="壹拾伍";
                        if($r2==6) $D="壹拾陆";
                        if($r2==7) $D="壹拾柒";
                        if($r2==8) $D="壹拾捌";
                        if($r2==9) $D="壹拾玖";
                        if($r2==0) $D="零壹拾";
                    }else {
                        if($r1==2) {
                            if($r2==1) $D="贰拾壹";
                            if($r2==2) $D="贰拾贰";
                            if($r2==3) $D="贰拾叁";
                            if($r2==4) $D="贰拾肆";
                            if($r2==5) $D="贰拾伍";
                            if($r2==6) $D="贰拾陆";
                            if($r2==7) $D="贰拾柒";
                            if($r2==8) $D="贰拾捌";
                            if($r2==9) $D="贰拾玖";
                            if($r2==0) $D="零贰拾";
                        }else {
                            if($r2==1) $D="叁拾壹";
                            if($r2==0) $D="零叁拾";
                        }
                    }
                }
                Return $Y."年".$M."月".$D."日";
    }
}